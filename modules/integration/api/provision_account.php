<?php
// provision_account.php — Student Account Provisioning
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/log_audit.php';
require_once __DIR__ . '/../../../app/config/mailer.php';

$input     = json_decode(file_get_contents('php://input'), true);
$studentId = intval($input['student_id'] ?? 0);

session_start();
$adminName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'System';
$adminRole = $_SESSION['user_role'] ?? $_SESSION['role']     ?? 'admin';

if (!$studentId) {
    echo json_encode(['success' => false, 'message' => 'Invalid student_id.']);
    exit;
}

try {
    $conn = getDBConnection();
    $conn->beginTransaction();

    // ── 1. Fetch student + check if account already exists ─────────────
    $stmt = $conn->prepare("
        SELECT s.*,
               CASE WHEN u.id IS NOT NULL THEN true ELSE false END AS has_account
        FROM students s
        LEFT JOIN users u
            ON u.role = 'student'
           AND LOWER(u.personal_email) = LOWER(s.personal_email)
        WHERE s.id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit;
    }

    $fullName = trim(
        $student['first_name'] . ' ' .
        ($student['middle_name'] ? $student['middle_name'] . ' ' : '') .
        $student['last_name']
    );

    // ── 2. Guard checks ────────────────────────────────────────────────
    if ($student['status'] !== 'approved') {
        logAudit($conn, $adminName, $adminRole, 'Account Creation Failed',
            "Attempted to provision account for {$fullName} but status is '{$student['status']}'.",
            $fullName, 'Failed');
        echo json_encode(['success' => false, 'message' => 'Student registration is not yet approved.']);
        exit;
    }
    if ($student['has_account']) {
        logAudit($conn, $adminName, $adminRole, 'Account Creation Failed',
            "Attempted to provision a duplicate account for {$fullName}. Account already exists.",
            $fullName, 'Failed');
        echo json_encode(['success' => false, 'message' => 'An account already exists for this student.']);
        exit;
    }

    // ── 3. Generate institutional email ───────────────────────────────
    $firstInitial = strtolower(substr($student['first_name'], 0, 1));
    $cleanLast    = strtolower(preg_replace('/\s+/', '', $student['last_name']));
    $baseEmail    = "{$firstInitial}{$cleanLast}@bcp.edu.ph";
    $finalEmail   = $baseEmail;
    $counter      = 1;

    $emailCheck = $conn->prepare("SELECT id FROM users WHERE institutional_email = ?");
    $emailCheck->execute([$finalEmail]);
    while ($emailCheck->fetch()) {
        $finalEmail = "{$firstInitial}{$cleanLast}{$counter}@bcp.edu.ph";
        $emailCheck->execute([$finalEmail]);
        $counter++;
    }

    // ── 4. Generate User ID ───────────────────────────────────────────
    $year     = date('Y');
    $userId   = null;
    $uidCheck = $conn->prepare("SELECT id FROM users WHERE user_id = ?");

    for ($i = 0; $i < 20; $i++) {
        $rand6 = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $tryId = "BCP-{$year}-{$rand6}";
        $uidCheck->execute([$tryId]);
        if (!$uidCheck->fetch()) { $userId = $tryId; break; }
    }
    if ($userId === null) throw new Exception('Failed to generate a unique User ID.');

    // ── 5. Generate temporary password ────────────────────────────────
    $chars    = 'abcdefghjkmnpqrstuvwxyz';
    $uppers   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $digits   = '23456789';
    $specials = '!@#$%&*';
    $tempPassword =
        $uppers[random_int(0, strlen($uppers) - 1)] .
        $digits[random_int(0, strlen($digits) - 1)] .
        $specials[random_int(0, strlen($specials) - 1)] .
        implode('', array_map(fn() => $chars[random_int(0, strlen($chars) - 1)], range(1, 7)));
    $tempPassword   = str_shuffle($tempPassword);
    $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

    // ── 6. Insert into users ──────────────────────────────────────────
    $insert = $conn->prepare("
        INSERT INTO users (
            user_id,
            first_name, middle_name, last_name,
            institutional_email, password, must_change_password, role,
            personal_email, mobile_number,
            birth_date, gender,
            street_address, city, province, zip_code,
            status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 1, 'student',
            ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW()
        )
    ");
    $insert->execute([
        $userId,
        $student['first_name'], $student['middle_name'], $student['last_name'],
        $finalEmail, $hashedPassword,
        $student['personal_email'], $student['mobile_number'],
        $student['birth_date'], $student['gender'],
        $student['street_address'], $student['city'], $student['province'], $student['zip_code'],
    ]);

    // ── 7. Insert into student_details ───────────────────────────────
    $details = $conn->prepare("
        INSERT INTO student_details (
            user_id, year_level, program, major, enrollment_status,
            guardian_name, guardian_contact, guardian_relationship
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $details->execute([
        $userId,
        $student['year_level'], $student['program'], $student['major'], $student['enrollment_status'],
        $student['guardian_name'], $student['guardian_contact'], $student['guardian_relationship'],
    ]);

    // ── 8. Update student status ──────────────────────────────────────
    $upd = $conn->prepare("UPDATE students SET status = 'account_created' WHERE id = ?");
    $upd->execute([$student['id']]);

    $conn->commit();

    // ── 9. Send credentials email ─────────────────────────────────────
    $subject = "Your BCP SIEMS Account Has Been Created";
    $body    = "
        <p>Hello {$fullName},</p>
        <p>Your SIEMS student account has been successfully created. Use the credentials below to sign in:</p>
        <ul>
            <li><strong>User ID:</strong> {$userId}</li>
            <li><strong>Institutional Email:</strong> {$finalEmail}</li>
            <li><strong>Temporary Password:</strong> {$tempPassword}</li>
        </ul>
        <p>For security, please change your password immediately after first login.</p>
        <p>Login page: <a href=\"http://localhost/bcp-enrollment%20BACKUP/pages/login.php\">Open SIEMS Login</a></p>
    ";
    $emailSent = sendEmail($student['personal_email'], $subject, $body);

    // ── 10. Audit log ─────────────────────────────────────────────────
    logAudit($conn, $adminName, $adminRole, 'Account Created',
        "Student account provisioned for {$fullName}. User ID: {$userId}, Email: {$finalEmail}, Program: {$student['program']}. "
        . ($emailSent ? "Credentials sent to {$student['personal_email']}." : "Email failed."),
        $fullName, $emailSent ? 'Success' : 'Failed');

    echo json_encode([
        'success'        => true,
        'personal_email' => $student['personal_email'],
        'email_sent'     => $emailSent,
        'credentials'    => [
            'fullName'           => $fullName,
            'userId'             => $userId,
            'institutionalEmail' => $finalEmail,
            'temporaryPassword'  => $tempPassword,
        ],
        'message' => $emailSent
            ? 'Account provisioned successfully. Credentials email sent.'
            : 'Account provisioned successfully, but credentials email could not be sent.',
    ]);

} catch (Exception $e) {
    if (isset($conn)) { try { $conn->rollBack(); } catch (Exception $re) {} }
    logAudit($conn ?? null, $adminName, $adminRole, 'Account Creation Failed',
        "Error provisioning student ID {$studentId}: " . $e->getMessage(),
        "Student ID: {$studentId}", 'Failed');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

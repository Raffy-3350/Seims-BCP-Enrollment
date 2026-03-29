<?php
// provision_account.php — Module 2: Admin Account Provisioning
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
$adminName = $_SESSION['user_name']  ?? $_SESSION['username'] ?? 'System';
$adminRole = $_SESSION['user_role']  ?? $_SESSION['role']     ?? 'admin';

if (!$studentId) {
    echo json_encode(['success' => false, 'message' => 'Invalid student_id.']);
    exit;
}

try {
    $conn = getDBConnection();
    $conn->begin_transaction();

    // ── 1. Fetch student + check if account already exists ─────────────
    $stmt = $conn->prepare("
        SELECT s.*,
               CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END AS has_account
        FROM students s
        LEFT JOIN users u
            ON u.role = 'student'
           AND u.personal_email COLLATE utf8mb4_unicode_ci = s.personal_email COLLATE utf8mb4_unicode_ci
        WHERE s.id = ?
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

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
        logAudit(
            $conn,
            $adminName,
            $adminRole,
            'Account Creation Failed',
            "Attempted to provision account for {$fullName} but student status is '{$student['status']}' (must be 'approved').",
            $fullName,
            'Failed'
        );
        echo json_encode(['success' => false, 'message' => 'Student registration is not yet approved.']);
        exit;
    }
    if ($student['has_account']) {
        logAudit(
            $conn,
            $adminName,
            $adminRole,
            'Account Creation Failed',
            "Attempted to provision a duplicate account for {$fullName}. Account already exists.",
            $fullName,
            'Failed'
        );
        echo json_encode(['success' => false, 'message' => 'An account already exists for this student.']);
        exit;
    }

    // ── 3. Generate institutional email ───────────────────────────────
    $firstInitial = strtolower(substr($student['first_name'], 0, 1));
    $cleanLast    = strtolower(preg_replace('/\s+/', '', $student['last_name']));
    $baseEmail    = "{$firstInitial}{$cleanLast}@bcp.edu.ph";

    $emailCheck = $conn->prepare("SELECT id FROM users WHERE institutional_email = ?");
    $counter    = 1;
    $finalEmail = $baseEmail;
    $emailCheck->bind_param("s", $finalEmail);
    $emailCheck->execute();
    $emailCheck->store_result();
    while ($emailCheck->num_rows > 0) {
        $finalEmail = "{$firstInitial}{$cleanLast}{$counter}@bcp.edu.ph";
        $emailCheck->bind_param("s", $finalEmail);
        $emailCheck->execute();
        $emailCheck->store_result();
        $counter++;
    }
    $emailCheck->close();

    // ── 4. Generate User ID ───────────────────────────────────────────
    $year      = date('Y');
    $uidCheck  = $conn->prepare("SELECT id FROM users WHERE user_id = ?");
    $maxTries  = 20;
    $userId    = null;

    for ($i = 0; $i < $maxTries; $i++) {
        $rand6 = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $tryId = "BCP-{$year}-{$rand6}";

        $uidCheck->bind_param("s", $tryId);
        $uidCheck->execute();
        $uidCheck->store_result();

        if ($uidCheck->num_rows === 0) {
            $userId = $tryId;
            break;
        }
    }
    $uidCheck->close();

    if ($userId === null) {
        throw new Exception('Failed to generate a unique User ID. Please try again.');
    }

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

    $insert->bind_param(
        "ssssssssssssss",
        $userId,
        $student['first_name'],
        $student['middle_name'],
        $student['last_name'],
        $finalEmail,
        $hashedPassword,
        $student['personal_email'],
        $student['mobile_number'],
        $student['birth_date'],
        $student['gender'],
        $student['street_address'],
        $student['city'],
        $student['province'],
        $student['zip_code']
    );
    $insert->execute();
    $insert->close();

    // Save student-specific fields to student_details table
    $details = $conn->prepare("
        INSERT INTO student_details (
            user_id, year_level, program, major, enrollment_status,
            guardian_name, guardian_contact, guardian_relationship
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $details->bind_param(
        "ssssssss",
        $userId,
        $student['year_level'],
        $student['program'],
        $student['major'],
        $student['enrollment_status'],
        $student['guardian_name'],
        $student['guardian_contact'],
        $student['guardian_relationship']
    );
    $details->execute();
    $details->close();

    // ── 7. Update student status to account_created ───────────────────
    $upd = $conn->prepare("UPDATE students SET status = 'account_created' WHERE id = ?");
    $upd->bind_param("i", $student['id']);
    $upd->execute();
    $upd->close();

    $conn->commit();

    // ── 8. Send credentials email ─────────────────────────────────────
    $subject = "Your BCP SIEMS Account Has Been Created";
    $body = "
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

    // ── 9. Audit log ──────────────────────────────────────────────────
    logAudit(
        $conn,
        $adminName,
        $adminRole,
        'Account Created',
        "Student account provisioned for {$fullName}. "
        . "Assigned User ID: {$userId}, Institutional Email: {$finalEmail}, "
        . "Program: {$student['program']}, Year Level: {$student['year_level']}. "
        . ($emailSent
            ? "Credentials email sent to: {$student['personal_email']}."
            : "Credentials email failed to send to: {$student['personal_email']}."),
        $fullName,
        $emailSent ? 'Success' : 'Failed'
    );

    // ── 10. Return credentials ────────────────────────────────────────
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
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    logAudit(
        $conn ?? null,
        $adminName,
        $adminRole,
        'Account Creation Failed',
        "Database error while provisioning account for student ID {$studentId}: " . $e->getMessage(),
        "Student ID: {$studentId}",
        'Failed'
    );
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
<?php
// provision_staff_account.php — Staff Account Provisioning
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/log_audit.php';
require_once __DIR__ . '/../../../app/config/mailer.php';

$input   = json_decode(file_get_contents('php://input'), true);
$staffId = intval($input['staff_id'] ?? 0);

session_start();
$adminName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'System';
$adminRole = $_SESSION['user_role'] ?? $_SESSION['role']     ?? 'admin';

if (!$staffId) {
    echo json_encode(['success' => false, 'message' => 'Invalid staff_id.']);
    exit;
}

try {
    $conn = getDBConnection();
    $conn->beginTransaction();

    // ── 1. Fetch staff record + check if account already exists ───────
    $stmt = $conn->prepare("
        SELECT sp.*,
               CASE WHEN u.id IS NOT NULL THEN true ELSE false END AS has_account
        FROM staff_pending sp
        LEFT JOIN users u
            ON u.role = sp.role
           AND LOWER(u.personal_email) = LOWER(sp.personal_email)
        WHERE sp.id = ?
    ");
    $stmt->execute([$staffId]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        echo json_encode(['success' => false, 'message' => 'Staff record not found.']);
        exit;
    }

    $fullName = trim(
        $staff['first_name'] . ' ' .
        ($staff['middle_name'] ? $staff['middle_name'] . ' ' : '') .
        $staff['last_name']
    );
    $roleLabel = ucfirst($staff['role']);

    // ── 2. Guard checks ───────────────────────────────────────────────
    if ($staff['status'] !== 'approved') {
        logAudit($conn, $adminName, $adminRole, 'Staff Account Creation Failed',
            "Attempted to provision account for {$fullName} but status is '{$staff['status']}'.",
            $fullName, 'Failed');
        echo json_encode(['success' => false, 'message' => 'Staff registration is not yet approved.']);
        exit;
    }
    if ($staff['has_account']) {
        logAudit($conn, $adminName, $adminRole, 'Staff Account Creation Failed',
            "Attempted to provision a duplicate account for {$fullName}. Account already exists.",
            $fullName, 'Failed');
        echo json_encode(['success' => false, 'message' => 'An account already exists for this staff member.']);
        exit;
    }

    // ── 3. Generate institutional email ──────────────────────────────
    $firstInitial = strtolower(substr($staff['first_name'], 0, 1));
    $cleanLast    = strtolower(preg_replace('/\s+/', '', $staff['last_name']));
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

    // ── 4. Generate User ID ──────────────────────────────────────────
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

    // ── 5. Generate temporary password ───────────────────────────────
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

    // ── 6. Insert into users ─────────────────────────────────────────
    // FIX: Added temporary_password to INSERT — column is NOT NULL in the DB
    $insert = $conn->prepare("
        INSERT INTO users (
            user_id,
            first_name, middle_name, last_name,
            institutional_email, password, temporary_password, must_change_password, role,
            personal_email, mobile_number,
            birth_date, gender,
            street_address, city, province, zip_code,
            department_program, employment_type, access_level,
            status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, 1, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, 'active', NOW()
        )
    ");
    $insert->execute([
        $userId,
        $staff['first_name'], $staff['middle_name'], $staff['last_name'],
        $finalEmail, $hashedPassword, $tempPassword,   // <-- temporary_password added
        $staff['role'],
        $staff['personal_email'], $staff['mobile_number'],
        $staff['birth_date'], $staff['gender'],
        $staff['street_address'], $staff['city'], $staff['province'], $staff['zip_code'],
        $staff['department'],
        $staff['employment_type'],
        $staff['access_level'],
    ]);

    // ── 7. Insert into staff_details ─────────────────────────────────
    // Create table if it doesn't exist at all
    $conn->exec("
        CREATE TABLE IF NOT EXISTS staff_details (
            id               SERIAL PRIMARY KEY,
            user_id          VARCHAR(30) NOT NULL UNIQUE,
            department       VARCHAR(100) DEFAULT NULL,
            employment_type  VARCHAR(50)  DEFAULT 'Permanent',
            created_at       TIMESTAMP NOT NULL DEFAULT NOW()
        )
    ");

    // FIX: ALTER TABLE to add missing columns if they don't exist yet.
    // This handles cases where the table was created without these columns.
    $conn->exec("ALTER TABLE staff_details ADD COLUMN IF NOT EXISTS position       VARCHAR(100) DEFAULT NULL");
    $conn->exec("ALTER TABLE staff_details ADD COLUMN IF NOT EXISTS specialization VARCHAR(100) DEFAULT NULL");
    $conn->exec("ALTER TABLE staff_details ADD COLUMN IF NOT EXISTS access_level   VARCHAR(30)  DEFAULT 'Standard'");

    $details = $conn->prepare("
        INSERT INTO staff_details (user_id, department, position, specialization, employment_type, access_level)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $details->execute([
        $userId,
        $staff['department'],
        $staff['position'],
        $staff['specialization'],
        $staff['employment_type'],
        $staff['access_level'],
    ]);

    // ── 8. Update staff_pending status ───────────────────────────────
    $upd = $conn->prepare("UPDATE staff_pending SET status = 'account_created', updated_at = NOW() WHERE id = ?");
    $upd->execute([$staff['id']]);

    $conn->commit();

    // ── 9. Send credentials email ─────────────────────────────────────
    $subject = "Your BCP SIEMS Account Has Been Created";
    $body    = "
        <p>Hello {$fullName},</p>
        <p>Your SIEMS {$roleLabel} account has been successfully created. Use the credentials below to sign in:</p>
        <ul>
            <li><strong>Staff ID:</strong> {$userId}</li>
            <li><strong>Institutional Email:</strong> {$finalEmail}</li>
            <li><strong>Temporary Password:</strong> {$tempPassword}</li>
        </ul>
        <p>For security, please change your password immediately after first login.</p>
        <p>Login page: <a href=\"https://seims-bcp-enrollment-production.up.railway.app/pages/login.php\">Open SIEMS Login</a></p>
    ";
    $emailSent = sendEmail($staff['personal_email'], $subject, $body);

    // ── 10. Audit log ─────────────────────────────────────────────────
    logAudit($conn, $adminName, $adminRole, 'Staff Account Created',
        "{$roleLabel} account provisioned for {$fullName}. User ID: {$userId}, Email: {$finalEmail}, Dept: {$staff['department']}. "
        . ($emailSent ? "Credentials sent to {$staff['personal_email']}." : "Email failed."),
        $fullName, $emailSent ? 'Success' : 'Failed');

    echo json_encode([
        'success'        => true,
        'personal_email' => $staff['personal_email'],
        'email_sent'     => $emailSent,
        'credentials'    => [
            'fullName'           => $fullName,
            'userId'             => $userId,
            'role'               => $roleLabel,
            'institutionalEmail' => $finalEmail,
            'temporaryPassword'  => $tempPassword,
        ],
        'message' => $emailSent
            ? 'Staff account provisioned successfully. Credentials email sent.'
            : 'Staff account provisioned successfully, but credentials email could not be sent.',
    ]);

} catch (Exception $e) {
    if (isset($conn)) { try { $conn->rollBack(); } catch (Exception $re) {} }
    logAudit($conn ?? null, $adminName, $adminRole, 'Staff Account Creation Failed',
        "Error provisioning staff ID {$staffId}: " . $e->getMessage(),
        "Staff ID: {$staffId}", 'Failed');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

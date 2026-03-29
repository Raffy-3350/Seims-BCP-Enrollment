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
    $conn->begin_transaction();

    // ── 1. Fetch staff record + check if account already exists ───────
    $stmt = $conn->prepare("
        SELECT sp.*,
               CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END AS has_account
        FROM staff_pending sp
        LEFT JOIN users u
            ON u.role = sp.role
           AND u.personal_email COLLATE utf8mb4_unicode_ci = sp.personal_email COLLATE utf8mb4_unicode_ci
        WHERE sp.id = ?
    ");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $staff = $stmt->get_result()->fetch_assoc();
    $stmt->close();

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
        logAudit(
            $conn,
            $adminName,
            $adminRole,
            'Staff Account Creation Failed',
            "Attempted to provision account for {$fullName} but staff status is '{$staff['status']}' (must be 'approved').",
            $fullName,
            'Failed'
        );
        echo json_encode(['success' => false, 'message' => 'Staff registration is not yet approved.']);
        exit;
    }
    if ($staff['has_account']) {
        logAudit(
            $conn,
            $adminName,
            $adminRole,
            'Staff Account Creation Failed',
            "Attempted to provision a duplicate account for {$fullName}. Account already exists.",
            $fullName,
            'Failed'
        );
        echo json_encode(['success' => false, 'message' => 'An account already exists for this staff member.']);
        exit;
    }

    // ── 3. Generate institutional email ──────────────────────────────
    $firstInitial = strtolower(substr($staff['first_name'], 0, 1));
    $cleanLast    = strtolower(preg_replace('/\s+/', '', $staff['last_name']));
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

    // ── 4. Generate User ID ──────────────────────────────────────────
    $year     = date('Y');
    $uidCheck = $conn->prepare("SELECT id FROM users WHERE user_id = ?");
    $maxTries = 20;
    $userId   = null;

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
 $insert = $conn->prepare("
    INSERT INTO users (
        user_id,
        first_name, middle_name, last_name,
        institutional_email, password, must_change_password, role,
        personal_email, mobile_number,
        birth_date, gender,
        street_address, city, province, zip_code,
        department_program, employment_type, access_level,
        status, created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, 1, ?,
        ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, 'active', NOW()
    )
");

    $insert->bind_param(
        "sssssssssssssss",
        $userId,
        $staff['first_name'],
        $staff['middle_name'],
        $staff['last_name'],
        $finalEmail,
        $hashedPassword,
        $staff['role'],
        $staff['personal_email'],
        $staff['mobile_number'],
        $staff['birth_date'],
        $staff['gender'],
        $staff['street_address'],
        $staff['city'],
        $staff['province'],
        $staff['zip_code'],
        $staff['department'],       // → department_program
      $staff['employment_type'],  // → employment_type
    $staff['access_level']      // → access_level
    );
    $insert->execute();
    $insert->close();

    // ── 7. Insert staff-specific fields into staff_details ───────────
    // Creates the table if it doesn't exist yet, same pattern as staff_register.php
    $conn->query("
        CREATE TABLE IF NOT EXISTS `staff_details` (
            `id`                int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id`           varchar(30) NOT NULL,
            `department`        varchar(100) DEFAULT NULL,
            `employment_type`   varchar(50)  DEFAULT 'Permanent',
            `access_level`      varchar(30)  DEFAULT 'Standard',
            `created_at`        datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $details = $conn->prepare("
        INSERT INTO staff_details (
            user_id, department,
            employment_type, access_level
        ) VALUES (?, ?, ?, ?)
    ");
    $details->bind_param(
        "ssss",
        $userId,
        $staff['department'],
        $staff['employment_type'],
        $staff['access_level']
    );
    $details->execute();
    $details->close();

    // ── 8. Update staff_pending status to account_created ────────────
    $upd = $conn->prepare("UPDATE staff_pending SET status = 'account_created', updated_at = NOW() WHERE id = ?");
    $upd->bind_param("i", $staff['id']);
    $upd->execute();
    $upd->close();

    $conn->commit();

    // ── 9. Send credentials email ─────────────────────────────────────
    $subject = "Your BCP SIEMS Account Has Been Created";
    $body = "
        <p>Hello {$fullName},</p>
        <p>Your SIEMS {$roleLabel} account has been successfully created. Use the credentials below to sign in:</p>
        <ul>
            <li><strong>Staff ID:</strong> {$userId}</li>
            <li><strong>Institutional Email:</strong> {$finalEmail}</li>
            <li><strong>Temporary Password:</strong> {$tempPassword}</li>
        </ul>
        <p>For security, please change your password immediately after first login.</p>
        <p>Login page: <a href=\"http://localhost/bcp-enrollment%20BACKUP/pages/login.php\">Open SIEMS Login</a></p>
    ";
    $emailSent = sendEmail($staff['personal_email'], $subject, $body);

    // ── 10. Audit log ─────────────────────────────────────────────────
    logAudit(
        $conn,
        $adminName,
        $adminRole,
        'Staff Account Created',
        "{$roleLabel} account provisioned for {$fullName}. "
        . "Assigned User ID: {$userId}, Institutional Email: {$finalEmail}, "
        . "Department: {$staff['department']}, Position: {$staff['position']}. "
        . ($emailSent
            ? "Credentials email sent to: {$staff['personal_email']}."
            : "Credentials email failed to send to: {$staff['personal_email']}."),
        $fullName,
        $emailSent ? 'Success' : 'Failed'
    );

    // ── 11. Return credentials ────────────────────────────────────────
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
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    logAudit(
        $conn ?? null,
        $adminName,
        $adminRole,
        'Staff Account Creation Failed',
        "Database error while provisioning account for staff ID {$staffId}: " . $e->getMessage(),
        "Staff ID: {$staffId}",
        'Failed'
    );
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
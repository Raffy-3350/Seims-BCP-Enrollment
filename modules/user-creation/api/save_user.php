<?php
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "PHP Error: $errstr", 'file' => basename($errfile), 'line' => $errline]);
    exit;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $error['message']]);
    }
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/config.php';

    $rawInput = file_get_contents('php://input');
    if (empty($rawInput)) throw new Exception('No data received');

    $data = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON: ' . json_last_error_msg());

    foreach (['role', 'firstName', 'lastName', 'personalEmail'] as $f) {
        if (empty(trim($data[$f] ?? ''))) throw new Exception("Missing required field: $f");
    }

    $role = $data['role'];
    if (!in_array($role, VALID_ROLES)) throw new Exception("Invalid role: $role");

    if ($role === 'student') {
        $lrn = preg_replace('/\D/', '', $data['lrn'] ?? '');
        if (strlen($lrn) !== 12) throw new Exception('LRN must be exactly 12 digits');
    }

    $conn = getDBConnection();
    $conn->begin_transaction();

    $lrnForId            = ($role === 'student') ? ($data['lrn'] ?? null) : null;
    $userId              = generateNextUserID($conn, $role, $lrnForId);
    $institutionalEmail  = generateEmail($data['firstName'], $data['lastName']);
    $tempPassword        = generateTempPassword();
    $hashedPassword      = password_hash($tempPassword, PASSWORD_BCRYPT);

    $n = function($k) use ($data) {
        return (isset($data[$k]) && $data[$k] !== '') ? $data[$k] : null;
    };

    $u_middleName    = $n('middleName');
    $u_birthDate     = $n('birthDate');
    $u_gender        = $n('gender');
    $u_lrn           = $n('lrn');
    $u_mobileNumber  = $n('mobileNumber');
    $u_streetAddress = $n('streetAddress');
    $u_barangay      = $n('barangay');
    $u_city          = $n('city');
    $u_province      = $n('province');
    $u_zipCode       = $n('zipCode');

    $stmt = $conn->prepare("
        INSERT INTO users (
            user_id, role, first_name, middle_name, last_name,
            birth_date, gender, lrn,
            mobile_number, personal_email, institutional_email, temporary_password,
            street_address, barangay, city, province, zip_code
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);

    $stmt->bind_param(
        "sssssssssssssssss",
        $userId, $role,
        $data['firstName'], $u_middleName, $data['lastName'],
        $u_birthDate, $u_gender, $u_lrn,
        $u_mobileNumber, $data['personalEmail'], $institutionalEmail, $hashedPassword,
        $u_streetAddress, $u_barangay, $u_city, $u_province, $u_zipCode
    );
    if (!$stmt->execute()) throw new Exception('Insert user failed: ' . $stmt->error);

    if ($role === 'student') {

        $s_yearLevel            = $n('yearLevel');
        $s_program              = $n('program');
        $s_major                = $n('major');
        $s_enrollmentStatus     = $n('enrollmentStatus') ?? 'Regular';
        $s_guardianName         = $n('guardianName');
        $s_guardianContact      = $n('guardianContact');
        $s_guardianRelationship = $n('guardianRelationship');
        $stmt = $conn->prepare("
            INSERT INTO student_details
                (user_id, year_level, program, major, enrollment_status, guardian_name, guardian_contact, guardian_relationship)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssssss",
            $userId, $s_yearLevel, $s_program, $s_major,
            $s_enrollmentStatus, $s_guardianName, $s_guardianContact, $s_guardianRelationship
        );
        if (!$stmt->execute()) throw new Exception('Insert student_details failed: ' . $stmt->error);

    } elseif ($role === 'faculty') {

        $f_department     = $n('department');
        $f_position       = $n('position');
        $f_specialization = $n('specialization');
        $f_employmentType = $n('employmentType') ?? 'Full-time';
        $stmt = $conn->prepare("
            INSERT INTO faculty_details (user_id, department, position, specialization, employment_type)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $userId, $f_department, $f_position, $f_specialization, $f_employmentType);
        if (!$stmt->execute()) throw new Exception('Insert faculty_details failed: ' . $stmt->error);

    } elseif ($role === 'admin') {

        $a_department     = $n('department');
        $a_position       = $n('position');
        $a_employmentType = $n('employmentType') ?? 'Permanent';
        $a_supervisor     = $n('supervisor');
        $stmt = $conn->prepare("
            INSERT INTO admin_details (user_id, department, position, employment_type, supervisor)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $userId, $a_department, $a_position, $a_employmentType, $a_supervisor);
        if (!$stmt->execute()) throw new Exception('Insert admin_details failed: ' . $stmt->error);

    } elseif ($role === 'superadmin') {

        $sa_department     = $n('department');
        $sa_position       = $n('position');
        $sa_employmentType = $n('employmentType') ?? 'Permanent';
        $sa_supervisor     = $n('supervisor');
        $stmt = $conn->prepare("
            INSERT INTO superadmin_details (user_id, department, position, employment_type, supervisor)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $userId, $sa_department, $sa_position, $sa_employmentType, $sa_supervisor);
        if (!$stmt->execute()) throw new Exception('Insert superadmin_details failed: ' . $stmt->error);

    } elseif ($role === 'registrar') {

        $r_department     = $n('department');
        $r_position       = $n('position');
        $r_employmentType = $n('employmentType') ?? 'Permanent';
        $r_supervisor     = $n('supervisor');
        $stmt = $conn->prepare("
            INSERT INTO registrar_details (user_id, department, position, employment_type, supervisor)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $userId, $r_department, $r_position, $r_employmentType, $r_supervisor);
        if (!$stmt->execute()) throw new Exception('Insert registrar_details failed: ' . $stmt->error);

    } elseif ($role === 'cashier') {

        $c_department     = $n('department');
        $c_position       = $n('position');
        $c_employmentType = $n('employmentType') ?? 'Permanent';
        $c_supervisor     = $n('supervisor');
        $stmt = $conn->prepare("
            INSERT INTO cashier_details (user_id, department, position, employment_type, supervisor)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $userId, $c_department, $c_position, $c_employmentType, $c_supervisor);
        if (!$stmt->execute()) throw new Exception('Insert cashier_details failed: ' . $stmt->error);

    } elseif ($role === 'librarian') {

        $l_department     = $n('department');
        $l_position       = $n('position');
        $l_employmentType = $n('employmentType') ?? 'Permanent';
        $l_supervisor     = $n('supervisor');
        $stmt = $conn->prepare("
            INSERT INTO librarian_details (user_id, department, position, employment_type, supervisor)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $userId, $l_department, $l_position, $l_employmentType, $l_supervisor);
        if (!$stmt->execute()) throw new Exception('Insert librarian_details failed: ' . $stmt->error);

    }

    // ─── Send Activation/Approval Email ─────────────────────────────────
    $token  = bin2hex(random_bytes(16));
    $expiry = date("Y-m-d H:i:s", time() + 86400);

    $updToken = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE user_id = ?");
    if ($updToken) {
        $updToken->bind_param("sss", $token, $expiry, $userId);
        $updToken->execute();
        $updToken->close();
    }

    require_once __DIR__ . '/../../../app/config/mailer.php';

    $protocol       = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host           = $_SERVER['HTTP_HOST'];
    $currentDir     = dirname($_SERVER['PHP_SELF']);
    $baseDir        = str_replace('/modules/user-creation/api', '', $currentDir);
    $activationLink = "$protocol://$host$baseDir/pages/login.php?reset_token=" . urlencode($token);

    $emailSubject = "Activate Your SIEMS Account";
    $emailBody    = "<h3>Welcome to BCP SIEMS!</h3>
                     <p>Your account has been created successfully.</p>
                     <p>To approve your account and set your password, please click the link below:</p>
                     <p><a href='$activationLink' style='background:#0f246c;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;'>Activate Account & Set Password</a></p>
                     <p>This link expires in 24 hours.</p>";

    sendEmail($data['personalEmail'], $emailSubject, $emailBody);
    // ─────────────────────────────────────────────────────────────────────

    $conn->commit();

    $adminUser      = 'admin@bcp.edu.ph';
    $adminRole      = 'superadmin';
    $logDetails     = "Created new user '{$data['firstName']} {$data['lastName']}' with role '{$role}'.";
    $affectedEntity = $data['firstName'] . ' ' . $data['lastName'];
    logActivity($conn, $adminUser, $adminRole, 'User Created', $logDetails, $affectedEntity, 'Success');

    echo json_encode([
        'success' => true,
        'message' => 'User created successfully. Activation email sent.',
        'data'    => [
            'userId'             => $userId,
            'institutionalEmail' => $institutionalEmail,
            'temporaryPassword'  => $tempPassword,
            'fullName'           => $data['firstName'] . ' ' . $data['lastName']
        ]
    ]);

} catch (Exception $e) {
    if (isset($conn)) { $conn->rollback(); $conn->close(); }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
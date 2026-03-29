<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bcp_enrollment');

define('VALID_ROLES', ['student', 'faculty', 'admin', 'superadmin', 'registrar', 'cashier', 'librarian']);

define('ROLE_PREFIXES', [
    'student'    => 'BCP',
    'faculty'    => 'BCP-FAC',
    'admin'      => 'BCP-ADM',
    'superadmin' => 'BCP-SADM',
    'registrar'  => 'BCP-REG',
    'cashier'    => 'BCP-CSH',
    'librarian'  => 'BCP-LIB',
]);

define('ROLE_DETAIL_TABLES', [
    'student'    => 'student_details',
    'faculty'    => 'faculty_details',
    'admin'      => 'admin_details',
    'superadmin' => 'superadmin_details',
    'registrar'  => 'registrar_details',
    'cashier'    => 'cashier_details',
    'librarian'  => 'librarian_details',
]);

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

function generateNextUserID($conn, $role, $lrn = null) {
    $year     = date('Y');
    $prefixes = ROLE_PREFIXES;

    if ($role === 'student' && !empty($lrn)) {
        $last4 = substr(preg_replace('/\D/', '', $lrn), -6);
        return "BCP-$year-$last4";
    }

    $prefix  = $prefixes[$role] ?? 'BCP-STF';
    $maxTries = 10;

    for ($i = 0; $i < $maxTries; $i++) {
        $rand4  = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $newId  = "$prefix-$rand4";

        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $newId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row['cnt'] === 0) {
            return $newId;
        }
    }

    return "$prefix-" . substr(time(), -6);
}

function generateEmail($firstName, $lastName) {
    $initial   = strtolower(substr(trim($firstName), 0, 1));
    $cleanLast = strtolower(str_replace(' ', '', trim($lastName)));
    return $initial . '.' . $cleanLast . '@bcp.edu.ph';
}

function generateTempPassword($length = 12) {
    $chars    = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function logActivity($conn, $performed_by, $performed_by_role, $event_type, $details, $affected_entity, $status) {
    $stmt = $conn->prepare("
        INSERT INTO audit_log (performed_by, performed_by_role, event_type, details, affected_entity, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if ($stmt) {
        $stmt->bind_param("ssssss", $performed_by, $performed_by_role, $event_type, $details, $affected_entity, $status);
        $stmt->execute();
    }
}
?>
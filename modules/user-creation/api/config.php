<?php
// config.php — user-creation/api/config.php
// Reads Railway environment variables for PostgreSQL (same vars as db.php)

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: '5432';
$db_name = getenv('DB_NAME') ?: 'postgres';
$db_user = getenv('DB_USER') ?: 'postgres';
$db_pass = getenv('DB_PASSWORD') ?: '';

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

// Returns a PDO connection to PostgreSQL using Railway env vars
function getDBConnection() {
    global $db_host, $db_port, $db_name, $db_user, $db_pass;
    try {
        $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ]));
    }
}

function generateNextUserID($conn, $role, $lrn = null) {
    $year     = date('Y');
    $prefixes = ROLE_PREFIXES;

    if ($role === 'student' && !empty($lrn)) {
        $last6 = substr(preg_replace('/\D/', '', $lrn), -6);
        return "BCP-$year-$last6";
    }

    $prefix   = $prefixes[$role] ?? 'BCP-STF';
    $maxTries = 10;

    for ($i = 0; $i < $maxTries; $i++) {
        $rand6 = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $newId = "$prefix-$rand6";

        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE user_id = ?");
        $stmt->execute([$newId]);
        $row = $stmt->fetch();

        if ((int) $row['cnt'] === 0) {
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

// Audit log — uses PDO positional params
function logActivity($conn, $performed_by, $performed_by_role, $event_type, $details, $affected_entity, $status) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO audit_log (performed_by, performed_by_role, event_type, details, affected_entity, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$performed_by, $performed_by_role, $event_type, $details, $affected_entity, $status]);
    } catch (Exception $e) {
        error_log("logActivity failed: " . $e->getMessage());
    }
}

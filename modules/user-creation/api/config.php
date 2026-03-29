<?php
define('VALID_ROLES', ['student','faculty','admin','superadmin','registrar','cashier','librarian']);

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
    $host     = getenv('DB_HOST');
    $port     = getenv('DB_PORT') ?: '5432';
    $dbname   = getenv('DB_NAME') ?: 'postgres';
    $user     = getenv('DB_USER') ?: 'postgres';
    $password = getenv('DB_PASSWORD');
    try {
        return new PDO("pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require", $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        error_log("DB connection failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database connection error.']);
        exit;
    }
}

function generateNextUserID($pdo, $role, $lrn = null) {
    $year = date('Y');
    if ($role === 'student' && !empty($lrn)) {
        $last6 = substr(preg_replace('/\D/', '', $lrn), -6);
        return "BCP-$year-$last6";
    }
    $prefix = ROLE_PREFIXES[$role] ?? 'BCP-STF';
    for ($i = 0; $i < 10; $i++) {
        $rand6 = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $newId = "$prefix-$rand6";
        $stmt  = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
        $stmt->execute([$newId]);
        if ((int)$stmt->fetchColumn() === 0) return $newId;
    }
    return "$prefix-" . substr(time(), -6);
}

function generateEmail($firstName, $lastName) {
    return strtolower(substr(trim($firstName), 0, 1)) . '.' . strtolower(str_replace(' ', '', trim($lastName))) . '@bcp.edu.ph';
}

function generateTempPassword($length = 12) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    $pw = '';
    for ($i = 0; $i < $length; $i++) $pw .= $chars[random_int(0, strlen($chars) - 1)];
    return $pw;
}

function logActivity($pdo, $performed_by, $performed_by_role, $event_type, $details, $affected_entity, $status) {
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_log (performed_by, performed_by_role, event_type, details, affected_entity, status) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$performed_by, $performed_by_role, $event_type, $details, $affected_entity, $status]);
    } catch (Throwable $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../../integration/api/log_audit.php';

$adminName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'System';
$adminRole = $_SESSION['user_role'] ?? $_SESSION['role']     ?? 'admin';

if (!in_array(strtolower($adminRole), ['superadmin', 'admin'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$userId   = trim($data['user_id']  ?? '');
$password = $data['password'] ?? '';

if (empty($userId) || empty($password)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'User ID and password are required.']);
    exit;
}

if (strlen($password) < 10 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[\W_]/', $password)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Password does not meet strength requirements.']);
    exit;
}

try {
    $conn = getDBConnection();

    $check = $conn->prepare("SELECT first_name, last_name, role FROM users WHERE user_id = ?");
    $check->bind_param("s", $userId);
    $check->execute();
    $user = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$user) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    $fullName = $user['first_name'] . ' ' . $user['last_name'];
    $hash     = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE users SET
            password               = ?,
            must_change_password   = 0,
            password_changed       = 1,
            reset_token_hash       = NULL,
            reset_token_expires_at = NULL,
            updated_at             = NOW()
        WHERE user_id = ?
    ");
    $stmt->bind_param("ss", $hash, $userId);
    $stmt->execute();
    $stmt->close();

    logAudit(
        $conn,
        $adminName,
        $adminRole,
        'Admin Password Reset',
        "Admin reset the password for {$fullName} (ID: {$userId}, Role: {$user['role']}).",
        $fullName,
        'Success'
    );

    $buffered = ob_get_clean();
    echo json_encode([
        'success' => true,
        'message' => "Password for {$fullName} has been reset.",
        'debug'   => $buffered // remove this line once working
    ]);

} catch (Exception $e) {
    $buffered = ob_get_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'debug'   => $buffered
    ]);
}

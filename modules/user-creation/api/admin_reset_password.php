<?php
// admin_reset_password.php
ini_set('display_errors', 0);   // FIX: hide errors so they never corrupt JSON output
ini_set('display_startup_errors', 0);
error_reporting(0);
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
    $conn = getDBConnection(); // returns PDO

    // FIX: was using MySQLi bind_param/get_result — replaced with PDO execute()
    $check = $conn->prepare("SELECT first_name, last_name, role FROM users WHERE user_id = ?");
    $check->execute([$userId]);
    $user = $check->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    $fullName = $user['first_name'] . ' ' . $user['last_name'];
    $hash     = password_hash($password, PASSWORD_DEFAULT);

    // FIX: was using MySQLi bind_param — replaced with PDO execute()
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
    $stmt->execute([$hash, $userId]);

    logAudit(
        $conn,
        $adminName,
        $adminRole,
        'Admin Password Reset',
        "Admin reset the password for {$fullName} (ID: {$userId}, Role: {$user['role']}).",
        $fullName,
        'Success'
    );

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => "Password for {$fullName} has been reset successfully.",
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
    ]);
}

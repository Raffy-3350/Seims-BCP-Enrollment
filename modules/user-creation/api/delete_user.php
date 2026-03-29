<?php
error_reporting(0); ini_set('display_errors', 0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

try {
    require_once __DIR__ . '/config.php';

    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST','DELETE'])) {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $data   = json_decode(file_get_contents('php://input'), true);
    $userId = $data['userId'] ?? '';
    if (empty($userId)) { echo json_encode(['success' => false, 'message' => 'User ID is required']); exit; }

    $conn = getDBConnection();
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT user_id, role, first_name, last_name, personal_email FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) { $conn->rollBack(); echo json_encode(['success' => false, 'message' => 'User not found: ' . $userId]); exit; }

    $fullName      = $user['first_name'] . ' ' . $user['last_name'];
    $userRole      = $user['role'];
    $personalEmail = $user['personal_email'];

    // 1. Delete role-specific detail record
    if (isset(ROLE_DETAIL_TABLES[$userRole])) {
        $conn->prepare("DELETE FROM " . ROLE_DETAIL_TABLES[$userRole] . " WHERE user_id = ?")->execute([$userId]);
    }

    // 2. Delete from source registration table
    if (!empty($personalEmail)) {
        $tbl = ($userRole === 'student') ? 'students' : 'staff_pending';
        $conn->prepare("DELETE FROM $tbl WHERE personal_email = ?")->execute([$personalEmail]);
    }

    // 3. Delete from users
    $del = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $del->execute([$userId]);

    if ($del->rowCount() === 0) { $conn->rollBack(); echo json_encode(['success' => false, 'message' => 'Failed to delete user.']); exit; }

    $conn->commit();

    logActivity($conn, 'admin@bcp.edu.ph', 'superadmin', 'User Deleted',
        "Deleted user '$fullName' (ID: $userId, Role: $userRole).", $fullName, 'Success');

    echo json_encode(['success' => true, 'message' => "User '$fullName' deleted successfully",
        'data' => ['userId' => $userId, 'userName' => $fullName, 'role' => $userRole]]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

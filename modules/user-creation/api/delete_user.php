<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

try {
    require_once __DIR__ . '/config.php';

    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $data   = json_decode(file_get_contents('php://input'), true);
    $userId = $data['userId'] ?? '';

    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    $conn = getDBConnection();
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT user_id, role, first_name, last_name, institutional_email, personal_email FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'User not found: ' . $userId]);
        exit;
    }

    $user          = $result->fetch_assoc();
    $fullName      = $user['first_name'] . ' ' . $user['last_name'];
    $userRole      = $user['role'];
    $personalEmail = $user['personal_email'];

    // 1. Delete from role-specific detail table (student_details, staff_details, etc.)
    $detailTables = ROLE_DETAIL_TABLES;
    if (isset($detailTables[$userRole])) {
        $table = $detailTables[$userRole];
        $stmt  = $conn->prepare("DELETE FROM `$table` WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("s", $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 2. Delete from students or staff_pending using personal_email
    if (!empty($personalEmail)) {
        if ($userRole === 'student') {
            $del = $conn->prepare("DELETE FROM students WHERE personal_email = ?");
        } else {
            $del = $conn->prepare("DELETE FROM staff_pending WHERE personal_email = ?");
        }
        if ($del) {
            $del->bind_param("s", $personalEmail);
            $del->execute();
            $del->close();
        }
    }

    // 3. Delete from users table
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $userId);

    if (!$stmt->execute() || $stmt->affected_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete user.']);
        exit;
    }

    $conn->commit();

    $adminUser  = 'admin@bcp.edu.ph';
    $adminRole  = 'superadmin';
    $logDetails = "Deleted user '$fullName' (ID: $userId, Role: $userRole).";
    logActivity($conn, $adminUser, $adminRole, 'User Deleted', $logDetails, $fullName, 'Success');

    echo json_encode([
        'success' => true,
        'message' => "User '$fullName' deleted successfully",
        'data'    => ['userId' => $userId, 'userName' => $fullName, 'role' => $userRole],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    if (isset($conn)) { $conn->rollback(); $conn->close(); }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
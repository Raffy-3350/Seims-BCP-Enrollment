<?php
error_reporting(0);
ini_set('display_errors', 0);

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

    $data    = json_decode(file_get_contents('php://input'), true);
    $userId  = trim($data['userId']  ?? '');
    $newRole = trim($data['newRole'] ?? '');
    $reason  = trim($data['reason']  ?? '');

    if (empty($userId))  throw new Exception('User ID is required');
    if (empty($newRole)) throw new Exception('New role is required');
    if (!in_array($newRole, VALID_ROLES)) throw new Exception('Invalid role: ' . $newRole);

    $conn = getDBConnection();
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT user_id, role, first_name, last_name, lrn FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) throw new Exception('User not found: ' . $userId);

    $user     = $result->fetch_assoc();
    $oldRole  = $user['role'];
    $fullName = $user['first_name'] . ' ' . $user['last_name'];
    $lrn      = $user['lrn'];

    if ($oldRole === $newRole) throw new Exception('User already has the role: ' . $newRole);

    $newUserId = generateNextUserID($conn, $newRole, $lrn);

    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    $detailTables = ROLE_DETAIL_TABLES;
    foreach ($detailTables as $table) {
        $stmt = $conn->prepare("UPDATE `$table` SET user_id = ? WHERE user_id = ?");
        if ($stmt) { $stmt->bind_param("ss", $newUserId, $userId); $stmt->execute(); }
    }

    $stmt = $conn->prepare("UPDATE users SET user_id = ?, role = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->bind_param("sss", $newUserId, $newRole, $userId);
    if (!$stmt->execute()) throw new Exception('Failed to update user: ' . $stmt->error);

    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    if (isset($detailTables[$oldRole])) {
        $oldTable = $detailTables[$oldRole];
        $stmt = $conn->prepare("DELETE FROM `$oldTable` WHERE user_id = ?");
        if ($stmt) { $stmt->bind_param("s", $newUserId); $stmt->execute(); }
    }

    if (isset($detailTables[$newRole])) {
        $newTable = $detailTables[$newRole];
        if ($newRole === 'student') {
            $stmt = $conn->prepare("INSERT IGNORE INTO `$newTable` (user_id, enrollment_status) VALUES (?, 'Regular')");
        } elseif ($newRole === 'faculty') {
            $stmt = $conn->prepare("INSERT IGNORE INTO `$newTable` (user_id, employment_type) VALUES (?, 'Full-time')");
        } else {
            $stmt = $conn->prepare("INSERT IGNORE INTO `$newTable` (user_id, employment_type) VALUES (?, 'Permanent')");
        }
        $stmt->bind_param("s", $newUserId);
        if (!$stmt->execute()) throw new Exception('Failed to create detail record: ' . $stmt->error);
    }

    $conn->commit();

    $adminUser  = 'admin@bcp.edu.ph';
    $adminRole  = 'superadmin';
    $logDetails = "Changed role for '$fullName' from '$oldRole' to '$newRole'. " . (!empty($reason) ? "Reason: $reason" : "");
    logActivity($conn, $adminUser, $adminRole, 'Role Changed', $logDetails, $fullName, 'Success');

    echo json_encode([
        'success' => true,
        'message' => "Role updated: $fullName is now $newRole",
        'data'    => [
            'oldUserId' => $userId,
            'newUserId' => $newUserId,
            'fullName'  => $fullName,
            'oldRole'   => $oldRole,
            'newRole'   => $newRole,
            'reason'    => $reason,
        ]
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->rollback();
        $conn->close();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
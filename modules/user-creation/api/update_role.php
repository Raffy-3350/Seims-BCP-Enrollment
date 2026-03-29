<?php
error_reporting(0); ini_set('display_errors', 0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

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
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT user_id, role, first_name, last_name, lrn FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new Exception('User not found: ' . $userId);

    $oldRole  = $user['role'];
    $fullName = $user['first_name'] . ' ' . $user['last_name'];
    if ($oldRole === $newRole) throw new Exception('User already has the role: ' . $newRole);

    $newUserId    = generateNextUserID($conn, $newRole, $user['lrn']);
    $detailTables = ROLE_DETAIL_TABLES;

    // 1. Delete old role detail record first (avoids FK conflict)
    if (isset($detailTables[$oldRole])) {
        $conn->prepare("DELETE FROM {$detailTables[$oldRole]} WHERE user_id = ?")->execute([$userId]);
    }

    // 2. Update any other detail tables that reference old user_id
    foreach ($detailTables as $r => $tbl) {
        if ($r === $oldRole) continue;
        $conn->prepare("UPDATE $tbl SET user_id = ? WHERE user_id = ?")->execute([$newUserId, $userId]);
    }

    // 3. Update users table
    $stmt = $conn->prepare("UPDATE users SET user_id = ?, role = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->execute([$newUserId, $newRole, $userId]);
    if ($stmt->rowCount() === 0) throw new Exception('Failed to update user.');

    // 4. Insert new role detail record
    if (isset($detailTables[$newRole])) {
        $tbl = $detailTables[$newRole];
        if ($newRole === 'student') {
            $conn->prepare("INSERT INTO $tbl (user_id, enrollment_status) VALUES (?, 'Regular') ON CONFLICT (user_id) DO NOTHING")->execute([$newUserId]);
        } elseif ($newRole === 'faculty') {
            $conn->prepare("INSERT INTO $tbl (user_id, employment_type) VALUES (?, 'Full-time') ON CONFLICT (user_id) DO NOTHING")->execute([$newUserId]);
        } else {
            $conn->prepare("INSERT INTO $tbl (user_id, employment_type) VALUES (?, 'Permanent') ON CONFLICT (user_id) DO NOTHING")->execute([$newUserId]);
        }
    }

    $conn->commit();

    $logDetails = "Changed role for '$fullName' from '$oldRole' to '$newRole'." . ($reason ? " Reason: $reason" : "");
    logActivity($conn, 'admin@bcp.edu.ph', 'superadmin', 'Role Changed', $logDetails, $fullName, 'Success');

    echo json_encode(['success' => true, 'message' => "Role updated: $fullName is now $newRole",
        'data' => ['oldUserId'=>$userId,'newUserId'=>$newUserId,'fullName'=>$fullName,'oldRole'=>$oldRole,'newRole'=>$newRole,'reason'=>$reason]]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

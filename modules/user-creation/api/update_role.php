<?php
// update_role.php
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
    $conn->beginTransaction();

    // Fetch current user
    $stmt = $conn->prepare("SELECT user_id, role, first_name, last_name, lrn FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) throw new Exception('User not found: ' . $userId);

    $oldRole  = $user['role'];
    $fullName = $user['first_name'] . ' ' . $user['last_name'];
    $lrn      = $user['lrn'];

    if ($oldRole === $newRole) throw new Exception('User already has the role: ' . $newRole);

    $newUserId = generateNextUserID($conn, $newRole, $lrn);

    $detailTables = ROLE_DETAIL_TABLES;

    // Update user_id in all detail tables that have a matching record
    foreach ($detailTables as $table) {
        try {
            $stmt = $conn->prepare("UPDATE {$table} SET user_id = ? WHERE user_id = ?");
            $stmt->execute([$newUserId, $userId]);
        } catch (Exception $e) {
            // Table may not exist for this role — safe to skip
            error_log("update_role: skipped table {$table}: " . $e->getMessage());
        }
    }

    // Update user's user_id and role
    $stmt = $conn->prepare("UPDATE users SET user_id = ?, role = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->execute([$newUserId, $newRole, $userId]);
    if ($stmt->rowCount() === 0) throw new Exception('Failed to update user.');

    // Remove old role detail record
    if (isset($detailTables[$oldRole])) {
        $oldTable = $detailTables[$oldRole];
        try {
            $stmt = $conn->prepare("DELETE FROM {$oldTable} WHERE user_id = ?");
            $stmt->execute([$newUserId]);
        } catch (Exception $e) {
            error_log("update_role: could not delete from {$oldTable}: " . $e->getMessage());
        }
    }

    // Create new role detail record
    // FIX: replaced ON CONFLICT (which requires a UNIQUE constraint) with
    //      a SELECT-then-INSERT pattern to safely avoid duplicate inserts.
    if (isset($detailTables[$newRole])) {
        $newTable = $detailTables[$newRole];
        try {
            // Check if a record already exists for this user_id
            $checkStmt = $conn->prepare("SELECT 1 FROM {$newTable} WHERE user_id = ?");
            $checkStmt->execute([$newUserId]);
            $exists = $checkStmt->fetchColumn();

            if (!$exists) {
                if ($newRole === 'student') {
                    $stmt = $conn->prepare("
                        INSERT INTO {$newTable} (user_id, enrollment_status)
                        VALUES (?, 'Regular')
                    ");
                } elseif ($newRole === 'faculty') {
                    $stmt = $conn->prepare("
                        INSERT INTO {$newTable} (user_id, employment_type)
                        VALUES (?, 'Full-time')
                    ");
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO {$newTable} (user_id, employment_type)
                        VALUES (?, 'Permanent')
                    ");
                }
                $stmt->execute([$newUserId]);
            }
        } catch (Exception $e) {
            throw new Exception('Failed to create detail record: ' . $e->getMessage());
        }
    }

    $conn->commit();

    $adminUser  = 'admin@bcp.edu.ph';
    $adminRole  = 'superadmin';
    $logDetails = "Changed role for '{$fullName}' from '{$oldRole}' to '{$newRole}'."
                . (!empty($reason) ? " Reason: {$reason}" : '');
    logActivity($conn, $adminUser, $adminRole, 'Role Changed', $logDetails, $fullName, 'Success');

    echo json_encode([
        'success' => true,
        'message' => "Role updated: {$fullName} is now {$newRole}",
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
        try { $conn->rollBack(); } catch (Exception $re) {}
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

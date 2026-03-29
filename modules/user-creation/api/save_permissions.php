<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

try {
    require_once __DIR__ . '/config.php';

    $data = json_decode(file_get_contents('php://input'), true);
    $role = $data['role'] ?? null;
    $newPermissions = $data['permissions'] ?? null;

    if (empty($role) || !in_array($role, VALID_ROLES)) {
        throw new Exception('Invalid or missing role.');
    }
    if (!is_array($newPermissions)) {
        throw new Exception('Invalid or missing permissions data.');
    }

    $conn = getDBConnection();
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT permissions FROM role_permissions WHERE role = ?");
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldPermissions = [];
    if ($row = $result->fetch_assoc()) {
        $oldPermissions = json_decode($row['permissions'], true) ?: [];
    }

    $granted = [];
    $revoked = [];
    $allPermissionKeys = array_keys($newPermissions);

    foreach ($allPermissionKeys as $perm) {
        $isNew = $newPermissions[$perm];
        $isOld = $oldPermissions[$perm] ?? false;

        if ($isNew && !$isOld) {
            $granted[] = "'$perm'";
        } elseif (!$isNew && $isOld) {
            $revoked[] = "'$perm'";
        }
    }

    $logDetails = "Updated permissions for the '{$role}' role.";
    $changes = [];
    if (!empty($granted)) {
        $changes[] = "Granted: " . implode(', ', $granted);
    }
    if (!empty($revoked)) {
        $changes[] = "Revoked: " . implode(', ', $revoked);
    }

    if (!empty($changes)) {
        $logDetails = implode('. ', $changes) . ".";
    } else {
        $logDetails = "Reviewed permissions for '{$role}' role. No changes were made.";
    }

    $jsonPermissions = json_encode($newPermissions);

    $stmt = $conn->prepare("
        INSERT INTO role_permissions (role, permissions)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE permissions = VALUES(permissions)
    ");
    $stmt->bind_param('ss', $role, $jsonPermissions);
    if (!$stmt->execute()) {
        $conn->rollback();
        throw new Exception('Failed to save permissions: ' . $stmt->error);
    }

    $adminUser = 'admin@bcp.edu.ph';
    $adminRole = 'superadmin';
    logActivity($conn, $adminUser, $adminRole, 'Permissions Changed', $logDetails, ucfirst($role) . ' Role', 'Success');

    $conn->commit();

    echo json_encode(['success' => true, 'message' => "Permissions for role '{$role}' saved successfully."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
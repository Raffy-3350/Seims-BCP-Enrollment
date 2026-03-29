<?php
// save_permissions.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

try {
    require_once __DIR__ . '/config.php';

    $data           = json_decode(file_get_contents('php://input'), true);
    $role           = $data['role']        ?? null;
    $newPermissions = $data['permissions'] ?? null;

    if (empty($role) || !in_array($role, VALID_ROLES)) {
        throw new Exception('Invalid or missing role.');
    }
    if (!is_array($newPermissions)) {
        throw new Exception('Invalid or missing permissions data.');
    }

    $conn = getDBConnection();
    $conn->beginTransaction();

    // Fetch current permissions for diff/audit log
    $stmt = $conn->prepare("SELECT permissions FROM role_permissions WHERE role = ?");
    $stmt->execute([$role]);
    $row            = $stmt->fetch();
    $oldPermissions = $row ? (json_decode($row['permissions'], true) ?: []) : [];

    // Build audit diff
    $granted = [];
    $revoked = [];
    foreach (array_keys($newPermissions) as $perm) {
        $isNew = $newPermissions[$perm];
        $isOld = $oldPermissions[$perm] ?? false;
        if ($isNew && !$isOld)  $granted[] = "'$perm'";
        if (!$isNew && $isOld)  $revoked[] = "'$perm'";
    }

    if (!empty($granted) || !empty($revoked)) {
        $changes = [];
        if (!empty($granted)) $changes[] = "Granted: "  . implode(', ', $granted);
        if (!empty($revoked)) $changes[] = "Revoked: "  . implode(', ', $revoked);
        $logDetails = implode('. ', $changes) . ".";
    } else {
        $logDetails = "Reviewed permissions for '{$role}' role. No changes were made.";
    }

    $jsonPermissions = json_encode($newPermissions);

    // FIX: replaced MySQL "ON DUPLICATE KEY UPDATE" with a safe manual upsert
    // that works on PostgreSQL regardless of whether a UNIQUE constraint exists.
    $checkStmt = $conn->prepare("SELECT id FROM role_permissions WHERE role = ?");
    $checkStmt->execute([$role]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        $upsert = $conn->prepare("UPDATE role_permissions SET permissions = ? WHERE role = ?");
        $upsert->execute([$jsonPermissions, $role]);
    } else {
        $upsert = $conn->prepare("INSERT INTO role_permissions (role, permissions) VALUES (?, ?)");
        $upsert->execute([$role, $jsonPermissions]);
    }

    $adminUser = 'admin@bcp.edu.ph';
    $adminRole = 'superadmin';
    logActivity($conn, $adminUser, $adminRole, 'Permissions Changed', $logDetails, ucfirst($role) . ' Role', 'Success');

    $conn->commit();

    echo json_encode(['success' => true, 'message' => "Permissions for role '{$role}' saved successfully."]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

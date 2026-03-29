<?php
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

    if (empty($role) || !in_array($role, VALID_ROLES)) throw new Exception('Invalid or missing role.');
    if (!is_array($newPermissions))                    throw new Exception('Invalid permissions data.');

    $conn = getDBConnection();
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT permissions FROM role_permissions WHERE role = ?");
    $stmt->execute([$role]);
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $old  = $row ? (json_decode($row['permissions'], true) ?: []) : [];

    $granted = $revoked = [];
    foreach (array_keys($newPermissions) as $perm) {
        if ($newPermissions[$perm]  && !($old[$perm] ?? false)) $granted[] = "'$perm'";
        if (!$newPermissions[$perm] &&  ($old[$perm] ?? false)) $revoked[] = "'$perm'";
    }
    $changes = [];
    if ($granted) $changes[] = "Granted: " . implode(', ', $granted);
    if ($revoked) $changes[] = "Revoked: " . implode(', ', $revoked);
    $logDetails = $changes
        ? implode(' | ', $changes) . "."
        : "Reviewed permissions for '{$role}' role. No changes were made.";

    // PostgreSQL upsert — requires role to be a unique/PK column in role_permissions
    $stmt = $conn->prepare("
        INSERT INTO role_permissions (role, permissions)
        VALUES (?, ?)
        ON CONFLICT (role) DO UPDATE SET permissions = EXCLUDED.permissions
    ");
    $stmt->execute([$role, json_encode($newPermissions)]);

    logActivity($conn, 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', $logDetails, ucfirst($role) . ' Role', 'Success');

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Permissions for '{$role}' saved."]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

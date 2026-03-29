<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/config.php';

$role = $_GET['role'] ?? '';

if (empty($role) || !in_array($role, VALID_ROLES)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing role.']);
    exit;
}

$conn = getDBConnection();

try {
    $stmt = $conn->prepare("SELECT permissions FROM role_permissions WHERE role = ?");
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $permissions = json_decode($row['permissions'], true);
        echo json_encode(['success' => true, 'permissions' => $permissions]);
    } else {
        echo json_encode(['success' => true, 'permissions' => null]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
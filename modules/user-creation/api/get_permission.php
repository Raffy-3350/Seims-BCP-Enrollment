<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

$role = $_GET['role'] ?? '';
if (empty($role) || !in_array($role, VALID_ROLES)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing role.']);
    exit;
}

$conn = getDBConnection();
try {
    $stmt = $conn->prepare("SELECT permissions FROM role_permissions WHERE role = ?");
    $stmt->execute([$role]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'permissions' => $row ? json_decode($row['permissions'], true) : null]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
/**
 * delete_file.php
 * Deletes a file uploaded for a user
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/config.php';

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid JSON input');
    }

    $file_id = isset($data['file_id']) ? trim($data['file_id']) : null;

    if (!$file_id) {
        throw new Exception('Missing file_id');
    }

    $conn = getDBConnection();

    // Get file info before deleting
    $stmt = $conn->prepare("SELECT file_name FROM user_files WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file_info = $result->fetch_assoc();
    $stmt->close();

    if (!$file_info) {
        throw new Exception('File not found');
    }

    $file_path = realpath(__DIR__ . '/../../..') . '/assets/uploads/' . $file_info['file_name'];

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM user_files WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $file_id);
    if (!$stmt->execute()) {
        throw new Exception('Database delete failed: ' . $stmt->error);
    }
    $stmt->close();

    // Delete physical file
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    echo json_encode([
        'success' => true,
        'message' => 'File deleted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

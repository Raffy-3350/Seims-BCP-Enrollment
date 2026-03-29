<?php
/**
 * download_file.php
 * Serves file downloads for uploaded files
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

try {
    require_once __DIR__ . '/config.php';

    $file_id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if (!$file_id) {
        throw new Exception('Missing file_id parameter');
    }

    $conn = getDBConnection();

    $stmt = $conn->prepare("
        SELECT file_name, original_name, file_type
        FROM user_files
        WHERE id = ?
    ");

    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file_info = $result->fetch_assoc();
    $stmt->close();

    if (!$file_info) {
        http_response_code(404);
        throw new Exception('File not found in database');
    }

    $file_path = realpath(__DIR__ . '/../../..') . '/assets/uploads/' . $file_info['file_name'];

    if (!file_exists($file_path)) {
        http_response_code(404);
        throw new Exception('File not found on disk');
    }

    // Send file
    header('Content-Type: ' . $file_info['file_type']);
    header('Content-Disposition: attachment; filename="' . $file_info['original_name'] . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');

    readfile($file_path);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo 'Error: ' . $e->getMessage();
}
?>

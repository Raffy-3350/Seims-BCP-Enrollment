<?php
/**
 * get_user_files.php
 * Retrieves files uploaded by or associated with a user
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/config.php';

    $user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : null;

    if (!$user_id) {
        throw new Exception('Missing user_id parameter');
    }

    $conn = getDBConnection();

    $stmt = $conn->prepare("
        SELECT id, file_name, original_name, file_size, file_type, uploaded_at
        FROM user_files
        WHERE user_id = ?
        ORDER BY uploaded_at DESC
    ");

    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('s', $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $files = [];

    while ($row = $result->fetch_assoc()) {
        $files[] = [
            'id' => $row['id'],
            'file_name' => $row['file_name'],
            'original_name' => $row['original_name'],
            'file_size' => $row['file_size'],
            'file_size_human' => formatBytes($row['file_size']),
            'file_type' => $row['file_type'],
            'uploaded_at' => $row['uploaded_at']
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'files' => $files,
        'count' => count($files)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

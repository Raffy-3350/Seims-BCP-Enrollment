<?php
/**
 * upload_file.php
 * Handles file uploads for users and stores file metadata in database
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

    // Validate input
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        throw new Exception('Missing user_id');
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = $_FILES['file']['error'] ?? 'Unknown error';
        throw new Exception('File upload error: ' . $errorMsg);
    }

    $user_id = trim($_POST['user_id']);
    $file = $_FILES['file'];

    // Validate file
    $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif'];
    $max_size = 10 * 1024 * 1024; // 10MB

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_ext)) {
        throw new Exception('File type not allowed. Allowed: ' . implode(', ', $allowed_ext));
    }

    if ($file['size'] > $max_size) {
        throw new Exception('File size exceeds 10MB limit');
    }

    // Create uploads directory if not exists
    $uploads_dir = realpath(__DIR__ . '/../../..') . '/assets/uploads';
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
    }

    // Generate unique filename
    $file_base = pathinfo($file['name'], PATHINFO_FILENAME);
    $file_name = $user_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
    $file_path = $uploads_dir . '/' . $file_name;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Save to database
    $conn = getDBConnection();

    $stmt = $conn->prepare("
        INSERT INTO user_files (user_id, file_name, original_name, file_size, file_type, uploaded_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        unlink($file_path);
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $file_size = $file['size'];
    $file_type = $file['type'];

    $stmt->bind_param('sssss', $user_id, $file_name, $file['name'], $file_size, $file_type);

    if (!$stmt->execute()) {
        unlink($file_path);
        throw new Exception('Database insert failed: ' . $stmt->error);
    }

    $file_id = $conn->insert_id;
    $stmt->close();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'file_id' => $file_id,
        'file_name' => $file_name,
        'original_name' => $file['name'],
        'message' => 'File uploaded successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
// update_user.php — Admin: Edit user metadata
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../../integration/api/log_audit.php';

session_start();
$adminName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'System';
$adminRole = $_SESSION['user_role'] ?? $_SESSION['role']     ?? 'admin';

$data = json_decode(file_get_contents('php://input'), true);

$userId = trim($data['user_id'] ?? '');
if (empty($userId)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$firstName     = trim($data['first_name']     ?? '');
$lastName      = trim($data['last_name']      ?? '');
$middleName    = trim($data['middle_name']    ?? '');
$mobileNumber  = trim($data['mobile_number']  ?? '');
$personalEmail = trim($data['personal_email'] ?? '');
$gender        = trim($data['gender']         ?? '');
$birthDate     = trim($data['birth_date']     ?? '');
$streetAddress = trim($data['street_address'] ?? '');
$city          = trim($data['city']           ?? '');
$province      = trim($data['province']       ?? '');
$zipCode       = trim($data['zip_code']       ?? '');
$lifeStatus    = trim($data['life_status']    ?? '');

if (empty($firstName) || empty($lastName)) {
    echo json_encode(['success' => false, 'message' => 'First and Last Name are required.']);
    exit;
}

// Validate life_status values
$allowedStatuses = ['Active', 'Alumni', 'Dropped', 'Terminated'];
if (!empty($lifeStatus) && !in_array($lifeStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

try {
    $conn = getDBConnection();

    $stmt = $conn->prepare("
        UPDATE users SET
            first_name     = ?,
            last_name      = ?,
            middle_name    = ?,
            mobile_number  = ?,
            personal_email = ?,
            gender         = ?,
            birth_date     = ?,
            street_address = ?,
            city           = ?,
            province       = ?,
            zip_code       = ?,
            life_status    = ?,
            updated_at     = NOW()
        WHERE user_id = ?
    ");
    $stmt->bind_param(
        "sssssssssssss",
        $firstName, $lastName, $middleName,
        $mobileNumber, $personalEmail, $gender,
        $birthDate, $streetAddress, $city, $province, $zipCode,
        $lifeStatus,
        $userId
    );
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No changes were made or user not found.']);
        exit;
    }
    $stmt->close();

    $fullName = "$firstName $lastName";
    logAudit(
        $conn,
        $adminName,
        $adminRole,
        'User Info Updated',
        "Admin updated metadata for user {$fullName} (ID: {$userId})." . (!empty($lifeStatus) ? " Status set to: {$lifeStatus}." : ''),
        $fullName,
        'Success'
    );

    echo json_encode(['success' => true, 'message' => 'User information updated successfully.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once __DIR__ . '/config.php';

$adminName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'System';
$adminRole = $_SESSION['user_role'] ?? $_SESSION['role']     ?? 'admin';

$data   = json_decode(file_get_contents('php://input'), true);
$userId = trim($data['user_id'] ?? '');

if (empty($userId)) { echo json_encode(['success'=>false,'message'=>'User ID is required.']); exit; }

$fields = ['first_name','last_name','middle_name','mobile_number','personal_email','gender','birth_date','street_address','city','province','zip_code','life_status'];
foreach ($fields as $f) $$f = trim($data[$f] ?? '');

if (empty($first_name) || empty($last_name)) { echo json_encode(['success'=>false,'message'=>'First and Last Name are required.']); exit; }

$allowed = ['Active','Alumni','Dropped','Terminated'];
if (!empty($life_status) && !in_array($life_status, $allowed)) { echo json_encode(['success'=>false,'message'=>'Invalid status value.']); exit; }

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        UPDATE users SET
            first_name=?, last_name=?, middle_name=?, mobile_number=?,
            personal_email=?, gender=?, birth_date=?, street_address=?,
            city=?, province=?, zip_code=?, life_status=?, updated_at=NOW()
        WHERE user_id=?
    ");
    $stmt->execute([$first_name,$last_name,$middle_name,$mobile_number,$personal_email,$gender,$birth_date,$street_address,$city,$province,$zip_code,$life_status,$userId]);

    if ($stmt->rowCount() === 0) { echo json_encode(['success'=>false,'message'=>'No changes made or user not found.']); exit; }

    logActivity($conn, $adminName, $adminRole, 'User Info Updated',
        "Updated metadata for $first_name $last_name (ID: $userId)." . ($life_status ? " Status: $life_status." : ''),
        "$first_name $last_name", 'Success');

    echo json_encode(['success' => true, 'message' => 'User information updated successfully.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

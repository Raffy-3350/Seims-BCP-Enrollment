<?php
/**
 * register_student.php  (Module 1)
 * Saves student registration to `students` table with status = 'pending'.
 * NO credentials are created here.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Catch ANY PHP fatal/parse error and return it as JSON instead of HTML
set_error_handler(function($errno, $errstr) {
    echo json_encode(['success' => false, 'message' => "PHP Error: $errstr"]);
    exit;
});

// Use main config from user-creation module
require_once __DIR__ . '/../../user-creation/api/config.php';

// Get database connection
$conn = getDBConnection();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { echo json_encode(['success' => false, 'message' => 'Invalid input.']); exit; }

// Required field check
foreach (['firstName','lastName','birthDate','mobileNumber','personalEmail','program','guardianName','guardianContact'] as $f) {
    if (empty(trim($data[$f] ?? ''))) { echo json_encode(['success' => false, 'message' => "Missing: $f"]); exit; }
}

// Registration ID
$year  = date('Y');
$s     = $conn->prepare("SELECT COUNT(*) FROM students WHERE YEAR(created_at) = ?");
$s->bind_param("s", $year);
$s->execute();
$s->bind_result($count);
$s->fetch();
$s->close();
$regId = 'REG-' . $year . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);

// Insert
$stmt = $conn->prepare("
    INSERT INTO students (
        registration_id, first_name, middle_name, last_name,
        birth_date, gender, lrn, mobile_number, personal_email,
        street_address, city, province, zip_code,
        program, year_level, major, enrollment_status,
        guardian_name, guardian_relationship, guardian_contact,
        status, created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW()
    )");

$fn     = trim($data['firstName']);
$mn     = trim($data['middleName'] ?? '');
$ln     = trim($data['lastName']);
$bd     = $data['birthDate'];
$gen    = $data['gender'] ?? 'Male';
$mob    = trim($data['mobileNumber']);
$email  = trim($data['personalEmail']);
$addr   = trim($data['streetAddress'] ?? '');
$city   = trim($data['city'] ?? '');
$prov   = trim($data['province'] ?? '');
$zip    = trim($data['zipCode'] ?? '');
$prog   = $data['program'];
$yr     = $data['yearLevel'] ?? '1st Year';
$major  = trim($data['major'] ?? '');
$estatus = $data['enrollmentStatus'] ?? 'Regular';
$gname  = trim($data['guardianName']);
$grel   = $data['guardianRelationship'] ?? 'Guardian';
$gcont  = trim($data['guardianContact']);

$stmt->bind_param("sssssssssssssssssss", 
    $regId, $fn, $mn, $ln, $bd, $gen, $mob, $email,
    $addr, $city, $prov, $zip, $prog, $yr, $major, $estatus,
    $gname, $grel, $gcont
);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'registrationId' => $regId, 'message' => 'Registration submitted. Status: pending.']);

<?php
/**
 * staff_register.php
 * Saves staff pre-registration to a `staff_pending` table with status = 'pending'.
 * Handles roles: admin, faculty, registrar, cashier, librarian
 * NO credentials are created here — provisioning is handled separately.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

set_error_handler(function($errno, $errstr) {
    echo json_encode(['success' => false, 'message' => "PHP Error: $errstr"]);
    exit;
});

require_once __DIR__ . '/../../user-creation/api/config.php';

$conn = getDBConnection();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { echo json_encode(['success' => false, 'message' => 'Invalid input.']); exit; }

// Validate role
$allowedRoles = ['admin', 'faculty', 'registrar', 'cashier', 'librarian'];
$role = strtolower(trim($data['role'] ?? ''));
if (!in_array($role, $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role specified.']); exit;
}

// Required fields
foreach (['firstName', 'lastName', 'birthDate', 'mobileNumber', 'personalEmail'] as $f) {
    if (empty(trim($data[$f] ?? ''))) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $f"]); exit;
    }
}

// PostgreSQL-compatible CREATE TABLE IF NOT EXISTS
$conn->exec("
    CREATE TABLE IF NOT EXISTS staff_pending (
        id                SERIAL PRIMARY KEY,
        registration_id   VARCHAR(30) NOT NULL UNIQUE,
        role              VARCHAR(20) NOT NULL,
        first_name        VARCHAR(80) NOT NULL,
        middle_name       VARCHAR(80) DEFAULT NULL,
        last_name         VARCHAR(80) NOT NULL,
        birth_date        DATE NOT NULL,
        gender            VARCHAR(10) NOT NULL DEFAULT 'Male',
        mobile_number     VARCHAR(20) DEFAULT NULL,
        personal_email    VARCHAR(120) NOT NULL,
        street_address    VARCHAR(150) DEFAULT NULL,
        city              VARCHAR(80) DEFAULT NULL,
        province          VARCHAR(80) DEFAULT NULL,
        zip_code          VARCHAR(10) DEFAULT NULL,
        department        VARCHAR(100) DEFAULT NULL,
        position          VARCHAR(100) DEFAULT NULL,
        employment_type   VARCHAR(50) DEFAULT 'Permanent',
        specialization    VARCHAR(100) DEFAULT NULL,
        access_level      VARCHAR(30) DEFAULT 'Standard',
        status            VARCHAR(20) NOT NULL DEFAULT 'pending',
        remarks           TEXT DEFAULT NULL,
        created_at        TIMESTAMP NOT NULL DEFAULT NOW(),
        updated_at        TIMESTAMP DEFAULT NULL
    )
");

// FIX: EXTRACT(YEAR FROM ...) instead of MySQL YEAR()
$year   = date('Y');
$prefix = strtoupper(substr($role, 0, 3));
$stmt   = $conn->prepare("SELECT COUNT(*) AS cnt FROM staff_pending WHERE role = ? AND EXTRACT(YEAR FROM created_at) = ?");
$stmt->execute([$role, $year]);
$row    = $stmt->fetch(PDO::FETCH_ASSOC);
$count  = (int) $row['cnt'];
$regId  = 'SREG-' . $prefix . '-' . $year . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);

// Collect fields
$fn          = trim($data['firstName']);
$mn          = trim($data['middleName'] ?? '');
$ln          = trim($data['lastName']);
$bd          = $data['birthDate'];
$gen         = $data['gender'] ?? 'Male';
$mob         = trim($data['mobileNumber']);
$email       = trim($data['personalEmail']);
$addr        = trim($data['streetAddress'] ?? '');
$city        = trim($data['city'] ?? '');
$prov        = trim($data['province'] ?? '');
$zip         = trim($data['zipCode'] ?? '');
$dept        = trim($data['department'] ?? '');
$pos         = trim($data['position'] ?? '');
$spec        = trim($data['specialization'] ?? '');
$empType     = $data['employmentType'] ?? 'Permanent';
$accessLevel = $data['accessLevel'] ?? 'Standard';

$stmt = $conn->prepare("
    INSERT INTO staff_pending (
        registration_id, role, first_name, middle_name, last_name,
        birth_date, gender, mobile_number, personal_email,
        street_address, city, province, zip_code,
        department, position, specialization, employment_type, access_level,
        status, created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW()
    )
");

$result = $stmt->execute([
    $regId, $role, $fn, $mn, $ln,
    $bd, $gen, $mob, $email,
    $addr, $city, $prov, $zip,
    $dept, $pos, $spec, $empType, $accessLevel
]);

if ($result) {
    echo json_encode([
        'success'        => true,
        'registrationId' => $regId,
        'role'           => $role,
        'message'        => 'Staff registration submitted. Status: pending review.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error during insert.']);
}

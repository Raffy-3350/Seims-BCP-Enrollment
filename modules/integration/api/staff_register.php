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

// Required fields for all staff
foreach (['firstName', 'lastName', 'birthDate', 'mobileNumber', 'personalEmail'] as $f) {
    if (empty(trim($data[$f] ?? ''))) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $f"]); exit;
    }
}

// Create the staff_pending table if it doesn't exist yet
$conn->query("
    CREATE TABLE IF NOT EXISTS `staff_pending` (
        `id`                int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `registration_id`   varchar(30) NOT NULL,
        `role`              enum('admin','faculty','registrar','cashier','librarian') NOT NULL,
        `first_name`        varchar(80) NOT NULL,
        `middle_name`       varchar(80) DEFAULT NULL,
        `last_name`         varchar(80) NOT NULL,
        `birth_date`        date NOT NULL,
        `gender`            enum('Male','Female') NOT NULL DEFAULT 'Male',
        `mobile_number`     varchar(20) DEFAULT NULL,
        `personal_email`    varchar(120) NOT NULL,
        `street_address`    varchar(150) DEFAULT NULL,
        `city`              varchar(80) DEFAULT NULL,
        `province`          varchar(80) DEFAULT NULL,
        `zip_code`          varchar(10) DEFAULT NULL,
        `department`        varchar(100) DEFAULT NULL,
        `position`          varchar(100) DEFAULT NULL,
        `employment_type`   varchar(50) DEFAULT 'Permanent',
        `specialization`    varchar(100) DEFAULT NULL,
        `access_level`      varchar(30) DEFAULT 'Standard',
        `status`            enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `remarks`           text DEFAULT NULL,
        `created_at`        datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`        datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `registration_id` (`registration_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Generate Registration ID  e.g. SREG-FAC-2026-00001
$year   = date('Y');
$prefix = strtoupper(substr($role, 0, 3));   // FAC, ADM, REG, CSH, LIB
$s = $conn->prepare("SELECT COUNT(*) FROM staff_pending WHERE role = ? AND YEAR(created_at) = ?");
$s->bind_param("ss", $role, $year);
$s->execute();
$s->bind_result($count);
$s->fetch();
$s->close();
$regId = 'SREG-' . $prefix . '-' . $year . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);

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
// FIX: position and specialization were never collected — now they are
$pos         = trim($data['position'] ?? '');
$spec        = trim($data['specialization'] ?? '');
$empType     = $data['employmentType'] ?? 'Permanent';
$accessLevel = $data['accessLevel'] ?? 'Standard';

// FIX: INSERT now includes position and specialization columns
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

// FIX: 18 variables, 18 "s" characters
$stmt->bind_param(
    "ssssssssssssssssss",
    $regId, $role, $fn, $mn, $ln,
    $bd, $gen, $mob, $email,
    $addr, $city, $prov, $zip,
    $dept, $pos, $spec, $empType, $accessLevel
);

if ($stmt->execute()) {
    echo json_encode([
        'success'        => true,
        'registrationId' => $regId,
        'role'           => $role,
        'message'        => 'Staff registration submitted. Status: pending review.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}
$stmt->close();

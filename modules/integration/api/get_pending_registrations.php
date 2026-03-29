<?php
// get_pending_staff_registrations.php
// Returns staff records with status = 'pending' for the staff approval tab.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    $conn = getDBConnection();

    $stmt = $conn->query("
        SELECT
            sp.id               AS staff_id,
            sp.registration_id  AS reference_number,
            sp.role,
            sp.first_name,
            sp.middle_name,
            sp.last_name,
            sp.personal_email,
            sp.mobile_number,
            sp.department,
            sp.position,
            sp.employment_type,
            sp.specialization,
            sp.access_level,
            sp.gender,
            sp.birth_date,
            sp.street_address,
            sp.city,
            sp.province,
            sp.zip_code,
            sp.status           AS registration_status,
            sp.created_at       AS registered_at
        FROM staff_pending sp
        WHERE sp.status = 'pending'
        ORDER BY sp.created_at ASC
    ");

    $staff = [];
    while ($row = $stmt->fetch_assoc()) {
        $staff[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'staff'   => $staff,
        'count'   => count($staff),
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

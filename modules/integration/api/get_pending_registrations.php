<?php
// get_pending_registrations.php
// Returns students with status = 'pending' for the approval tab.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    // Fixed: use getDBConnection() consistently, not the raw $mysqli variable
    $conn = getDBConnection();

    $stmt = $conn->query("
        SELECT
            s.id              AS student_id,
            s.first_name,
            s.middle_name,
            s.last_name,
            s.personal_email,
            s.mobile_number,
            s.program,
            s.year_level,
            s.status          AS registration_status,
            s.registration_id AS reference_number,
            s.created_at      AS registered_at
        FROM students s
        WHERE s.status = 'pending'
        ORDER BY s.created_at ASC
    ");

    $students = [];
    while ($row = $stmt->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success'  => true,
        'students' => $students,
        'count'    => count($students),
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

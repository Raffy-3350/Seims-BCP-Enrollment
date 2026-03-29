<?php
// get_approved_students.php
// Returns students with status = 'approved' and no existing account yet.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/config.php';

try {
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
        LEFT JOIN users u
            ON u.role = 'student'
            AND LOWER(u.personal_email) = LOWER(s.personal_email)
        WHERE s.status = 'approved'
          AND u.id IS NULL
        ORDER BY s.created_at ASC
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'students' => $students,
        'count'    => count($students),
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

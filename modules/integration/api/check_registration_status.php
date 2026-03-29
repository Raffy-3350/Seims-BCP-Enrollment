<?php
// check_registration_status.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    $conn = getDBConnection();

    // Only show approved students without an account yet
    $stmt = $conn->query("
        SELECT
            s.id                AS student_id,
            s.first_name,
            s.middle_name,
            s.last_name,
            s.personal_email,
            s.mobile_number,
            s.program,
            s.year_level,
            s.status            AS registration_status,
            s.registration_id   AS reference_number,
            s.created_at        AS registered_at,
            CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END AS has_account
        FROM students s
        LEFT JOIN users u
            ON u.role = 'student'
           AND u.personal_email COLLATE utf8mb4_unicode_ci = s.personal_email COLLATE utf8mb4_unicode_ci
        WHERE s.status = 'approved'
          AND u.id IS NULL
        ORDER BY s.created_at DESC
    ");

    $students = [];
    while ($row = $stmt->fetch_assoc()) {
        $row['has_account'] = false;
        $students[] = $row;
    }

    // Stats
    $statsStmt = $conn->query("
        SELECT
            (SELECT COUNT(*) FROM students s
             LEFT JOIN users u
                ON u.role = 'student'
               AND u.personal_email COLLATE utf8mb4_unicode_ci = s.personal_email COLLATE utf8mb4_unicode_ci
             WHERE s.status = 'approved' AND u.id IS NULL)                          AS pending,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE())         AS today,
            (SELECT COUNT(*) FROM students)                                          AS total
    ");
    $stats = $statsStmt->fetch_assoc();

    echo json_encode([
        'success'  => true,
        'students' => $students,
        'count'    => count($students),
        'stats'    => [
            'pending' => (int) $stats['pending'],
            'today'   => (int) $stats['today'],
            'total'   => (int) $stats['total'],
        ],
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

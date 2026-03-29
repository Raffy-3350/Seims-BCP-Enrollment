<?php
// check_registration_status.php
// Returns approved students that do not yet have an account.
// Feeds the student Provision tab.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    $conn = getDBConnection();

    // FIX: removed MySQL-only COLLATE utf8mb4_unicode_ci — not valid in PostgreSQL
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
            CASE WHEN u.id IS NOT NULL THEN true ELSE false END AS has_account
        FROM students s
        LEFT JOIN users u
            ON u.role = 'student'
           AND LOWER(u.personal_email) = LOWER(s.personal_email)
        WHERE s.status = 'approved'
          AND u.id IS NULL
        ORDER BY s.created_at DESC
    ");

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $statsStmt = $conn->query("
        SELECT
            (SELECT COUNT(*)
             FROM students s
             LEFT JOIN users u
                ON u.role = 'student'
               AND LOWER(u.personal_email) = LOWER(s.personal_email)
             WHERE s.status = 'approved'
               AND u.id IS NULL)                                        AS pending,
            (SELECT COUNT(*)
             FROM users
             WHERE DATE(created_at) = CURRENT_DATE)                    AS today,
            (SELECT COUNT(*) FROM students)                             AS total
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

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

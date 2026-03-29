<?php
// check_staff_registration_status.php
// Returns approved staff records that do not yet have a users account.
// Feeds the staff Provision tab.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    $conn = getDBConnection();

    // FIX: removed MySQL-only COLLATE utf8mb4_unicode_ci — not valid in PostgreSQL
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
            sp.created_at       AS registered_at,
            CASE WHEN u.id IS NOT NULL THEN true ELSE false END AS has_account
        FROM staff_pending sp
        LEFT JOIN users u
            ON u.role = sp.role
           AND LOWER(u.personal_email) = LOWER(sp.personal_email)
        WHERE sp.status = 'approved'
          AND u.id IS NULL
        ORDER BY sp.created_at DESC
    ");

    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $statsStmt = $conn->query("
        SELECT
            (SELECT COUNT(*)
             FROM staff_pending sp
             LEFT JOIN users u
                ON u.role = sp.role
               AND LOWER(u.personal_email) = LOWER(sp.personal_email)
             WHERE sp.status = 'approved'
               AND u.id IS NULL)                                        AS pending,
            (SELECT COUNT(*)
             FROM users
             WHERE role != 'student'
               AND DATE(created_at) = CURRENT_DATE)                    AS today,
            (SELECT COUNT(*) FROM staff_pending)                        AS total
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'staff'   => $staff,
        'count'   => count($staff),
        'stats'   => [
            'pending' => (int) $stats['pending'],
            'today'   => (int) $stats['today'],
            'total'   => (int) $stats['total'],
        ],
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

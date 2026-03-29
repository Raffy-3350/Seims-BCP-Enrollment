<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

$conn = getDBConnection();

try {
    $role   = $_GET['role']   ?? 'all';
    $search = $_GET['search'] ?? '';

    if ($role !== 'all' && !in_array($role, VALID_ROLES))
        throw new Exception('Invalid role filter: ' . $role);

    $sql = "SELECT
        u.id, u.user_id, u.role, u.first_name, u.middle_name, u.last_name,
        u.birth_date, u.gender, u.lrn, u.mobile_number, u.personal_email,
        u.institutional_email, u.street_address, u.barangay, u.city,
        u.province, u.zip_code, u.created_at, u.updated_at, u.life_status,
        CASE WHEN u.role = 'student' THEN sd.program     ELSE u.department_program END AS department_program,
        CASE WHEN u.role = 'student' THEN sd.year_level  ELSE u.year_position      END AS year_position,
        sd.major, sd.enrollment_status, sd.guardian_name, sd.guardian_contact, sd.guardian_relationship,
        fd.specialization, u.employment_type, ad.access_level
    FROM users u
    LEFT JOIN student_details    sd  ON u.user_id = sd.user_id
    LEFT JOIN faculty_details    fd  ON u.user_id = fd.user_id
    LEFT JOIN admin_details      ad  ON u.user_id = ad.user_id
    LEFT JOIN superadmin_details sad ON u.user_id = sad.user_id
    LEFT JOIN registrar_details  rd  ON u.user_id = rd.user_id
    LEFT JOIN cashier_details    cd  ON u.user_id = cd.user_id
    LEFT JOIN librarian_details  ld  ON u.user_id = ld.user_id
    WHERE 1=1";

    $params = [];
    if ($role !== 'all')   { $sql .= " AND u.role = ?"; $params[] = $role; }
    if (!empty($search)) {
        $sql .= " AND (u.first_name ILIKE ? OR u.last_name ILIKE ? OR u.user_id ILIKE ? OR u.institutional_email ILIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like, $like);
    }
    $sql .= " ORDER BY u.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cStmt = $conn->prepare("SELECT role, COUNT(*) AS count FROM users GROUP BY role");
    $cStmt->execute();
    $counts = array_fill_keys(VALID_ROLES, 0);
    foreach ($cStmt->fetchAll() as $r) $counts[$r['role']] = (int)$r['count'];

    echo json_encode(['success' => true, 'users' => $users, 'total' => count($users), 'counts' => $counts]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

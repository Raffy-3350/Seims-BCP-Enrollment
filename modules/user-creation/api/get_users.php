<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/config.php';

$conn = getDBConnection();

try {
    $role   = $_GET['role']   ?? 'all';
    $search = $_GET['search'] ?? '';

    if ($role !== 'all' && !in_array($role, VALID_ROLES)) {
        throw new Exception('Invalid role filter: ' . $role);
    }

    $sql = "SELECT
        u.id,
        u.user_id,
        u.role,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.birth_date,
        u.gender,
        u.lrn,
        u.mobile_number,
        u.personal_email,
        u.institutional_email,
        u.street_address,
        u.barangay,
        u.city,
        u.province,
        u.zip_code,
        u.created_at,
        u.updated_at,
        u.life_status,
        -- department/program unified column
        CASE
            WHEN u.role = 'student'    THEN sd.program
            ELSE u.department_program
        END AS department_program,
        -- year level / position unified column
           CASE
            WHEN u.role = 'student' THEN sd.year_level
            ELSE u.year_position
        END AS year_position,
        -- student-only fields
        sd.major,
        sd.enrollment_status,
        sd.guardian_name,
        sd.guardian_contact,
        sd.guardian_relationship,
        -- faculty-only fields
        fd.specialization,
        u.employment_type,
        -- admin-only field
        ad.access_level
    FROM users u
    LEFT JOIN student_details    sd  ON u.user_id = sd.user_id
    LEFT JOIN faculty_details    fd  ON u.user_id = fd.user_id
    LEFT JOIN admin_details      ad  ON u.user_id = ad.user_id
    LEFT JOIN superadmin_details sad ON u.user_id = sad.user_id
    LEFT JOIN registrar_details  rd  ON u.user_id = rd.user_id
    LEFT JOIN cashier_details    cd  ON u.user_id = cd.user_id
    LEFT JOIN librarian_details  ld  ON u.user_id = ld.user_id
    WHERE 1=1";

    $params      = [];
    $paramTypes  = '';

    if ($role !== 'all') {
        $sql        .= " AND u.role = ?";
        $params[]    = $role;
        $paramTypes .= 's';
    }

    if (!empty($search)) {
        $sql        .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.user_id LIKE ? OR u.institutional_email LIKE ?)";
        $like        = "%$search%";
        $params[]    = $like;
        $params[]    = $like;
        $params[]    = $like;
        $params[]    = $like;
        $paramTypes .= 'ssss';
    }

    $sql .= " ORDER BY u.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($paramTypes, ...$params);
    }
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $countStmt = $conn->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $countStmt->execute();
    $countRows = $countStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $counts = array_fill_keys(VALID_ROLES, 0);
    foreach ($countRows as $row) {
        $counts[$row['role']] = (int)$row['count'];
    }

    echo json_encode([
        'success' => true,
        'users'   => $users,
        'total'   => count($users),
        'counts'  => $counts
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
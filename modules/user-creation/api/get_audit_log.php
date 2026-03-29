<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

$conn = getDBConnection();

try {
    $filter  = $_GET['filter']   ?? 'All Events';
    $page    = max(1, (int)($_GET['page']     ?? 1));
    $perPage = max(1, (int)($_GET['per_page'] ?? 10));
    $offset  = ($page - 1) * $perPage;

    $where = match($filter) {
        'Login Events'       => " WHERE event_type ILIKE '%Login%'",
        'Password Reset'     => " WHERE event_type ILIKE '%Password Reset%'",
        'Role Changes'       => " WHERE event_type ILIKE '%Role%'",
        'Permission Changes' => " WHERE event_type ILIKE '%Permission%'",
        'User Creation'      => " WHERE event_type ILIKE '%Created%' OR event_type ILIKE '%Creation%'",
        'User Deletion'      => " WHERE event_type ILIKE '%Deleted%' OR event_type ILIKE '%Deletion%'",
        'Failed Access'      => " WHERE status = 'Blocked' OR status = 'Failed'",
        default              => '',
    };

    $total = (int)$conn->query("SELECT COUNT(*) FROM audit_log" . $where)->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));

    $sql = "SELECT
                id,
                timestamp         AS ts,
                performed_by      AS \"by\",
                performed_by_role AS \"byRole\",
                event_type        AS event,
                details           AS detail,
                affected_entity   AS affected,
                status
            FROM audit_log"
            . $where
            . " ORDER BY timestamp DESC"
            . " LIMIT " . $perPage . " OFFSET " . $offset;

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'     => true,
        'logs'        => $logs,
        'total'       => $total,
        'total_pages' => $totalPages,
        'page'        => $page,
        'per_page'    => $perPage,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

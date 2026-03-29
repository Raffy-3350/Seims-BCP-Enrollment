<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/config.php';

$conn = getDBConnection();

try {
    $filter = $_GET['filter'] ?? 'All Events';

    $sql = "SELECT 
                id, 
                timestamp as ts, 
                performed_by as `by`, 
                performed_by_role as byRole, 
                event_type as event, 
                details as detail, 
                affected_entity as affected, 
                status 
            FROM audit_log";

    $whereClause = '';
    switch ($filter) {
        case 'Login Events':
            $whereClause = " WHERE event_type LIKE '%Login%'";
            break;
        case 'Password Reset':
            $whereClause = " WHERE event_type LIKE '%Password Reset%'";
            break;
        case 'Role Changes':
            $whereClause = " WHERE event_type LIKE '%Role%'";
            break;
        case 'Permission Changes':
            $whereClause = " WHERE event_type LIKE '%Permission%'";
            break;
        case 'User Creation':
            $whereClause = " WHERE event_type LIKE '%Created%' OR event_type LIKE '%Creation%'";
            break;
        case 'User Deletion':
            $whereClause = " WHERE event_type LIKE '%Deleted%' OR event_type LIKE '%Deletion%'";
            break;
        case 'Failed Access':
            $whereClause = " WHERE status = 'Blocked' OR status = 'Failed'";
            break;
    }
    $sql .= $whereClause;
    $sql .= " ORDER BY timestamp DESC LIMIT 100";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'logs' => $logs]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
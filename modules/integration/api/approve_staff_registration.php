<?php
// approve_staff_registration.php
// Approves or rejects a pending staff registration.
// POST body: { staff_id: int, action: 'approve'|'reject', remarks: string (optional) }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/log_audit.php';

session_start();
$adminName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'System';
$adminRole = $_SESSION['user_role'] ?? $_SESSION['role']     ?? 'admin';

$input   = json_decode(file_get_contents('php://input'), true);
$staffId = intval($input['staff_id'] ?? 0);
$action  = $input['action']  ?? '';
$remarks = trim($input['remarks'] ?? '');

if (!$staffId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

try {
    $conn = getDBConnection();

    // Confirm staff record exists and is pending
    $check = $conn->prepare("
        SELECT id, status, first_name, middle_name, last_name, role, department, position
        FROM staff_pending
        WHERE id = ?
    ");
    $check->bind_param("i", $staffId);
    $check->execute();
    $staff = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$staff) {
        echo json_encode(['success' => false, 'message' => 'Staff record not found.']);
        exit;
    }
    if ($staff['status'] !== 'pending') {
        echo json_encode([
            'success' => false,
            'message' => 'Staff record is not in pending status (current: ' . $staff['status'] . ').',
        ]);
        exit;
    }

    // Build full name for audit log
    $fullName = trim(
        $staff['first_name'] . ' ' .
        ($staff['middle_name'] ? $staff['middle_name'] . ' ' : '') .
        $staff['last_name']
    );

    $newStatus    = $action === 'approve' ? 'approved' : 'rejected';
    $finalRemarks = $remarks ?: ($action === 'approve' ? 'Approved by admin.' : 'Rejected by admin.');

    $stmt = $conn->prepare("
        UPDATE staff_pending
        SET status     = ?,
            remarks    = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $newStatus, $finalRemarks, $staffId);
    $stmt->execute();
    $stmt->close();

    // Audit log
    $roleLabel = ucfirst($staff['role']);
    if ($action === 'approve') {
        logAudit(
            $conn,
            $adminName,
            $adminRole,
            'Staff Registration Approved',
            "Registration for {$fullName} (Role: {$roleLabel}, "
            . "Department: {$staff['department']}, Position: {$staff['position']}) "
            . "was approved and added to the provisioning queue. "
            . "Remarks: {$finalRemarks}",
            $fullName,
            'Success'
        );
    } else {
        logAudit(
            $conn,
            $adminName,
            $adminRole,
            'Staff Registration Rejected',
            "Registration for {$fullName} (Role: {$roleLabel}, "
            . "Department: {$staff['department']}, Position: {$staff['position']}) "
            . "was rejected. Remarks: {$finalRemarks}",
            $fullName,
            'Failed'
        );
    }

    echo json_encode([
        'success' => true,
        'status'  => $newStatus,
        'message' => 'Staff registration ' . $newStatus . ' successfully.',
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
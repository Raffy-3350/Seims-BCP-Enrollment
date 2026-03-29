<?php
// approve_registration.php
// Approves or rejects a pending student registration.
// POST body: { student_id: int, action: 'approve'|'reject', remarks: string (optional) }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/log_audit.php';

session_start();
$adminName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'System';
$adminRole = $_SESSION['user_role'] ?? $_SESSION['role']     ?? 'admin';

$input     = json_decode(file_get_contents('php://input'), true);
$studentId = intval($input['student_id'] ?? 0);
$action    = $input['action']   ?? '';
$remarks   = trim($input['remarks'] ?? '');

if (!$studentId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

try {
    $conn = getDBConnection();

    // Confirm student exists and is pending
    $check = $conn->prepare("
        SELECT id, status, first_name, middle_name, last_name, program, year_level
        FROM students WHERE id = ?
    ");
    $check->execute([$studentId]);
    $student = $check->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit;
    }
    if ($student['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Student is not in pending status (current: ' . $student['status'] . ').']);
        exit;
    }

    $fullName = trim(
        $student['first_name'] . ' ' .
        ($student['middle_name'] ? $student['middle_name'] . ' ' : '') .
        $student['last_name']
    );

    $newStatus    = $action === 'approve' ? 'approved' : 'rejected';
    $finalRemarks = $remarks ?: ($action === 'approve' ? 'Approved by admin.' : 'Rejected by admin.');

    $stmt = $conn->prepare("
        UPDATE students
        SET status      = ?,
            remarks     = ?,
            approved_at = NOW(),
            updated_at  = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $finalRemarks, $studentId]);

    // Audit log
    if ($action === 'approve') {
        logAudit(
            $conn, $adminName, $adminRole,
            'Student Approved',
            "Registration for {$fullName} (Program: {$student['program']}, Year: {$student['year_level']}) was approved. Remarks: {$finalRemarks}",
            $fullName, 'Success'
        );
    } else {
        logAudit(
            $conn, $adminName, $adminRole,
            'Student Registration Rejected',
            "Registration for {$fullName} (Program: {$student['program']}, Year: {$student['year_level']}) was rejected. Remarks: {$finalRemarks}",
            $fullName, 'Failed'
        );
    }

    echo json_encode([
        'success' => true,
        'status'  => $newStatus,
        'message' => 'Student ' . $newStatus . ' successfully.',
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/config.php';

$role = $_GET['role'] ?? 'student';
$lrn  = $_GET['lrn']  ?? null;

if (!in_array($role, VALID_ROLES)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role: ' . $role]);
    exit;
}

$conn = getDBConnection();

try {
    $year     = date('Y');
    $prefixes = ROLE_PREFIXES;

    if ($role === 'student' && !empty($lrn)) {
        $cleanLrn = preg_replace('/\D/', '', $lrn);
        if (strlen($cleanLrn) >= 4) {
            $last4  = substr($cleanLrn, -6);
            $nextId = "BCP-$year-$last4";
            echo json_encode(['success' => true, 'nextId' => $nextId, 'basedOn' => 'lrn']);
        } else {
            echo json_encode(['success' => true, 'nextId' => "BCP-$year-######", 'basedOn' => 'placeholder']);
        }
        exit;
    }

    if ($role === 'student') {
        echo json_encode(['success' => true, 'nextId' => "BCP-$year-######", 'basedOn' => 'placeholder']);
        exit;
    }

    $prefix = $prefixes[$role] ?? 'BCP-STF';
    $rand4  = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $nextId = "$prefix-$rand4";

    echo json_encode(['success' => true, 'nextId' => $nextId, 'basedOn' => 'random']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
<?php
session_start();
require_once __DIR__ . '/../app/config/db.php'; // Now includes autoloader
require_once __DIR__ . '/../app/config/mailer.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: login.php");
    exit;
}

$identifier = trim($_POST["identifier"] ?? '');
$password   = $_POST["password"]        ?? '';

function logActivity($conn, $performedBy, $performedByRole, $eventType, $details, $affectedEntity, $status) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO audit_log (performed_by, performed_by_role, event_type, details, affected_entity, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$performedBy, $performedByRole, $eventType, $details, $affectedEntity, $status]);
    } catch (Throwable $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

if (empty($identifier) || empty($password)) {
    header("location: login.php?error=" . urlencode("Please fill in all fields."));
    exit;
}

try {
    // PDO syntax for selecting user
    $stmt = $mysqli->prepare("
        SELECT * FROM users 
        WHERE LOWER(user_id) = LOWER(?) 
           OR LOWER(institutional_email) = LOWER(?) 
        LIMIT 1
    ");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') {
            header("location: login.php?error=" . urlencode("Account is inactive."));
            exit;
        }

        // Generate OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $stmt = $mysqli->prepare("UPDATE users SET otp_code = ?, otp_expiry = NOW() + INTERVAL '10 minutes' WHERE id = ?");
        $stmt->execute([$otp, $user['id']]);

        // Setup Session for OTP
        $_SESSION['otp_pending_id'] = $user['id'];
        $_SESSION['otp_pending_role'] = $user['role'];
        
        // Use PHPMailer (from mailer.php)
        $subject = "Your Verification Code";
        $body = "<h2>Your OTP is: $otp</h2><p>Expires in 10 minutes.</p>";
        
        if (sendEmail($user['institutional_email'], $subject, $body)) {
            logActivity($mysqli, $identifier, $user['role'], 'Login', 'OTP Sent', $identifier, 'Success');
            header("location: verify_otp.php");
        } else {
            header("location: login.php?error=" . urlencode("Failed to send email."));
        }
        exit;
    } else {
        header("location: login.php?error=" . urlencode("Invalid credentials."));
        exit;
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

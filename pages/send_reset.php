<?php
// 1. Load the Autoloader (Required for PHPMailer & PostgreSQL)
require_once __DIR__ . '/../vendor/autoload.php'; 
require_once __DIR__ . '/../app/config/db.php'; // This provides $mysqli as a PDO object
require_once __DIR__ . '/../app/config/mailer.php';

function logPasswordResetEvent($conn, $performedBy, $role, $eventType, $details, $affectedEntity, $status) {
    try {
        // PDO Syntax: Use named placeholders or ?
        $stmt = $conn->prepare("
            INSERT INTO audit_log (performed_by, performed_by_role, event_type, details, affected_entity, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$performedBy, $role, $eventType, $details, $affectedEntity, $status]);
    } catch (Throwable $e) {
        error_log("Audit log error (send_reset.php): " . $e->getMessage());
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: login.php");
    exit;
}

$email = trim($_POST["email"] ?? "");

if (empty($email)) {
    logPasswordResetEvent($mysqli, 'anonymous', 'guest', 'Password Reset Request Failed', 'No email submitted.', 'Unknown', 'Failed');
    header("location: login.php?reset_error=" . urlencode("Please enter your email address."));
    exit;
}

try {
    // PDO Syntax: LOWER(TRIM()) is fine, but execute is different
    $stmt = $mysqli->prepare("
        SELECT id, role, manually_changed_password FROM users
        WHERE LOWER(TRIM(institutional_email)) = LOWER(?) 
           OR LOWER(TRIM(personal_email)) = LOWER(?)
        LIMIT 1
    ");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch(); // PDO fetch() replaces fetch_assoc()

    if (!$user) {
        logPasswordResetEvent($mysqli, 'anonymous', 'guest', 'Password Reset Request Failed', "Email not found: $email", $email, 'Failed');
        header("location: login.php?reset_error=" . urlencode("No account found."));
        exit;
    }

    if ($user['role'] !== 'superadmin' && !empty($user['manually_changed_password'])) {
        header("location: login.php?reset_error=" . urlencode("Contact admin to reset password."));
        exit;
    }

    // Generate token
    $token = bin2hex(random_bytes(16));

    // 2. POSTGRESQL DATE SYNTAX: Use INTERVAL '30 minutes'
    $updateStmt = $mysqli->prepare("
        UPDATE users
        SET reset_token_hash = ?,
            reset_token_expires_at = NOW() + INTERVAL '30 minutes'
        WHERE id = ?
    ");
    $updateStmt->execute([$token, $user['id']]);

    // Build reset link
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $resetLink = "{$protocol}://{$host}/pages/login.php?reset_token=" . urlencode($token);

    $subject = "Reset Your SIEMS Password";
    $body = "<p>Click here to reset: <a href='{$resetLink}'>Reset Password</a></p>";

    if (!sendEmail($email, $subject, $body)) {
        throw new Exception("Mailer failed");
    }

    logPasswordResetEvent($mysqli, $email, 'user', 'Password Reset Requested', "Link sent.", $email, 'Success');
    header("location: login.php?reset_sent=1");
    exit;

} catch (Throwable $e) {
    error_log("Reset Error: " . $e->getMessage());
    header("location: login.php?reset_error=" . urlencode("System error. Check logs."));
    exit;
}

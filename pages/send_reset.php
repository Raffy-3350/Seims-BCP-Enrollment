<?php
require_once __DIR__ . '/../app/config/db.php'; // $mysqli
require_once __DIR__ . '/../app/config/mailer.php';

function logPasswordResetEvent($conn, $performedBy, $role, $eventType, $details, $affectedEntity, $status) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO audit_log (performed_by, performed_by_role, event_type, details, affected_entity, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param("ssssss", $performedBy, $role, $eventType, $details, $affectedEntity, $status);
            $stmt->execute();
            $stmt->close();
        }
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
    logPasswordResetEvent(
        $mysqli,
        'anonymous',
        'guest',
        'Password Reset Request Failed',
        'Password reset request was submitted without an email.',
        'Unknown Account',
        'Failed'
    );
    header("location: login.php?reset_error=" . urlencode("Please enter your email address."));
    exit;
}

try {
    // Match institutional or personal email
    $stmt = $mysqli->prepare("
        SELECT id, role, manually_changed_password FROM users
        WHERE LOWER(TRIM(institutional_email)) = LOWER(?) 
           OR LOWER(TRIM(personal_email)) = LOWER(?)
        LIMIT 1
    ");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        logPasswordResetEvent(
            $mysqli,
            'anonymous',
            'guest',
            'Password Reset Request Failed',
            "Password reset requested for non-existent email: {$email}.",
            $email,
            'Failed'
        );
        header("location: login.php?reset_error=" . urlencode("No account found for that email."));
        exit;
    }

    // Block non-superadmin users who have already manually changed their password
    if ($user['role'] !== 'superadmin' && !empty($user['manually_changed_password'])) {
        logPasswordResetEvent(
            $mysqli,
            $email,
            $user['role'],
            'Password Reset Blocked',
            "Password reset blocked for {$email} — password already changed once. Must contact administration.",
            $email,
            'Failed'
        );
        header("location: login.php?reset_error=" . urlencode("You have already changed your password once. Please contact the administration to reset it."));
        exit;
    }

    // Generate token
    $token  = bin2hex(random_bytes(16));

    // Use DB time for expiry to avoid PHP/DB timezone drift issues
    $updateStmt = $mysqli->prepare("
        UPDATE users
        SET reset_token_hash = ?,
            reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
        WHERE id = ?
    ");
    $updateStmt->bind_param("si", $token, $user['id']);
    $updateStmt->execute();
    $updateStmt->close();

    // Build reset link that opens reset modal on login page.
    $protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host        = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $currentDir  = dirname($_SERVER['PHP_SELF']); // .../pages
    $loginPath   = $currentDir . '/login.php';
    $resetLink   = "{$protocol}://{$host}{$loginPath}?reset_token=" . urlencode($token);

    $subject = "Reset Your SIEMS Password";
    $body    = "
        <p>Hello,</p>
        <p>We received a request to reset your SIEMS password.</p>
        <p><a href=\"{$resetLink}\" style=\"display:inline-block;padding:10px 14px;background:#1535a0;color:#fff;text-decoration:none;border-radius:6px;\">Reset Password</a></p>
        <p>This link expires in 30 minutes.</p>
        <p>If you did not request this, you can ignore this email.</p>
    ";

    if (!sendEmail($email, $subject, $body)) {
        logPasswordResetEvent(
            $mysqli,
            $email,
            'user',
            'Password Reset Request Failed',
            "Password reset email could not be sent to {$email}.",
            $email,
            'Failed'
        );
        header("location: login.php?reset_error=" . urlencode("Failed to send reset email. Please try again."));
        exit;
    }

    logPasswordResetEvent(
        $mysqli,
        $email,
        'user',
        'Password Reset Requested',
        "Password reset link sent to {$email}.",
        $email,
        'Success'
    );
    header("location: login.php?reset_sent=1");
    exit;

} catch (Throwable $e) {
    logPasswordResetEvent(
        $mysqli,
        $email ?: 'anonymous',
        'guest',
        'Password Reset Request Failed',
        "System error while processing password reset request for {$email}: " . $e->getMessage(),
        $email ?: 'Unknown Account',
        'Failed'
    );
    header("location: login.php?reset_error=" . urlencode("A system error occurred. Please try again."));
    exit;
}
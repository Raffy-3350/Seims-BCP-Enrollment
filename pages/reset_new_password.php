<?php
require_once __DIR__ . '/../app/config/db.php';

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
        error_log("Audit log error (reset_new_password.php): " . $e->getMessage());
    }
}

// GET: bounce to login modal
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["token"])) {
    header("Location: login.php?reset_token=" . urlencode($_GET["token"]));
    exit;
}

function is_strong_password(string $pw, ?string &$msg = null): bool {
    if (strlen($pw) < 10)              { $msg = "Password must be at least 10 characters."; return false; }
    if (!preg_match('/[A-Z]/', $pw))   { $msg = "Must contain at least one uppercase letter."; return false; }
    if (!preg_match('/[a-z]/', $pw))   { $msg = "Must contain at least one lowercase letter."; return false; }
    if (!preg_match('/[0-9]/', $pw))   { $msg = "Must contain at least one number."; return false; }
    if (!preg_match('/[\W_]/', $pw))   { $msg = "Must contain at least one symbol (e.g. !@#\$%)."; return false; }
    return true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token   = trim($_POST["token"] ?? "");
    $pw      = $_POST["password"]         ?? "";
    $confirm = $_POST["password_confirm"] ?? "";
    $error   = "";

    if (empty($token) || empty($pw)) {
        logPasswordResetEvent(
            $mysqli,
            'anonymous',
            'guest',
            'Password Reset Failed',
            'Password reset attempted with missing token or password.',
            'Unknown Account',
            'Failed'
        );
        $error = "Invalid or expired token.";
    } elseif ($pw !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!is_strong_password($pw, $msg)) {
        $error = $msg;
    } else {
        try {
            // Validate token — users table uses reset_token_hash
            $stmt = $mysqli->prepare("
                SELECT id, user_id, institutional_email
                FROM users
                WHERE reset_token_hash = ?
                  AND reset_token_expires_at IS NOT NULL
                  AND reset_token_expires_at > NOW()
                LIMIT 1
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                logPasswordResetEvent(
                    $mysqli,
                    'anonymous',
                    'guest',
                    'Password Reset Failed',
                    'Password reset attempted with invalid or expired token.',
                    'Unknown Account',
                    'Failed'
                );
                $error = "Invalid or expired reset token.";
            } else {
                $hash = password_hash($pw, PASSWORD_DEFAULT);

                // Update password column (users table uses `password`, not `temporary_password`)
                // Also clear the reset token and set must_change_password = 0
                $updateStmt = $mysqli->prepare("
                    UPDATE users
                    SET password                    = ?,
                        must_change_password        = 0,
                        password_changed            = 1,
                        manually_changed_password   = 1,
                        reset_token_hash            = NULL,
                        reset_token_expires_at      = NULL
                    WHERE id = ?
                ");
                $updateStmt->bind_param("si", $hash, $user['id']);
                $updateStmt->execute();

                $performedBy = $user['institutional_email'] ?: $user['user_id'];
                logPasswordResetEvent(
                    $mysqli,
                    $performedBy,
                    'user',
                    'Password Reset Completed',
                    "Password was successfully reset via email link for account {$performedBy}.",
                    $performedBy,
                    'Success'
                );
                header("Location: login.php?reset_success=1");
                exit;
            }
        } catch (Exception $e) {
            logPasswordResetEvent(
                $mysqli,
                'anonymous',
                'guest',
                'Password Reset Failed',
                "System error during password reset: " . $e->getMessage(),
                'Unknown Account',
                'Failed'
            );
            $error = "A system error occurred. Please try again.";
        }
    }

    // Redirect back with error
    $tokenParam = urlencode($token);
    $errorParam = urlencode($error);
    header("Location: login.php?reset_token={$tokenParam}&reset_error={$errorParam}");
    exit;
}
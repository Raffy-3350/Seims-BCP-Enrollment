<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: login.php");
    exit;
}

require_once __DIR__ . '/../app/config/db.php';   // gives us $mysqli (PDO object)
require_once __DIR__ . '/../app/config/mailer.php';

$identifier = trim($_POST["identifier"] ?? '');
$password   = $_POST["password"]        ?? '';

// ── Audit logger (PDO version) ────────────────────────────────────────────
if (!function_exists('logActivity')) {
    function logActivity($pdo, $performedBy, $performedByRole, $eventType, $details, $affectedEntity, $status) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_log (performed_by, performed_by_role, event_type, details, affected_entity, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$performedBy, $performedByRole, $eventType, $details, $affectedEntity, $status]);
        } catch (Throwable $e) {
            error_log("Audit log error in auth.php: " . $e->getMessage());
        }
    }
}

if (empty($identifier) || empty($password)) {
    header("location: login.php?error=" . urlencode("Please fill in all required fields."));
    exit;
}

// ── Helper: log failure and redirect ─────────────────────────────────────
function failAndRedirect($pdo, $performedBy, $role, $detail, $redirectMsg) {
    logActivity($pdo, $performedBy, $role, 'Login Failed', $detail, $performedBy, 'Failed');
    header("location: login.php?error=" . urlencode($redirectMsg));
    exit;
}

try {
    // ── 1. Look up user ───────────────────────────────────────────────────
    // PostgreSQL: COALESCE instead of IFNULL, no TRIM() issues
    $stmt = $mysqli->prepare("
        SELECT
            id,
            user_id,
            institutional_email,
            personal_email,
            password,
            must_change_password,
            CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS full_name,
            TRIM(role) AS role,
            status
        FROM users
        WHERE user_id = ?
           OR institutional_email = ?
           OR personal_email = ?
        LIMIT 1
    ");
    $stmt->execute([$identifier, $identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ── 2. No user found ──────────────────────────────────────────────────
    if (!$user) {
        failAndRedirect(
            $mysqli,
            $identifier, 'unknown',
            "Login attempt with unrecognised identifier: '$identifier'.",
            "Invalid username or password."
        );
    }

    $displayId = $user['institutional_email'] ?: $user['user_id'];

    // ── 3. Account status check ───────────────────────────────────────────
    if ($user['status'] !== 'active') {
        failAndRedirect(
            $mysqli,
            $displayId, $user['role'],
            "Login blocked — account status is '{$user['status']}'.",
            "Your account is inactive. Please contact the administrator."
        );
    }

    // ── 4. Password verification ──────────────────────────────────────────
    if (!password_verify($password, $user['password'] ?? '')) {
        failAndRedirect(
            $mysqli,
            $displayId, $user['role'],
            "Failed login attempt — incorrect password.",
            "Invalid username or password."
        );
    }

    // ── 5. Check valid remember_me token — skip OTP if exists ────────────
    $stmt = $mysqli->prepare("
        SELECT remember_token
        FROM users
        WHERE id = ?
          AND remember_token IS NOT NULL
          AND token_expiry > NOW()
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $hasToken = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($hasToken) {
        session_regenerate_id(true);
        $_SESSION['loggedin']      = true;
        $_SESSION['id']            = $user['id'];
        $_SESSION['username']      = $user['user_id'];
        $_SESSION['full_name']     = trim($user['full_name']);
        $_SESSION['role']          = $user['role'];
        $_SESSION['email']         = $user['institutional_email'];
        $_SESSION['last_activity'] = time();

        logActivity($mysqli, $displayId, $user['role'], 'User Login',
            "'{$user['full_name']}' logged in via Remember Me (OTP skipped).", $displayId, 'Success');

        $dest = (strtolower(trim($user['role'])) === 'student') ? 'student_navigation.php' : 'navigation.php';
        header("location: " . $dest);
        exit;
    }

    // ── 6. Store partial session (not fully logged in yet) ────────────────
    session_regenerate_id(true);
    $_SESSION['otp_pending_id']             = $user['id'];
    $_SESSION['otp_pending_role']           = $user['role'];
    $_SESSION['otp_pending_display_id']     = $displayId;
    $_SESSION['otp_pending_full_name']      = trim($user['full_name']);
    $_SESSION['otp_pending_username']       = $user['user_id'];
    $_SESSION['otp_pending_email']          = $user['institutional_email'];
    $_SESSION['otp_pending_personal_email'] = $user['personal_email'];
    $_SESSION['otp_pending_must_change_pw'] = ($user['must_change_password'] == 1) ? true : false;
    $_SESSION['otp_remember_me']            = isset($_POST['remember']);

    // ── 7. Generate OTP and store in DB ──────────────────────────────────
    // PostgreSQL interval syntax: NOW() + INTERVAL '10 minutes'
    $otp  = (string) random_int(100000, 999999);
    $stmt = $mysqli->prepare("
        UPDATE users
        SET otp_code   = ?,
            otp_expiry = NOW() + INTERVAL '10 minutes'
        WHERE id = ?
    ");
    $stmt->execute([$otp, $user['id']]);

    // ── 8. Send OTP email ─────────────────────────────────────────────────
    $emailTo = $user['personal_email'] ?: $user['institutional_email'];
    $subject = "Your SIEMS Verification Code";
    $body    = "
        <div style=\"font-family:'DM Sans',Arial,sans-serif;max-width:480px;margin:0 auto;\">
            <div style=\"background:#0f246c;padding:24px 32px;border-radius:10px 10px 0 0;\">
                <h2 style=\"color:#fff;margin:0;font-size:1.3rem;letter-spacing:0.05em;\">SIEMS — Verification Code</h2>
            </div>
            <div style=\"background:#f0f5ff;padding:32px;border-radius:0 0 10px 10px;border:1px solid #dbeafe;\">
                <p style=\"color:#374151;margin:0 0 16px;\">Hello, <strong>" . htmlspecialchars(trim($user['full_name'])) . "</strong></p>
                <p style=\"color:#374151;margin:0 0 24px;\">Use the code below to complete your sign-in. It expires in <strong>10 minutes</strong>.</p>
                <div style=\"text-align:center;margin:0 0 24px;\">
                    <span style=\"display:inline-block;background:#fff;border:2px solid #2563eb;border-radius:12px;padding:16px 40px;font-size:2.25rem;font-weight:800;letter-spacing:0.25em;color:#0f246c;\">{$otp}</span>
                </div>
                <p style=\"color:#6b7280;font-size:0.8125rem;margin:0;\">If you did not attempt to log in, please contact your system administrator immediately.</p>
            </div>
        </div>
    ";

    if (!sendEmail($emailTo, $subject, $body)) {
        $stmt = $mysqli->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        logActivity($mysqli, $displayId, $user['role'], 'OTP Send Failed',
            "Could not send OTP email to {$emailTo}.", $displayId, 'Failed');
        header("location: login.php?error=" . urlencode("Could not send verification email. Please try again."));
        exit;
    }

    logActivity($mysqli, $displayId, $user['role'], 'OTP Sent',
        "Verification code sent to {$emailTo} for login.", $displayId, 'Success');

    // ── 9. Redirect to OTP verification page ─────────────────────────────
    header("location: verify_otp.php");
    exit;

} catch (Exception $e) {
    error_log("Auth error: " . $e->getMessage());
    header("location: login.php?error=" . urlencode("A system error occurred. Please try again."));
    exit;
}

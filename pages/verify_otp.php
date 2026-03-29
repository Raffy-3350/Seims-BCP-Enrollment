<?php
session_start();

// Must have a pending OTP session — otherwise boot back to login
if (!isset($_SESSION['otp_pending_id'])) {
    header("location: login.php");
    exit;
}

require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/config/mailer.php';

if (!function_exists('logActivity')) {
    function logActivity($conn, $performedBy, $performedByRole, $eventType, $details, $affectedEntity, $status) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO audit_log (performed_by, performed_by_role, event_type, details, affected_entity, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("ssssss", $performedBy, $performedByRole, $eventType, $details, $affectedEntity, $status);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log("Audit log error in verify_otp.php: " . $e->getMessage());
        }
    }
}

$error        = '';
$success      = '';
$pendingId    = $_SESSION['otp_pending_id'];
$displayId    = $_SESSION['otp_pending_display_id'];
$role         = $_SESSION['otp_pending_role'];
$fullName     = $_SESSION['otp_pending_full_name'];
$maskedEmail  = '';

// Build masked email for display e.g. j***@school.edu
$rawEmail = $_SESSION['otp_pending_personal_email'] ?? $_SESSION['otp_pending_email'] ?? '';
if ($rawEmail) {
    [$localPart, $domain] = explode('@', $rawEmail, 2);
    $maskedEmail = substr($localPart, 0, 1) . str_repeat('*', max(1, strlen($localPart) - 1)) . '@' . $domain;
}

// ── Handle Resend ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action']) && $_POST['_action'] === 'resend') {
    $newOtp = (string) random_int(100000, 999999);
    $stmt = $mysqli->prepare("UPDATE users SET otp_code = ?, otp_expiry = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?");
    $stmt->bind_param("si", $newOtp, $pendingId);
    $stmt->execute();
    $stmt->close();

    $subject = "Your SIEMS Verification Code";
    $body    = "
        <div style=\"font-family:'DM Sans',Arial,sans-serif;max-width:480px;margin:0 auto;\">
            <div style=\"background:#0f246c;padding:24px 32px;border-radius:10px 10px 0 0;\">
                <h2 style=\"color:#fff;margin:0;font-size:1.3rem;letter-spacing:0.05em;\">SIEMS — Verification Code</h2>
            </div>
            <div style=\"background:#f0f5ff;padding:32px;border-radius:0 0 10px 10px;border:1px solid #dbeafe;\">
                <p style=\"color:#374151;margin:0 0 16px;\">Hello, <strong>" . htmlspecialchars($fullName) . "</strong></p>
                <p style=\"color:#374151;margin:0 0 24px;\">Your new verification code is below. It expires in <strong>10 minutes</strong>.</p>
                <div style=\"text-align:center;margin:0 0 24px;\">
                    <span style=\"display:inline-block;background:#fff;border:2px solid #2563eb;border-radius:12px;padding:16px 40px;font-size:2.25rem;font-weight:800;letter-spacing:0.25em;color:#0f246c;\">{$newOtp}</span>
                </div>
                <p style=\"color:#6b7280;font-size:0.8125rem;margin:0;\">If you did not request this, contact your administrator immediately.</p>
            </div>
        </div>
    ";

    if (sendEmail($rawEmail, $subject, $body)) {
        logActivity($mysqli, $displayId, $role, 'OTP Resent', "New OTP sent to {$rawEmail}.", $displayId, 'Success');
        $success = "A new code has been sent to {$maskedEmail}.";
    } else {
        $error = "Failed to resend code. Please try again.";
    }
    $mysqli->close();
}

// ── Handle Verify ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $submitted = trim($_POST['otp'] ?? '');

    if (empty($submitted) || !ctype_digit($submitted) || strlen($submitted) !== 6) {
        $error = "Please enter the 6-digit code.";
    } else {
        $stmt = $mysqli->prepare("SELECT otp_code, otp_expiry FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $pendingId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || empty($row['otp_code'])) {
            $error = "No verification code found. Please log in again.";
            logActivity($mysqli, $displayId, $role, 'OTP Verify Failed', "No OTP found in DB for user.", $displayId, 'Failed');

        } elseif (new DateTime() > new DateTime($row['otp_expiry'])) {
            // Expired — clear it
            $mysqli->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE id = ?")->execute([$pendingId]);
            $error = "Your verification code has expired. Please request a new one.";
            logActivity($mysqli, $displayId, $role, 'OTP Expired', "Expired OTP used by {$displayId}.", $displayId, 'Failed');

        } elseif (!hash_equals($row['otp_code'], $submitted)) {
            $error = "Incorrect code. Please try again.";
            logActivity($mysqli, $displayId, $role, 'OTP Verify Failed', "Wrong OTP entered by {$displayId}.", $displayId, 'Failed');

        } else {
            // ── OTP passed — snapshot all pending session values into locals FIRST ──
            $mysqli->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE id = ?")->execute([$pendingId]);

            $snapUsername   = $_SESSION['otp_pending_username']       ?? '';
            $snapEmail      = $_SESSION['otp_pending_email']          ?? '';
            $snapRememberMe = $_SESSION['otp_remember_me']            ?? false;
            $mustChangePw   = ($_SESSION['otp_pending_must_change_pw'] === true);

            // Now safe to regenerate — all values are in local vars
            session_regenerate_id(true);

            // Build the clean authenticated session
            $_SESSION['loggedin']           = true;
            $_SESSION['id']                 = $pendingId;
            $_SESSION['username']           = $snapUsername;
            $_SESSION['full_name']          = $fullName;
            $_SESSION['role']               = $role;
            $_SESSION['email']              = $snapEmail;
            $_SESSION['last_activity']      = time();
            if ($mustChangePw) {
                $_SESSION['must_change_password'] = true;
            }

            // Remember Me
            if ($snapRememberMe) {
                $token  = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', time() + (86400 * 30));
                $stmt   = $mysqli->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param('ssi', $token, $expiry, $pendingId);
                    $stmt->execute();
                    $stmt->close();
                    setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true);
                }
            }

            logActivity($mysqli, $displayId, $role, 'User Login',
                "'{$fullName}' completed 2-step verification and logged in successfully.", $displayId, 'Success');

            $mysqli->close();

            if ($mustChangePw) {
                header("location: login.php?change_password=1&noredir=1");
                exit;
            }

            $dest = (strtolower($role ?? '') === 'student') ? 'student_navigation.php' : 'navigation.php';
header("location: " . $dest);
exit;
        }
    }
    if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIEMS — Verify Your Identity</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=Fraunces:ital,wght@0,300;0,400;0,600;1,300;1,400&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="shortcut icon" href="/assets/img/siems.png" type="image/x-icon">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --navy:         #0f246c;
            --navy-mid:     #1a3a8f;
            --navy-dark:    #091847;
            --blue:         #3B82F6;
            --blue-dark:    #1E40AF;
            --blue-light:   #60A5FA;
            --blue-pale:    #DBEAFE;
            --blue-faint:   #EFF6FF;
            --accent:       #2563EB;
            --white:        #ffffff;
            --bg:           #F0F5FF;
            --rule:         #E2E8F0;
            --ink:          #0F172A;
            --ink-soft:     #374151;
            --ink-muted:    #64748B;
            --ink-faint:    #94A3B8;
            --gold:         #c9a84c;
            --gold-lt:      #e8cc85;
            --error:        #dc2626;
            --success:      #16a34a;
            --shadow-blue:  0 6px 24px rgba(37, 99, 235, 0.22);
            --transition:   all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-sm:    8px;
            --radius-md:    12px;
        }

        html, body { height: 100%; font-family: 'DM Sans', sans-serif; -webkit-font-smoothing: antialiased; }

        body {
            height: 100vh;
            background: linear-gradient(135deg, #c7d9ff 0%, var(--bg) 50%, #f0f5ff 100%);
            display: flex;
            align-items: stretch;
            justify-content: center;
            overflow: hidden;
        }

        .card {
            width: 100%;
            max-width: 100%;
            background: var(--white);
            display: flex;
            min-height: 100vh;
            overflow: hidden;
            animation: cardIn 0.7s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── LEFT PANEL ───────────────────────────── */
        .left {
            position: relative;
            width: 47%;
            flex-shrink: 0;
            overflow: hidden;
            background: var(--bg);
        }
        .chev-1 {
            position: absolute;
            top: 0; left: 0; bottom: 0;
            width: 100%;
            background: linear-gradient(150deg, var(--navy-dark) 0%, var(--navy-mid) 100%);
            clip-path: polygon(0 0, 82% 0, 100% 50%, 82% 100%, 0 100%);
        }
        .chev-2 {
            position: absolute;
            top: 0; left: 0; bottom: 0;
            width: 108%;
            background: linear-gradient(140deg, #1a3a8f 0%, var(--accent) 100%);
            clip-path: polygon(0 0, 70% 0, 88% 50%, 70% 100%, 0 100%);
        }
        .chev-dots {
            position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none; z-index: 2;
            clip-path: polygon(0 0, 82% 0, 100% 50%, 82% 100%, 0 100%);
        }
        .chev-ring {
            position: absolute; top: 50%; left: 38%;
            transform: translate(-50%, -50%);
            width: 520px; height: 520px;
            border: 1px solid rgba(96,165,250,0.12); border-radius: 50%;
            pointer-events: none; z-index: 2;
            animation: spinSlow 60s linear infinite;
            clip-path: polygon(0 0, 82% 0, 100% 50%, 82% 100%, 0 100%);
        }
        .chev-ring2 {
            position: absolute; top: 50%; left: 38%;
            transform: translate(-50%, -50%);
            width: 340px; height: 340px;
            border: 1px solid rgba(96,165,250,0.08); border-radius: 50%;
            pointer-events: none; z-index: 2;
            animation: spinSlow 40s linear infinite reverse;
            clip-path: polygon(0 0, 82% 0, 100% 50%, 82% 100%, 0 100%);
        }
        @keyframes spinSlow { to { transform: translate(-50%,-50%) rotate(360deg); } }

        .logo-wrap {
            position: absolute; top: 0; left: 0; bottom: 0; width: 74%;
            z-index: 5; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 16px; padding: 2rem 1rem 2rem 1.5rem;
            animation: logoIn 0.9s 0.15s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes logoIn {
            from { opacity: 0; transform: scale(0.82); }
            to   { opacity: 1; transform: scale(1); }
        }
        .shield { position: relative; width: 154px; height: 175px; filter: drop-shadow(0 12px 32px rgba(0,0,0,0.45)); }
        .shield svg { width: 100%; height: 100%; }
        .shield-body { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; padding-bottom: 12px; gap: 4px; }
        .brand { text-align: center; }
        .brand-name { font-family: 'Barlow Condensed', sans-serif; font-size: 1.75rem; font-weight: 800; color: var(--white); text-transform: uppercase; letter-spacing: 0.05em; line-height: 1.1; text-shadow: 0 2px 14px rgba(0,0,0,0.4); }
        .brand-name span { color: var(--gold-lt); display: block; font-size: 1.1rem; font-weight: 700; }
        .brand-sub { font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.38); text-transform: uppercase; letter-spacing: 0.15em; margin-top: 6px; }
        .system-label { display: inline-flex; align-items: center; gap: 7px; font-size: 9px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--blue-light); background: rgba(96,165,250,0.14); border: 1px solid rgba(96,165,250,0.28); padding: 5px 13px; border-radius: 20px; margin-top: 4px; }
        .pulse-dot { width: 6px; height: 6px; background: var(--blue-light); border-radius: 50%; animation: pulse 2.4s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(0.75); } }

        /* ── RIGHT PANEL ──────────────────────────── */
        .right {
            flex: 1; padding: 3rem 3.5rem 2.5rem 2.5rem;
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            overflow-y: auto; position: relative; background: var(--bg);
        }
        .right-inner { width: 100%; max-width: 420px; }
        .right::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-lt), var(--accent));
        }

        .page-hd { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--blue-pale); }
        .page-hd-top { font-family: 'Barlow Condensed', sans-serif; font-size: 1rem; font-weight: 700; color: var(--accent); letter-spacing: 0.12em; text-transform: uppercase; line-height: 1; }
        .page-hd h1 { font-family: 'Barlow Condensed', sans-serif; font-size: clamp(1.8rem, 2.8vw, 2.5rem); font-weight: 800; color: var(--ink); text-transform: uppercase; letter-spacing: 0.04em; line-height: 1.05; }
        .page-hd h1 span { color: var(--accent); }

        /* OTP specific */
        .otp-info {
            background: var(--blue-faint);
            border: 1px solid var(--blue-pale);
            border-radius: 10px;
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .otp-info-icon { width: 28px; height: 28px; background: linear-gradient(135deg, var(--accent), var(--blue-dark)); border-radius: 7px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .otp-info-icon i { font-size: 0.8rem; color: var(--white); }
        .otp-info-text { font-size: 0.8125rem; color: var(--ink-soft); line-height: 1.55; }
        .otp-info-text strong { display: block; font-weight: 700; color: var(--blue-dark); margin-bottom: 2px; font-size: 0.8125rem; }

        /* 6-digit input boxes */
        .otp-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 1.5rem 0;
        }
        .otp-box {
            width: 52px; height: 60px;
            text-align: center;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--navy);
            background: var(--white);
            border: 1.5px solid rgba(59,130,246,0.25);
            border-radius: var(--radius-sm);
            outline: none;
            transition: var(--transition);
            caret-color: var(--accent);
        }
        .otp-box:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.10);
        }
        .otp-box.filled { border-color: var(--accent); background: var(--blue-faint); }
        .otp-box.err    { border-color: #fca5a5; background: #fff8f8; }

        .alert {
            display: flex; align-items: flex-start; gap: 10px;
            border-radius: 10px; padding: 0.875rem 1rem; margin-bottom: 1.25rem;
            animation: shakeIn 0.5s ease;
        }
        @keyframes shakeIn { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-5px)} 40%,80%{transform:translateX(5px)} }
        .alert-error  { background:#fef2f2; border:1px solid #fecaca; border-left:3px solid var(--error); }
        .alert-success{ background:#f0fdf4; border:1px solid #bbf7d0; border-left:3px solid var(--success); }
        .alert i      { font-size:1rem; margin-top:1px; flex-shrink:0; }
        .alert-error  i { color:var(--error); }
        .alert-success i { color:var(--success); }
        .alert span   { font-size:0.8125rem; font-weight:500; line-height:1.5; }
        .alert-error  span { color:#7f1d1d; }
        .alert-success span { color:#14532d; }

        .sbtn {
            width: 100%; padding: 0.9rem;
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--accent) 100%);
            color: var(--white); border: none; border-radius: var(--radius-sm);
            font-family: 'Barlow Condensed', sans-serif; font-size: 1.1rem;
            font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
            cursor: pointer; position: relative; overflow: hidden;
            transition: var(--transition); box-shadow: var(--shadow-blue);
        }
        .sbtn::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.14),transparent); transition:left 0.5s; }
        .sbtn:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(37,99,235,0.32); }
        .sbtn:hover::before { left:100%; }
        .sbtn:active { transform:translateY(0); }
        .sbtn:disabled { opacity:0.65; cursor:not-allowed; transform:none; }
        .sbi { display:flex; align-items:center; justify-content:center; gap:8px; }

        .resend-row {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.8125rem;
            color: var(--ink-muted);
        }
        .resend-row button {
            background: none; border: none; color: var(--accent);
            font-weight: 600; cursor: pointer; font-size: 0.8125rem;
            padding: 0; transition: color 0.2s;
        }
        .resend-row button:hover { text-decoration: underline; }
        .resend-row button:disabled { color: var(--ink-faint); cursor: not-allowed; text-decoration: none; }

        .back-row {
            margin-top: 0.75rem;
            text-align: center;
            font-size: 0.8125rem;
            color: var(--ink-muted);
        }
        .back-row a { color: var(--ink-muted); text-decoration: none; display:inline-flex; align-items:center; gap:4px; }
        .back-row a:hover { color: var(--accent); }

        /* Countdown timer */
        #countdown { font-weight: 700; color: var(--accent); }
        #countdown.expired { color: var(--error); }

        .page-footer { margin-top: 1.25rem; font-size: 0.65rem; color: var(--ink-faint); text-align: center; }
        .page-footer a { color: var(--accent); text-decoration: none; }

        @media (max-width: 700px) {
            body { overflow-y: auto; }
            .card { flex-direction: column; min-height: 100vh; }
            .left { width: 100%; height: 220px; flex-shrink: 0; }
            .chev-1 { clip-path: polygon(0 0, 100% 0, 100% 80%, 50% 100%, 0 80%); }
            .chev-2 { clip-path: polygon(0 0, 100% 0, 100% 68%, 50% 90%, 0 68%); }
            .chev-dots,.chev-ring,.chev-ring2 { clip-path: polygon(0 0, 100% 0, 100% 80%, 50% 100%, 0 80%); }
            .logo-wrap { width:100%; flex-direction:row; padding:1.5rem 2rem; gap:1.25rem; }
            .brand { text-align:left; }
            .shield { width:90px; height:102px; }
            .right { padding:1.75rem 1.5rem; align-items:stretch; }
            .right-inner { max-width:100%; }
            .otp-box { width:44px; height:54px; font-size:1.5rem; }
        }
    </style>
</head>
<body>
<div class="card">

    <!-- ── LEFT PANEL ─────────────────────────── -->
    <div class="left">
        <div class="chev-1"></div>
        <div class="chev-2"></div>
        <div class="chev-dots"></div>
        <div class="chev-ring"></div>
        <div class="chev-ring2"></div>
        <div class="logo-wrap">
            <div class="shield">
                <div class="shield-body">
                    <img src="../assets/img/siems.png"
                         alt="SIEMS Logo"
                         style="width:400px;height:auto;object-fit:contain;filter:drop-shadow(0 2px 8px rgba(0,0,0,0.4));">
                </div>
            </div>
            <div class="brand">
                <div class="brand-name"><span>Student Information</span></div>
                <div class="brand-sub">Enrollment Management System</div>
                <div class="system-label" style="margin-top:10px;">
                    <span class="pulse-dot"></span> System Online
                </div>
            </div>
        </div>
    </div>

    <!-- ── RIGHT PANEL ────────────────────────── -->
    <div class="right">
    <div class="right-inner">

        <div class="page-hd">
            <div class="page-hd-top">SIEMS — Two-Step Verification</div>
            <h1>Verify <span>Identity</span></h1>
        </div>

        <div class="otp-info">
            <div class="otp-info-icon"><i class='bx bx-envelope'></i></div>
            <div class="otp-info-text">
                <strong>Check your email</strong>
                A 6-digit verification code was sent to <strong><?= htmlspecialchars($maskedEmail) ?></strong>. Enter it below to continue.
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class='bx bx-error-circle'></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class='bx bx-check-circle'></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        <?php endif; ?>

        <form id="otpForm" method="POST" action="verify_otp.php" novalidate>
            <div class="otp-inputs" id="otpInputs">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" aria-label="Digit 1">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 2">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 3">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 4">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 5">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 6">
            </div>
            <input type="hidden" name="otp" id="otpHidden">

            <button type="submit" class="sbtn" id="verifyBtn" disabled>
                <span class="sbi" id="verifyBtnInner">
                    <i class='bx bx-shield-check'></i> Verify &amp; Sign In
                </span>
            </button>
        </form>

        <div class="resend-row">
            Code expires in <span id="countdown">10:00</span> &nbsp;·&nbsp;
            <form method="POST" action="verify_otp.php" style="display:inline;">
                <input type="hidden" name="_action" value="resend">
                <button type="submit" id="resendBtn" disabled>Resend code</button>
            </form>
        </div>

        <div class="back-row">
            <a href="login.php"><i class='bx bx-arrow-back'></i> Back to Sign In</a>
        </div>

        <div class="page-footer">
            SIEMS &mdash; Student Information &amp; Enrollment Integrated Management System &mdash; &nbsp;&middot;&nbsp;
            <a href="#">Privacy Policy</a> &middot; <a href="#">Support</a>
        </div>

    </div>
    </div>
</div>

<script>
    // ── OTP box auto-advance & paste support ─────────────────────────────────
    const boxes      = Array.from(document.querySelectorAll('.otp-box'));
    const hiddenOtp  = document.getElementById('otpHidden');
    const verifyBtn  = document.getElementById('verifyBtn');
    const verifyInner= document.getElementById('verifyBtnInner');
    const form       = document.getElementById('otpForm');

    function updateHidden() {
        const val = boxes.map(b => b.value).join('');
        hiddenOtp.value = val;
        const complete = val.length === 6 && /^\d{6}$/.test(val);
        verifyBtn.disabled = !complete;
        boxes.forEach(b => {
            b.classList.toggle('filled', b.value !== '');
        });
    }

    boxes.forEach((box, idx) => {
        box.addEventListener('input', e => {
            // Allow only digits
            box.value = box.value.replace(/\D/g, '').slice(-1);
            updateHidden();
            if (box.value && idx < boxes.length - 1) boxes[idx + 1].focus();
        });

        box.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !box.value && idx > 0) {
                boxes[idx - 1].value = '';
                boxes[idx - 1].focus();
                updateHidden();
            }
            if (e.key === 'ArrowLeft'  && idx > 0) boxes[idx - 1].focus();
            if (e.key === 'ArrowRight' && idx < boxes.length - 1) boxes[idx + 1].focus();
        });

        // Handle paste into any box
        box.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            pasted.split('').forEach((ch, i) => {
                if (boxes[i]) boxes[i].value = ch;
            });
            updateHidden();
            const nextEmpty = boxes.find(b => !b.value);
            if (nextEmpty) nextEmpty.focus(); else boxes[5].focus();
        });
    });

    // Auto-focus first box
    if (boxes[0]) boxes[0].focus();

    // Loading state on submit
    form.addEventListener('submit', function() {
        verifyBtn.disabled = true;
        verifyInner.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Verifying…';
    });

    // ── Countdown timer (10 min) ──────────────────────────────────────────────
    const countdownEl = document.getElementById('countdown');
    const resendBtn   = document.getElementById('resendBtn');
    let seconds = 600;

    const timer = setInterval(() => {
        seconds--;
        if (seconds <= 0) {
            clearInterval(timer);
            countdownEl.textContent = 'Expired';
            countdownEl.classList.add('expired');
            verifyBtn.disabled = true;
            resendBtn.disabled = false;
            return;
        }
        const m = String(Math.floor(seconds / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        countdownEl.textContent = `${m}:${s}`;
        // Enable resend after 60 seconds
        if (seconds <= 540) resendBtn.disabled = false;
    }, 1000);
</script>
</body>
</html>

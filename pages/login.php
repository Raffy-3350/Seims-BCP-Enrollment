<?php
session_start();
require_once __DIR__ . '/../app/config/db.php';

// ── Change Password (first-login modal) ───────────────────────────────────────
$cp_error   = "";
$cp_success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["_action"]) && $_POST["_action"] === "change_password") {
    // Must be in a "pending password change" state
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        header("location: login.php");
        exit;
    }

    $pw      = $_POST["password"]         ?? "";
    $confirm = $_POST["password_confirm"] ?? "";

    function is_strong_password(string $pw, ?string &$msg = null): bool {
        if (strlen($pw) < 10)            { $msg = "At least 10 characters required."; return false; }
        if (!preg_match('/[A-Z]/', $pw)) { $msg = "Must include an uppercase letter."; return false; }
        if (!preg_match('/[a-z]/', $pw)) { $msg = "Must include a lowercase letter."; return false; }
        if (!preg_match('/[0-9]/', $pw)) { $msg = "Must include a number."; return false; }
        if (!preg_match('/[\W_]/', $pw)) { $msg = "Must include a symbol (e.g. !@#\$%)."; return false; }
        return true;
    }

    if ($pw !== $confirm) {
        $cp_error = "Passwords do not match.";
    } elseif (!is_strong_password($pw, $msg)) {
        $cp_error = $msg;
    } else {
        try {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE users SET password = ?, must_change_password = 0, password_changed = 1, manually_changed_password = 0 WHERE id = ?");
            $stmt->bind_param("si", $hash, $_SESSION['id']);
            $stmt->execute();
            $stmt->close();
            unset($_SESSION['must_change_password']);
            $destination = (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'student')
                ? 'student_navigation.php'
                : 'navigation.php';
            header("location: " . $destination);
            exit;
        } catch (PDOException $e) {
            $cp_error = "System error. Please try again.";
        }
    }
}
// ── End Change Password ────────────────────────────────────────────────────────

// ── Check for "Remember Me" cookie BEFORE the already-logged-in redirect ──────
// This must run first so the session is populated before the redirect check below.
if (!isset($_SESSION["loggedin"]) && isset($_COOKIE['remember_token'])) {
    $token        = $_COOKIE['remember_token'];
    $current_time = date('Y-m-d H:i:s');

    $sql = "
        SELECT
            id,
            user_id,
            institutional_email,
            TRIM(CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name)) AS full_name,
            role,
            must_change_password
        FROM users
        WHERE remember_token = ? AND token_expiry > ?
        LIMIT 1
    ";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("ss", $token, $current_time);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $user_id, $institutional_email, $full_name, $role, $must_change_password);
                $stmt->fetch();

                session_regenerate_id(true);
                $_SESSION["loggedin"]      = true;
                $_SESSION["id"]            = $id;
                $_SESSION["username"]      = $user_id;
                $_SESSION["full_name"]     = $full_name;
                $_SESSION["role"]          = $role;
                $_SESSION["email"]         = $institutional_email;
                $_SESSION["last_activity"] = time();
                if ($must_change_password) {
                    $_SESSION["must_change_password"] = true;
                }
            }
        }
        $stmt->close();
    }
}

// ── Already logged in → redirect to the correct dashboard ─────────────────────
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && !$cp_error) {
    if (!isset($_SESSION["must_change_password"]) || $_SESSION["must_change_password"] !== true) {
        $skipRedir = ['noredir','reset_sent','reset_token','reset_error','reset_success'];
        if (!array_intersect($skipRedir, array_keys($_GET))) {
            $dest = (strtolower($_SESSION['role'] ?? '') === 'student') ? 'student_navigation.php' : 'navigation.php';
            header("location: " . $dest);
            exit;
        }
    }
}               
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIEMS — Student Information & Enrollment Management</title>
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
            --shadow-blue:  0 6px 24px rgba(37, 99, 235, 0.22);
            --shadow-md:    0 8px 32px rgba(15, 36, 108, 0.10);
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
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
            z-index: 2;
            clip-path: polygon(0 0, 82% 0, 100% 50%, 82% 100%, 0 100%);
        }
        .chev-ring {
            position: absolute;
            top: 50%; left: 38%;
            transform: translate(-50%, -50%);
            width: 520px; height: 520px;
            border: 1px solid rgba(96,165,250,0.12);
            border-radius: 50%;
            pointer-events: none;
            z-index: 2;
            animation: spinSlow 60s linear infinite;
            clip-path: polygon(0 0, 82% 0, 100% 50%, 82% 100%, 0 100%);
        }
        .chev-ring2 {
            position: absolute;
            top: 50%; left: 38%;
            transform: translate(-50%, -50%);
            width: 340px; height: 340px;
            border: 1px solid rgba(96,165,250,0.08);
            border-radius: 50%;
            pointer-events: none;
            z-index: 2;
            animation: spinSlow 40s linear infinite reverse;
            clip-path: polygon(0 0, 82% 0, 100% 50%, 82% 100%, 0 100%);
        }
        @keyframes spinSlow { to { transform: translate(-50%,-50%) rotate(360deg); } }

        .logo-wrap {
            position: absolute;
            top: 0; left: 0; bottom: 0;
            width: 74%;
            z-index: 5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 2rem 1rem 2rem 1.5rem;
            animation: logoIn 0.9s 0.15s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes logoIn {
            from { opacity: 0; transform: scale(0.82); }
            to   { opacity: 1; transform: scale(1); }
        }

        .shield {
            position: relative;
            width: 154px;
            height: 175px;
            filter: drop-shadow(0 12px 32px rgba(0,0,0,0.45));
        }
        .shield svg { width: 100%; height: 100%; }
        .shield-body {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding-bottom: 12px;
            gap: 4px;
        }

        .brand { text-align: center; }
        .brand-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--white);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            line-height: 1.1;
            text-shadow: 0 2px 14px rgba(0,0,0,0.4);
        }
        .brand-name span { color: var(--gold-lt); display: block; font-size: 1.1rem; font-weight: 700; }
        .brand-sub {
            font-size: 10px;
            font-weight: 600;
            color: rgba(255,255,255,0.38);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-top: 6px;
        }

        .system-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--blue-light);
            background: rgba(96,165,250,0.14);
            border: 1px solid rgba(96,165,250,0.28);
            padding: 5px 13px;
            border-radius: 20px;
            margin-top: 4px;
        }
        .pulse-dot {
            width: 6px; height: 6px;
            background: var(--blue-light);
            border-radius: 50%;
            animation: pulse 2.4s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.75); }
        }

        /* ── RIGHT PANEL ──────────────────────────── */
        .right {
            flex: 1;
            padding: 3rem 3.5rem 2.5rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            position: relative;
            background: var(--bg);
        }

        .right-inner {
            width: 100%;
            max-width: 420px;
        }

        .right::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-lt), var(--accent));
        }

        .page-hd {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--blue-pale);
        }
        .page-hd-top {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: 0.12em;
            text-transform: uppercase;
            line-height: 1;
        }
        .page-hd h1 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: clamp(1.8rem, 2.8vw, 2.5rem);
            font-weight: 800;
            color: var(--ink);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            line-height: 1.05;
        }
        .page-hd h1 span { color: var(--accent); }

        .role-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 1.5rem;
        }
        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: var(--blue-dark);
            background: var(--blue-pale);
            border: 1px solid var(--blue-pale);
            padding: 4px 10px;
            border-radius: 6px;
        }
        .role-pill .dot {
            width: 5px; height: 5px;
            background: var(--accent);
            border-radius: 50%;
            flex-shrink: 0;
        }

        .error-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 3px solid var(--error);
            border-radius: 10px;
            padding: 0.875rem 1rem;
            margin-bottom: 1.25rem;
            animation: shakeIn 0.5s ease;
        }
        @keyframes shakeIn {
            0%,100% { transform: translateX(0); }
            20%,60% { transform: translateX(-5px); }
            40%,80% { transform: translateX(5px); }
        }
        .error-alert i { color: var(--error); font-size: 1rem; margin-top: 1px; flex-shrink: 0; }
        .error-alert span { font-size: 0.8125rem; font-weight: 500; color: #7f1d1d; line-height: 1.5; }

        .fgrid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 1.25rem;
        }
        .s2 { grid-column: 1 / -1; }

        .fld { display: flex; flex-direction: column; gap: 4px; }
        .fld label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.10em;
            text-transform: uppercase;
            color: var(--blue-dark);
        }
        .fld label i { font-size: 0.6rem; color: var(--accent); }

        .iw {
            position: relative;
            display: flex;
            align-items: center;
        }

        .fi {
            width: 100%;
            padding: 0.78rem 1rem 0.78rem 2.4rem;
            border: 1.5px solid rgba(59, 130, 246, 0.25);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9375rem;
            font-weight: 450;
            color: var(--ink);
            background: var(--white);
            outline: none;
            transition: var(--transition);
            appearance: none;
            position: relative;
            z-index: 1;
        }
        .fi::placeholder { color: var(--ink-faint); font-weight: 400; }
        .fi:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }
        .fi.err { border-color: #fca5a5; background: #fff8f8; }

        .ii {
            position: absolute;
            left: 0.7rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: var(--blue-light);
            pointer-events: none;
            transition: color 0.17s;
            z-index: 2;
        }
        .iw:focus-within .ii { color: var(--accent); }

        .peye {
            position: absolute;
            right: 0.7rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: var(--ink-faint);
            cursor: pointer;
            z-index: 3;
            transition: color 0.17s;
            background: none;
            border: none;
            padding: 4px;
        }
        .peye:hover { color: var(--accent); }

        .pw-strength {
            margin-top: 6px;
            font-size: 0.75rem;
            color: var(--ink-muted);
        }
        .pw-strength span {
            font-weight: 600;
        }
        .pw-strength.weak span {
            color: #dc2626;
        }
        .pw-strength.medium span {
            color: #ea580c;
        }
        .pw-strength.strong span {
            color: #16a34a;
        }

        .ff {
            margin-top: 0.875rem;
            font-size: 0.8125rem;
            color: var(--ink-muted);
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .ff a { color: var(--accent); font-weight: 600; text-decoration: none; }
        .ff a:hover { text-decoration: underline; }

        .sbtn {
            margin-top: 1.1rem;
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--accent) 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow-blue);
        }
        .sbtn::before {
            content:'';
            position:absolute;
            top:0; left:-100%;
            width:100%; height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,0.14),transparent);
            transition: left 0.5s;
        }
        .sbtn:hover { transform:translateY(-2px); box-shadow: 0 10px 28px rgba(37,99,235,0.32); }
        .sbtn:hover::before { left:100%; }
        .sbtn:active { transform:translateY(0); }
        .sbi { display:flex; align-items:center; justify-content:center; gap:8px; }

        .security-notice {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--blue-faint);
            border: 1px solid var(--blue-pale);
            border-radius: 10px;
            padding: 0.875rem 1rem;
            margin-top: 1rem;
        }
        .security-icon {
            width: 28px; height: 28px;
            background: linear-gradient(135deg, var(--accent), var(--blue-dark));
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .security-icon i { font-size: 0.8rem; color: var(--white); }
        .security-text { font-size: 0.6875rem; color: var(--ink-soft); line-height: 1.55; }
        .security-text strong { display: block; font-weight: 700; color: var(--blue-dark); margin-bottom: 2px; font-size: 0.6875rem; }

        .page-footer {
            margin-top: 1.25rem;
            font-size: 0.65rem;
            color: var(--ink-faint);
            text-align: center;
        }
        .page-footer a { color: var(--accent); text-decoration: none; }
        .page-footer a:hover { text-decoration: underline; }

        @media (max-width: 700px) {
            body { overflow-y: auto; }
            .card { flex-direction: column; min-height: 100vh; }
            .left { width: 100%; height: 220px; flex-shrink: 0; }
            .chev-1 { clip-path: polygon(0 0, 100% 0, 100% 80%, 50% 100%, 0 80%); }
            .chev-2 { clip-path: polygon(0 0, 100% 0, 100% 68%, 50% 90%, 0 68%); }
            .chev-dots { clip-path: polygon(0 0, 100% 0, 100% 80%, 50% 100%, 0 80%); }
            .chev-ring, .chev-ring2 { clip-path: polygon(0 0, 100% 0, 100% 80%, 50% 100%, 0 80%); }
            .logo-wrap { width:100%; flex-direction:row; padding:1.5rem 2rem; gap:1.25rem; }
            .brand { text-align:left; }
            .shield { width:90px; height:102px; }
            .right { padding:1.75rem 1.5rem; align-items: stretch; }
            .right-inner { max-width: 100%; }
            .fgrid { grid-template-columns:1fr; }
            .s2 { grid-column:1; }
        }

        /* Change-password strength bar */
        .cp-strength { font-size: 0.72rem; font-weight: 700; margin-top: 5px; color: #94a3b8; }
        .cp-strength .bar-track { height: 4px; background: #e2e8f0; border-radius: 9999px; margin-bottom: 4px; }
        .cp-strength .bar-fill  { height: 100%; border-radius: 9999px; transition: all 0.3s; width: 0; background: #ef4444; }
        .cp-strength.weak   .bar-fill { background: #ef4444; width: 33%; }
        .cp-strength.medium .bar-fill { background: #f59e0b; width: 66%; }
        .cp-strength.strong .bar-fill { background: #22c55e; width: 100%; }

        /* Modal Styles */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px); z-index: 2000;
            display: none; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s ease;
        }
        .modal-overlay.show { display: flex; opacity: 1; }
        .modal-card {
            background: var(--white); width: 90%; max-width: 420px;
            padding: 2rem; border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: translateY(20px); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        .modal-overlay.show .modal-card { transform: translateY(0); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .modal-title { font-family: 'Barlow Condensed', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--navy); text-transform: uppercase; }
        .modal-close { background: none; border: none; font-size: 1.5rem; color: var(--ink-muted); cursor: pointer; transition: color 0.2s; }
        .modal-close:hover { color: var(--error); }
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
                         style="width:400px; height:auto; object-fit:contain; filter:drop-shadow(0 2px 8px rgba(0,0,0,0.4));">
                </div>
            </div>

            <div class="brand">
                <div class="brand-name">
                    <span>Student Information</span>
                </div>
                <div class="brand-sub">Enrollment Management System</div>
                <div class="system-label" style="margin-top:10px;">
                    <span class="pulse-dot"></span>
                    System Online
                </div>
            </div>
        </div>
    </div>

    <!-- ── RIGHT PANEL ────────────────────────── -->
    <div class="right">
    <div class="right-inner">

        <div class="page-hd">
            <div class="page-hd-top">SIEMS — Operations Portal</div>
            <h1>Portal <span>Sign In</span></h1>
        </div>

        <div class="role-pills">
            <span class="role-pill"><span class="dot"></span> Super Admin</span>
            <span class="role-pill"><span class="dot"></span> Registrar</span>
            <span class="role-pill"><span class="dot"></span> Faculty</span>
            <span class="role-pill"><span class="dot"></span> Staff</span>
        </div>

        <!-- Error alert (hidden by default) -->
        <div class="error-alert" id="errorAlert" style="<?php echo empty($_GET['error']) ? 'display:none;' : 'display:flex;'; ?>">
            <i class='bx bx-error-circle'></i>
            <span id="errorMsg">
                <?php echo isset($_GET['error']) ? htmlspecialchars($_GET['error']) : 'Invalid credentials. Access denied.'; ?>
            </span>
        </div>

        <form id="loginForm" action="auth.php" method="POST" novalidate>
            <div class="fgrid">

                <div class="fld s2">
                    <label><i class='bx bx-circle'></i> Username or Email <span style="color:var(--accent)">*</span></label>
                    <div class="iw">
                        <i class='bx bx-user ii'></i>
                        <input
                            type="text"
                            class="fi"
                            name="identifier"
                            id="identifier"
                            placeholder="Enter your username or email"
                            autocomplete="username"
                            spellcheck="false"
                        >
                    </div>
                </div>

                <div class="fld s2">
                    <label><i class='bx bx-circle'></i> Password <span style="color:var(--accent)">*</span></label>
                    <div class="iw">
                        <i class='bx bx-lock ii'></i>
                        <input
                            type="password"
                            class="fi"
                            name="password"
                            id="pw1"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            style="padding-right:2.4rem;"
                        >
                        <button type="button" class="peye" id="tp1" aria-label="Toggle password">
                            <i class='bx bx-show' id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

            </div>

            <div style="margin-top:1rem; display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="remember" id="remember" style="width:16px; height:16px; cursor:pointer; accent-color:var(--accent);">
                <label for="remember" style="font-size:0.85rem; color:var(--ink-muted); cursor:pointer; font-weight:500;">Remember me on this device</label>
            </div>

            <div class="ff">
                <span>Forgot your password? <a href="#" id="forgotLink">Reset Here</a></span>
            </div>

            <button type="submit" class="sbtn" id="submitBtn">
                <span class="sbi" id="btnInner">
                    <i class='bx bx-log-in'></i> Sign In to Portal
                </span>
            </button>
        </form>

        <div class="security-notice">
            <div class="security-icon"><i class='bx bxs-shield-alt-2'></i></div>
            <div class="security-text">
                <strong>Authorized Personnel Only</strong>
                This system is for SIEMS-registered users only. All access attempts are logged and monitored by the system administrator.
            </div>
        </div>

        <div class="page-footer">
            SIEMS &mdash; Student Information &amp; Enrollment Integrated Management System &mdash; &nbsp;&middot;&nbsp;
            <a href="#">Privacy Policy</a> &middot; <a href="#">Support</a>
        </div>

    </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal-overlay" id="forgotModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">Reset Password</div>
            <button type="button" class="modal-close" id="closeModal">&times;</button>
        </div>
        <p style="font-size: 0.9rem; color: var(--ink-muted); margin-bottom: 1.5rem; line-height: 1.5;">
            Enter your registered email address and we'll send you a secure link to reset your password.
        </p>

        <?php if(isset($_GET['reset_error'])): ?>
            <div class="error-alert" style="display:flex; margin-bottom:1.25rem;">
                <i class='bx bx-error-circle'></i>
                <span><?php echo htmlspecialchars($_GET['reset_error']); ?></span>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['reset_sent'])): ?>
            <div class="error-alert" style="display:flex; margin-bottom:1.25rem; background:#dcfce7; border-color:#bbf7d0; border-left-color:#16a34a;">
                <i class='bx bx-check-circle' style="color:#16a34a;"></i>
                <span style="color:#166534;">Reset link sent. Please check your email.</span>
            </div>
        <?php endif; ?>

        <form action="send_reset.php" method="POST">
            <div class="fld">
                <label>Email Address</label>
                <div class="iw">
                    <i class='bx bx-envelope ii'></i>
                    <input type="email" name="email" class="fi" required placeholder="name@example.com">
                </div>
            </div>
            <button type="submit" class="sbtn" style="margin-top: 1.5rem;">Send Reset Link</button>
        </form>
    </div>
</div>

<!-- Change Password Modal (first-login) -->
<div class="modal-overlay" id="changePwModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">Set Your Password</div>
            <button type="button" class="modal-close" id="closeChangePw">&times;</button>
        </div>
        <p style="font-size:0.875rem; color:var(--ink-muted); margin-bottom:1.25rem; line-height:1.55;">
            This is your first login. Please set a strong personal password before continuing.
        </p>

        <?php if ($cp_error): ?>
            <div class="error-alert" style="display:flex; margin-bottom:1.25rem;">
                <i class='bx bx-error-circle'></i>
                <span><?= htmlspecialchars($cp_error) ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="changePwForm">
            <input type="hidden" name="_action" value="change_password">

            <div class="fld" style="margin-bottom:0.75rem;">
                <label>New Password</label>
                <div class="iw">
                    <i class='bx bx-lock-alt ii'></i>
                    <input type="password" name="password" id="cpPw" class="fi" required minlength="10"
                           placeholder="Min 10 chars · upper · lower · number · symbol"
                           oninput="cpCheckStrength(this.value)"
                           style="padding-right:2.4rem;">
                    <button type="button" class="peye" onclick="cpToggle('cpPw','cpEye1')" aria-label="Toggle">
                        <i class='bx bx-show' id="cpEye1"></i>
                    </button>
                </div>
                <div class="cp-strength" id="cpStrengthEl">
                    <div class="bar-track"><div class="bar-fill" id="cpStrengthBar"></div></div>
                    <span id="cpStrengthLabel">Enter a password</span>
                </div>
            </div>

            <div class="fld" style="margin-bottom:1rem;">
                <label>Confirm Password</label>
                <div class="iw">
                    <i class='bx bx-lock-alt ii'></i>
                    <input type="password" name="password_confirm" id="cpPw2" class="fi" required minlength="10"
                           placeholder="Re-enter your password"
                           style="padding-right:2.4rem;">
                    <button type="button" class="peye" onclick="cpToggle('cpPw2','cpEye2')" aria-label="Toggle">
                        <i class='bx bx-show' id="cpEye2"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="sbtn">
                <span class="sbi"><i class='bx bx-shield-check'></i> Save Password &amp; Continue</span>
            </button>
        </form>
    </div>
</div>

<!-- Set New Password Modal -->
<div class="modal-overlay" id="newPwModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">Set New Password</div>
            <button type="button" class="modal-close" id="closeNewPw">&times;</button>
        </div>
        <p style="font-size: 0.9rem; color: var(--ink-muted); margin-bottom: 1.2rem; line-height: 1.5;">
            Create a strong password for your SIEMS account. Make sure it's something only you know.
        </p>

        <?php if(isset($_GET['reset_error']) && isset($_GET['reset_token'])): ?>
            <div class="error-alert" style="display:flex; margin-bottom:1.25rem;">
                <i class='bx bx-error-circle'></i>
                <span><?php echo htmlspecialchars($_GET['reset_error']); ?></span>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['reset_success'])): ?>
            <div class="error-alert" style="display:flex; margin-bottom:1.25rem; background:#dcfce7; border-color:#bbf7d0; border-left-color:#16a34a;">
                <i class='bx bx-check-circle' style="color:#16a34a;"></i>
                <span style="color:#166534;">Password updated successfully. You can now sign in with your new password.</span>
            </div>
        <?php endif; ?>

        <form action="reset_new_password.php" method="POST" id="newPwForm">
            <input type="hidden" name="token" id="resetTokenField" value="<?php echo isset($_GET['reset_token']) ? htmlspecialchars($_GET['reset_token']) : ''; ?>">
            <div class="fld">
                <label>New Password</label>
                <div class="iw">
                    <i class='bx bx-lock-alt ii'></i>
                    <input
                        type="password"
                        name="password"
                        class="fi"
                        required
                        minlength="10"
                        placeholder="At least 10 chars, with upper, lower, number, symbol"
                    >
                    <button type="button" class="peye" id="newPwToggle" aria-label="Toggle new password visibility">
                        <i class='bx bx-show' id="newPwEye"></i>
                    </button>
                </div>
                <div id="pwStrength" class="pw-strength">Password strength: <span>Weak</span></div>
            </div>
            <div class="fld" style="margin-top:0.75rem;">
                <label>Confirm New Password</label>
                <div class="iw">
                    <i class='bx bx-lock-alt ii'></i>
                    <input
                        type="password"
                        name="password_confirm"
                        class="fi"
                        required
                        minlength="10"
                        placeholder="Re-enter your new password"
                    >
                    <button type="button" class="peye" id="newPwConfirmToggle" aria-label="Toggle confirm password visibility">
                        <i class='bx bx-show' id="newPwConfirmEye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="sbtn" style="margin-top: 1.5rem;">Update Password</button>
        </form>
    </div>
</div>

<script>
    // Password toggle
    const tp1 = document.getElementById('tp1');
    const pw1 = document.getElementById('pw1');
    const eye = document.getElementById('eyeIcon');

    if (tp1 && pw1) {
        tp1.addEventListener('click', function () {
            const isHidden = pw1.type === 'password';
            pw1.type = isHidden ? 'text' : 'password';
            eye.className = isHidden ? 'bx bx-hide' : 'bx bx-show';
        });
    }

    const idField    = document.getElementById('identifier');
    const errorAlert = document.getElementById('errorAlert');
    const errorMsg   = document.getElementById('errorMsg');
    const submitBtn  = document.getElementById('submitBtn');
    const btnInner   = document.getElementById('btnInner');
    const form       = document.getElementById('loginForm');

    if (idField) idField.focus();

    // Hide the PHP-generated error when the user starts typing again
    document.querySelectorAll('.fi').forEach(inp => {
        inp.addEventListener('input', function () {
            this.classList.remove('err');
            if (errorAlert) errorAlert.style.display = 'none';
        });
    });

    // The JS now handles basic client-side validation and the loading state.
    // The actual authentication and redirection is handled by auth.php.
    if (form) {
        form.addEventListener('submit', function (e) {
            const id = (idField.value || '').trim();
            const pw = (pw1.value || '');

            idField.classList.remove('err');
            pw1.classList.remove('err');

            if (!id || !pw) {
                e.preventDefault(); // Stop form submission
                errorMsg.textContent = 'Please fill in all required fields.';
                errorAlert.style.display = 'flex';
                if (!id) idField.classList.add('err');
                if (!pw) pw1.classList.add('err');
                return;
            }

            // If fields are filled, show loading state and allow the form to submit to auth.php
            submitBtn.disabled = true;
            btnInner.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Authenticating…';
        });
    }

    // Modal Logic
    const forgotLink   = document.getElementById('forgotLink');
    const forgotModal  = document.getElementById('forgotModal');
    const closeModal   = document.getElementById('closeModal');
    const newPwModal   = document.getElementById('newPwModal');
    const closeNewPw   = document.getElementById('closeNewPw');
    const newPwInput   = document.querySelector('#newPwForm input[name="password"]');
    const pwStrengthEl = document.getElementById('pwStrength');
    const newPwToggle  = document.getElementById('newPwToggle');
    const newPwEye     = document.getElementById('newPwEye');
    const newPwConfirm = document.querySelector('#newPwForm input[name="password_confirm"]');
    const newPwConfirmToggle = document.getElementById('newPwConfirmToggle');
    const newPwConfirmEye    = document.getElementById('newPwConfirmEye');

    if (forgotLink && forgotModal && closeModal) {
        forgotLink.addEventListener('click', (e) => {
            e.preventDefault();
            forgotModal.classList.add('show');
        });

        closeModal.addEventListener('click', () => {
            forgotModal.classList.remove('show');
        });

        forgotModal.addEventListener('click', (e) => {
            if (e.target === forgotModal) {
                forgotModal.classList.remove('show');
            }
        });
    }

    // Auto-open Set New Password modal when coming from email link or after error
    (function() {
        const params = new URLSearchParams(window.location.search);
        const hasToken       = params.has('reset_token');
        const hasSuccess     = params.has('reset_success');
        const hasTokenError  = params.has('reset_error') && hasToken;
        const hasForgotState = params.has('reset_error') || params.has('reset_sent');

        if (newPwModal && (hasToken || hasSuccess || hasTokenError)) {
            newPwModal.classList.add('show');
        }

        if (forgotModal && hasForgotState && !hasToken) {
            forgotModal.classList.add('show');
        }

        if (closeNewPw && newPwModal) {
            closeNewPw.addEventListener('click', () => {
                newPwModal.classList.remove('show');
                const url = window.location.pathname;
                window.history.replaceState({}, document.title, url);
            });

            newPwModal.addEventListener('click', (e) => {
                if (e.target === newPwModal) {
                    newPwModal.classList.remove('show');
                    const url = window.location.pathname;
                    window.history.replaceState({}, document.title, url);
                }
            });
        }
    })();

    // Password strength indicator for new password in reset modal
    if (newPwInput && pwStrengthEl) {
        const strengthText = pwStrengthEl.querySelector('span');

        const evaluateStrength = (value) => {
            let score = 0;
            if (value.length >= 10) score++;
            if (/[A-Z]/.test(value)) score++;
            if (/[a-z]/.test(value)) score++;
            if (/[0-9]/.test(value)) score++;
            if (/[\W_]/.test(value)) score++;

            let label = 'Very weak';
            let cls = 'weak';
            if (score >= 4) {
                label = 'Strong';
                cls = 'strong';
            } else if (score === 3) {
                label = 'Medium';
                cls = 'medium';
            } else if (score === 2) {
                label = 'Weak';
                cls = 'weak';
            }

            pwStrengthEl.classList.remove('weak', 'medium', 'strong');
            pwStrengthEl.classList.add(cls);
            if (strengthText) strengthText.textContent = label;
        };

        newPwInput.addEventListener('input', (e) => {
            evaluateStrength(e.target.value || '');
        });
    }

    // Toggle visibility for new password fields in reset modal
    if (newPwToggle && newPwInput && newPwEye) {
        newPwToggle.addEventListener('click', () => {
            const isHidden = newPwInput.type === 'password';
            newPwInput.type = isHidden ? 'text' : 'password';
            newPwEye.className = isHidden ? 'bx bx-hide' : 'bx bx-show';
        });
    }

    // ── Change Password Modal ────────────────────────────────────────────────
    (function() {
        const overlay  = document.getElementById('changePwModal');
        const closeBtn = document.getElementById('closeChangePw');
        if (!overlay) return;

        // Auto-open if: ?change_password=1 in URL, a PHP cp_error exists, or session flag
        const params = new URLSearchParams(window.location.search);
        const hasCpFlag  = params.has('change_password');
        const hasCpError = <?= json_encode(!empty($cp_error)) ?>;
        const sessionFlag = <?= json_encode(isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] === true) ?>;

        if (hasCpFlag || hasCpError || sessionFlag) {
            overlay.classList.add('show');
        }

        // Close only if there's no server-side error (i.e. not mid-flow)
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                if (!hasCpError && !sessionFlag) {
                    overlay.classList.remove('show');
                    const url = window.location.pathname;
                    window.history.replaceState({}, document.title, url);
                }
            });
        }
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay && !hasCpError && !sessionFlag) {
                overlay.classList.remove('show');
                const url = window.location.pathname;
                window.history.replaceState({}, document.title, url);
            }
        });
    })();

    function cpToggle(inputId, eyeId) {
        const inp = document.getElementById(inputId);
        const eye = document.getElementById(eyeId);
        inp.type = inp.type === 'password' ? 'text' : 'password';
        eye.className = inp.type === 'text' ? 'bx bx-hide' : 'bx bx-show';
    }

    function cpCheckStrength(val) {
        let score = 0;
        if (val.length >= 10)   score++;
        if (/[A-Z]/.test(val))  score++;
        if (/[a-z]/.test(val))  score++;
        if (/[0-9]/.test(val))  score++;
        if (/[\W_]/.test(val))  score++;
        const el = document.getElementById('cpStrengthEl');
        const lb = document.getElementById('cpStrengthLabel');
        el.className = 'cp-strength ' + (score >= 4 ? 'strong' : score >= 3 ? 'medium' : 'weak');
        lb.textContent = score >= 4 ? 'Strong ✓' : score >= 3 ? 'Medium' : 'Weak';
    }
    // ── End Change Password Modal ────────────────────────────────────────────
</script>
</body>
</html>

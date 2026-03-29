<?php
session_start();

if (
    !isset($_SESSION['mfa_pending_user']) ||
    !isset($_SESSION['mfa_code_hash']) ||
    !isset($_SESSION['mfa_code_expires'])
) {
    header("location: ../pages/login.php");
    exit;
}

$pending_user   = $_SESSION['mfa_pending_user'];
$code_expires   = $_SESSION['mfa_code_expires'];
$error_message  = "";

if (time() > $code_expires) {
    unset($_SESSION['mfa_pending_user'], $_SESSION['mfa_code_hash'], $_SESSION['mfa_code_expires'], $_SESSION['mfa_code_plain']);
    header("location: ../pages/login.php?error=" . urlencode("Your verification code has expired. Please sign in again."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input_code = trim($_POST["mfa_code"] ?? "");

    if ($input_code === "") {
        $error_message = "Please enter the 6-digit code.";
    } else {
        $stored_hash = $_SESSION['mfa_code_hash'] ?? null;

        if ($stored_hash && password_verify($input_code, $stored_hash)) {
            unset($_SESSION['mfa_pending_user'], $_SESSION['mfa_code_hash'], $_SESSION['mfa_code_expires'], $_SESSION['mfa_code_plain']);
            
            // Set proper session variables for main system
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $_SESSION['mfa_user_id'];
            $_SESSION["username"] = $_SESSION['mfa_user_id'];
            $_SESSION["full_name"] = $_SESSION['mfa_user_name'];
            $_SESSION["role"] = $_SESSION['mfa_user_role'];
            $_SESSION["email"] = $_SESSION['mfa_user_email'];
            $_SESSION["last_activity"] = time();

            header("location: ../pages/navigation.php");
            exit;
        } else {
            $error_message = "The code you entered is incorrect. Please try again.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Login Code</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --navy:         #0f246c;
            --blue:         #2563EB;
            --blue-light:   #60A5FA;
            --bg:           #F0F5FF;
            --white:        #ffffff;
            --error:        #dc2626;
            --ink:          #0F172A;
            --ink-muted:    #64748B;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: radial-gradient(circle at top, #c7d9ff 0, var(--bg) 40%, #e5edff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .card {
            background: var(--white);
            width: 100%;
            max-width: 420px;
            padding: 2.25rem 2rem;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(15, 36, 108, 0.18);
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            margin-bottom: 1.3rem;
        }
        .shield {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--navy), var(--blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
        }
        h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--ink);
        }
        .subtitle {
            font-size: 0.85rem;
            color: var(--ink-muted);
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }
        .subtitle strong {
            font-weight: 600;
            color: var(--ink);
        }
        .code-inputs {
            display: flex;
            justify-content: center;
            gap: 0.6rem;
            margin: 1.1rem 0 0.5rem;
        }
        .code-inputs input {
            width: 42px;
            height: 48px;
            border-radius: 10px;
            border: 1.5px solid #d1d5db;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 600;
            outline: none;
            transition: border-color 0.18s, box-shadow 0.18s, transform 0.18s;
        }
        .code-inputs input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
            transform: translateY(-1px);
        }
        .error-msg {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #fef2f2;
            color: #991b1b;
            border-radius: 10px;
            padding: 0.6rem 0.8rem;
            font-size: 0.8rem;
            margin-bottom: 0.9rem;
        }
        .error-msg i {
            color: var(--error);
        }
        .hint {
            font-size: 0.75rem;
            color: var(--ink-muted);
            margin-bottom: 1.1rem;
        }
        .btn-primary {
            width: 100%;
            padding: 0.85rem;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, var(--navy), var(--blue));
            color: var(--white);
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: default;
        }
        .footer {
            margin-top: 0.9rem;
            font-size: 0.75rem;
            color: var(--ink-muted);
            text-align: center;
        }
        .footer a {
            color: var(--blue);
            text-decoration: none;
            font-weight: 500;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .dev-box {
            margin-top: 0.9rem;
            padding: 0.6rem 0.8rem;
            font-size: 0.8rem;
            border-radius: 8px;
            background: #ecfdf5;
            color: #166534;
        }
        .dev-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <div class="shield"><i class='bx bxs-shield-alt-2'></i></div>
            <div>
                <h1>Two-Step Verification</h1>
                <div style="font-size: 0.75rem; color: var(--ink-muted);">
                    Signed in as <strong><?php echo htmlspecialchars($pending_user['username']); ?></strong>
                </div>
            </div>
        </div>

        <p class="subtitle">
            We’ve sent a 6-digit verification code to <strong><?php echo htmlspecialchars($pending_user['email']); ?></strong>.
            Enter the code below to finish signing in.
        </p>

        <?php if ($error_message): ?>
            <div class="error-msg">
                <i class='bx bx-error-circle'></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form id="mfaForm" method="POST" autocomplete="off">
            <input type="hidden" name="mfa_code" id="mfa_code">

            <div class="code-inputs">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*">
            </div>

            <div class="hint">
                The code expires in 5 minutes. If the code is wrong or expired, you will be asked to sign in again.
            </div>

            <button type="submit" class="btn-primary" id="submitBtn">
                <span id="btnText"><i class='bx bx-lock-open-alt'></i> Verify &amp; Continue</span>
            </button>
        </form>

        <div class="footer">
            Not you? <a href="../../pages/logout.php">Sign in with a different account</a>
        </div>
    </div>

    <script>
        const inputs = Array.from(document.querySelectorAll('.code-inputs input'));
        const hiddenInput = document.getElementById('mfa_code');
        const form = document.getElementById('mfaForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');

        // Auto-focus first box
        if (inputs.length) {
            inputs[0].focus();
        }

        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = value;

                if (value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });

        form.addEventListener('submit', (e) => {
            const code = inputs.map(i => i.value).join('');
            hiddenInput.value = code;

            if (code.length !== 6) {
                e.preventDefault();
                inputs.forEach(i => i.classList.add('shake'));
                setTimeout(() => inputs.forEach(i => i.classList.remove('shake')), 300);
                return;
            }

            submitBtn.disabled = true;
            btnText.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Verifying…";
        });
    </script>
</body>
</html>

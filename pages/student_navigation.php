<?php
session_start();

// Security: require login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Security: idle timeout
$timeout_duration = 900; // 15 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("location: login.php?error=" . urlencode("Your session has expired due to inactivity."));
    exit;
}
$_SESSION['last_activity'] = time();

// Only students should land here
$role = strtolower($_SESSION['role'] ?? '');
if (strtolower($role) !== 'student') {
    header("location: navigation.php");
    exit;
}

// Force password change if provisioned account
if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] === true) {
    header("location: login.php?change_password=1");
    exit;
}

$fullName = $_SESSION["full_name"] ?? "Student";
$email    = $_SESSION["email"] ?? "";
$initials = '';
$parts = preg_split('/\s+/', trim($fullName));
if (is_array($parts)) {
    foreach ($parts as $p) {
        $p = trim((string)$p);
        if ($p === '') continue;
        $initials .= substr($p, 0, 1);
        if (strlen($initials) >= 2) break;
    }
}
if ($initials === '') $initials = 'ST';
$initials = strtoupper($initials);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Student Navigation</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Barlow+Condensed:wght@700;800;900&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="/assets/img/siems.png" type="image/x-icon">
    <link rel="shortcut icon" href="/assets/img/siems.png" type="image/x-icon">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root{
            --sidebar-w: 300px;
            --navy: #0f246c;
            --border: rgba(59,130,246,0.25);
            --blue: #2563eb;
            --bg: #f0f5ff;
            --surface: #ffffff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body{
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: #0f172a;
            overflow-x: hidden;
        }
        .page-wrapper{
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .page-wrapper.expanded { margin-left: 0; }
        .sidebar{
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            width: var(--sidebar-w);
            background: var(--navy);
            border-right: 1px solid var(--border);
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.35);
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .sidebar.collapsed{ transform: translateX(-100%); }
        .sidebar-header{
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 2px solid rgba(59,130,246,0.25);
        }
        .brand{
            display: flex;
            align-items: center;
            gap: 0.95rem;
        }
        .avatar{
            width: 52px; height: 52px;
            border-radius: 9999px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(59,130,246,0.35);
            display: flex; align-items: center; justify-content: center;
            font-weight: 900;
            color: #eff6ff;
            letter-spacing: 0.02em;
        }
        .brand-info h1{
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.2rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1.1;
        }
        .brand-info p{
            font-size: 0.75rem;
            color: rgba(255,255,255,0.72);
            margin-top: 2px;
            font-weight: 600;
            word-break: break-word;
        }

        .top-navbar{
            position: sticky;
            top: 0;
            z-index: 999;
            height: 72px;
            background: var(--surface);
            box-shadow: 0 1px 0 rgba(59,130,246,0.14), 0 2px 12px rgba(15,36,108,0.06);
            border-bottom: 1px solid rgba(59,130,246,0.12);
        }
        .navbar-content{
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.25rem;
        }
        .toggle-btn{
            background: transparent;
            border: 1px solid rgba(59,130,246,0.2);
            width: 42px; height: 42px;
            border-radius: 12px;
            color: #1e40af;
            font-size: 1.3rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
        }
        .profile-wrapper{
            position: relative;
        }
        .profile-btn{
            width: 40px; height: 40px;
            border-radius: 12px;
            background: rgba(37,99,235,0.10);
            border: 1px solid rgba(37,99,235,0.20);
            display: flex; align-items: center; justify-content: center;
            font-weight: 900;
            color: #1e40af;
            cursor: pointer;
        }
        .profile-dropdown{
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            width: 260px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(2,6,23,0.18);
            border: 1px solid rgba(59,130,246,0.16);
            display: none;
            overflow: hidden;
        }
        .profile-dropdown.show{ display: block; }
        .dropdown-header{
            padding: 14px 14px 12px;
            background: linear-gradient(135deg, rgba(37,99,235,0.10), rgba(37,99,235,0.04));
        }
        .dropdown-name{ font-weight: 900; color: #0f246c; }
        .dropdown-email{ font-size: 0.75rem; color: rgba(15,36,108,0.7); margin-top: 2px; font-weight: 700; word-break: break-word; }
        .dropdown-role{ font-size: 0.75rem; color: rgba(37,99,235,0.85); margin-top: 2px; font-weight: 800; text-transform: capitalize; }
        .dropdown-item{
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0f246c;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
        }
        .dropdown-item:hover{ background: rgba(37,99,235,0.08); }
        .dropdown-divider{ height: 1px; background: rgba(15,36,108,0.12); }

        /* Sidebar nav */
        .nav{ padding: 0.75rem 0.75rem 1.25rem; }
        .section-title{
            font-size: 0.68rem;
            font-weight: 900;
            color: rgba(255,255,255,0.65);
            text-transform: uppercase;
            letter-spacing: 1.4px;
            margin-top: 1.1rem;
            margin-bottom: 0.5rem;
            padding: 0 0.5rem;
        }
        .nav ul{ list-style: none; display: flex; flex-direction: column; gap: 0.45rem; }
        .nav ul li{ position: relative; }

        .nav a.nav-item{
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.95rem 0.95rem;
            color: #eff6ff;
            text-decoration: none;
            border-radius: 14px;
            border: 1px solid transparent;
            background: transparent;
            transition: all 0.2s ease;
        }
        .nav a.nav-item:hover{
            background: rgba(59,130,246,0.18);
            border-color: rgba(59,130,246,0.25);
            transform: translateX(6px);
        }
        .nav a.nav-item.active{
            background: rgba(59,130,246,0.26);
            border-color: rgba(59,130,246,0.35);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.15);
        }
        .left{ display: flex; align-items: center; gap: 0.65rem; }
        .icon-wrapper{
            width: 36px; height: 36px;
            border-radius: 12px;
            background: rgba(59,130,246,0.15);
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .nav-label{ display: flex; flex-direction: column; gap: 0.15rem; }
        .nav-label .main-text{ font-size: 0.95rem; font-weight: 700; }
        .nav-label .sub-text{ font-size: 0.72rem; opacity: 0.78; font-weight: 600; }
        .bx{ color: #eff6ff; }
        .sub-menu{
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.5s cubic-bezier(0.4,0,0.2,1), opacity 0.3s ease;
            margin: 0.35rem 0 0;
            border-radius: 14px;
            background: rgba(0,0,0,0.16);
        }
        .nav ul li.open .sub-menu{
            max-height: 600px;
            opacity: 1;
            padding: 0.5rem 0 0.5rem 0.45rem;
        }
        .sub-menu li a{
            padding: 0.65rem 0.75rem 0.65rem 2.25rem;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 600;
        }
        .sub-menu li a:hover{
            background: rgba(59,130,246,0.18);
        }

        .content-area{ padding: 1.5rem 1.75rem; }
        .content-panel{ display: none; animation: fadeIn 0.25s ease; }
        .content-panel.active{ display: block; }
        @keyframes fadeIn { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform: translateY(0);} }

        @media (max-width: 900px){
            .sidebar{ transform: translateX(-100%); }
            .sidebar.show{ transform: translateX(0); }
            .page-wrapper{ margin-left: 0; }
        }
    </style>
</head>

<body>
<div id="overlay" class="fixed inset-0 bg-black/30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-200"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand">
            <div class="avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div class="brand-info">
                <h1><?php echo htmlspecialchars($fullName); ?></h1>
                <p><?php echo htmlspecialchars($email); ?></p>
            </div>
        </div>
    </div>

    <nav class="nav" aria-label="Student sidebar">
        <span class="section-title">Student Dashboard</span>
        <ul>
            <li>
                <a href="#" class="nav-item active" onclick="return showPanel('dashboard', this);">
                    <span class="left">
                        <div class="icon-wrapper"><i class='bx bx-tachometer'></i></div>
                        <span class="nav-label">
                            <span class="main-text">Dashboard</span>
                            <span class="sub-text">SMS Account Fundamentals</span>
                        </span>
                    </span>
                </a>
            </li>
            <li><a href="#" class="nav-item" onclick="return showPanel('smsProfile', this);">
                <span class="left">
                    <div class="icon-wrapper"><i class='bx bx-user'></i></div>
                    <span class="nav-label"><span class="main-text">SMS Profile</span><span class="sub-text">Student profile details</span></span>
                </span>
            </a></li>
            <li><a href="#" class="nav-item" onclick="return showPanel('moduleGrant', this);">
                <span class="left">
                    <div class="icon-wrapper"><i class='bx bx-purchase-tag'></i></div>
                    <span class="nav-label"><span class="main-text">Module Grant</span><span class="sub-text">Approved learning modules</span></span>
                </span>
            </a></li>
            <li><a href="#" class="nav-item" onclick="return showPanel('permit', this);">
                <span class="left">
                    <div class="icon-wrapper"><i class='bx bx-file'></i></div>
                    <span class="nav-label"><span class="main-text">Permit</span><span class="sub-text">Enrollment permit status</span></span>
                </span>
            </a></li>
            <li><a href="#" class="nav-item" onclick="return showPanel('semestralGrade', this);">
                <span class="left">
                    <div class="icon-wrapper"><i class='bx bx-bar-chart-alt'></i></div>
                    <span class="nav-label"><span class="main-text">Semestral Grade</span><span class="sub-text">Current semester grades</span></span>
                </span>
            </a></li>
        </ul>

        <span class="section-title">Enrollment</span>
        <ul>
            <li class="open">
                <a href="#" class="nav-item" onclick="toggleSubmenu(this); return false;">
                    <span class="left">
                        <div class="icon-wrapper"><i class='bx bx-book-open'></i></div>
                        <span class="nav-label"><span class="main-text">Enrollment</span><span class="sub-text">Enrollment information</span></span>
                    </span>
                    <i class='bx bx-chevron-down text-[#dbeafe]'></i>
                </a>
                <ul class="sub-menu">
                    <li><a href="#" class="nav-item" onclick="return showPanel('enrollmentInformation', this);">
                        <span class="left"><div class="icon-wrapper !w-28"><i class='bx bx-info-circle'></i></div><span class="nav-label"><span class="main-text">Enrollment Information</span><span class="sub-text">Program & year details</span></span></span>
                    </a></li>
                    <li><a href="#" class="nav-item" onclick="return showPanel('enrollment', this);">
                        <span class="left"><div class="icon-wrapper !w-28"><i class='bx bx-list-check'></i></div><span class="nav-label"><span class="main-text">Enrollment</span><span class="sub-text">Current enrollment record</span></span></span>
                    </a></li>
                    <li><a href="#" class="nav-item" onclick="return showPanel('registrarForms', this);">
                        <span class="left"><div class="icon-wrapper !w-28"><i class='bx bx-receipt'></i></div><span class="nav-label"><span class="main-text">Registrar Forms</span><span class="sub-text">Download official forms</span></span></span>
                    </a></li>
                </ul>
            </li>
        </ul>

        <span class="section-title">Wallet and Payments</span>
        <ul>
            <li><a href="#" class="nav-item" onclick="return showPanel('accountStatement', this);">
                <span class="left">
                    <div class="icon-wrapper"><i class='bx bx-wallet'></i></div>
                    <span class="nav-label"><span class="main-text">Account Statement</span><span class="sub-text">Billing & balance summary</span></span>
                </span>
                <span class="ml-2 inline-flex items-center px-2.5 py-1 rounded-full bg-amber-300/80 text-amber-900 text-[11px] font-black">BETA</span>
            </a></li>
        </ul>

        <span class="section-title">Scholarship</span>
        <ul>
            <li class="open">
                <a href="#" class="nav-item" onclick="toggleSubmenu(this); return false;">
                    <span class="left">
                        <div class="icon-wrapper"><i class='bx bx-award'></i></div>
                        <span class="nav-label"><span class="main-text">Scholarship</span><span class="sub-text">Scholarship options</span></span>
                    </span>
                    <i class='bx bx-chevron-down text-[#dbeafe]'></i>
                </a>
                <ul class="sub-menu">
                    <li><a href="#" class="nav-item" onclick="return showPanel('scholarship', this);">
                        <span class="left"><div class="icon-wrapper !w-28"><i class='bx bx-badge-check'></i></div><span class="nav-label"><span class="main-text">Scholarship</span><span class="sub-text">Current scholarship status</span></span></span>
                    </a></li>
                    <li><a href="#" class="nav-item" onclick="return showPanel('scholarshipApplication', this);">
                        <span class="left"><div class="icon-wrapper !w-28"><i class='bx bx-edit'></i></div><span class="nav-label"><span class="main-text">Scholarship Application</span><span class="sub-text">Submit application requests</span></span></span>
                    </a></li>
                </ul>
            </li>
        </ul>

        <span class="section-title">Gradebook</span>
        <ul>
            <li class="open">
                <a href="#" class="nav-item" onclick="toggleSubmenu(this); return false;">
                    <span class="left">
                        <div class="icon-wrapper"><i class='bx bx-book'></i></div>
                        <span class="nav-label"><span class="main-text">Gradebook</span><span class="sub-text">Grades and subjects</span></span>
                    </span>
                    <i class='bx bx-chevron-down text-[#dbeafe]'></i>
                </a>
                <ul class="sub-menu">
                    <li><a href="#" class="nav-item" onclick="return showPanel('academicProgress', this);">
                        <span class="left"><div class="icon-wrapper !w-28"><i class='bx bx-line-chart'></i></div><span class="nav-label"><span class="main-text">Academic Progress</span><span class="sub-text">TOR & academic tracking</span></span></span>
                    </a></li>
                    <li><a href="#" class="nav-item" onclick="return showPanel('lmsSubjects', this);">
                        <span class="left"><div class="icon-wrapper !w-28"><i class='bx bx-list-ul'></i></div><span class="nav-label"><span class="main-text">LMS Subjects</span><span class="sub-text">Your LMS subject list</span></span></span>
                    </a></li>
                </ul>
            </li>
        </ul>

        <div class="mt-6 px-2">
            <div class="rounded-2xl border border-white/20 bg-white/10 p-3">
                <div class="text-white font-black text-sm">Secure Platform</div>
                <div class="text-white/70 text-xs font-bold mt-1">Online & operational</div>
            </div>
        </div>
    </nav>
</aside>

<div class="page-wrapper" id="pageWrapper">
    <header class="top-navbar">
        <div class="navbar-content">
            <button id="toggleBtn" class="toggle-btn" aria-label="Toggle sidebar">
                <i class='bx bx-menu'></i>
            </button>

            <div class="flex items-center gap-3">
                <div id="clock" class="hidden sm:block text-sm font-black text-slate-700"></div>
                <div class="profile-wrapper" id="profileWrapper">
                    <div id="profileBtn" class="profile-btn"><?php echo htmlspecialchars($initials); ?></div>
                    <div id="profileDropdown" class="profile-dropdown">
                        <div class="dropdown-header">
                            <div class="dropdown-name"><?php echo htmlspecialchars($fullName); ?></div>
                            <div class="dropdown-email"><?php echo htmlspecialchars($email); ?></div>
                            <div class="dropdown-role">Student</div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item" onclick="return false;"><i class='bx bx-user'></i>My Profile</a>
                        <a href="#" class="dropdown-item" onclick="return false;"><i class='bx bx-cog'></i>Settings</a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item" style="color:#b91c1c;"><i class='bx bx-log-out'></i>Log Out</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="content-area">
        <!-- Student panels -->
        <div id="panel-dashboard" class="content-panel active">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-xl font-black text-[#0f246c] uppercase tracking-wide">Student Dashboard</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">SMS Account Fundamentals</p>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                        <div class="text-xs font-black text-blue-800 uppercase">Profile</div>
                        <div class="text-sm font-black text-slate-800 mt-2">View your student information</div>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                        <div class="text-xs font-black text-blue-800 uppercase">Payments</div>
                        <div class="text-sm font-black text-slate-800 mt-2">View your account statement</div>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                        <div class="text-xs font-black text-blue-800 uppercase">Grades</div>
                        <div class="text-sm font-black text-slate-800 mt-2">Track your academic progress</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="panel-smsProfile" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-lg font-black text-[#0f246c]">SMS Profile</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel (connect to SMS profile data later).</p>
            </div>
        </div>

        <div id="panel-moduleGrant" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-lg font-black text-[#0f246c]">Module Grant</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel.</p>
            </div>
        </div>

        <div id="panel-permit" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-lg font-black text-[#0f246c]">Permit</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel.</p>
            </div>
        </div>

        <div id="panel-semestralGrade" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-lg font-black text-[#0f246c]">Semestral Grade</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel.</p>
            </div>
        </div>

        <div id="panel-enrollmentInformation" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-lg font-black text-[#0f246c]">Enrollment Information</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel.</p>
            </div>
        </div>

        <div id="panel-enrollment" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-lg font-black text-[#0f246c]">Enrollment</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel.</p>
            </div>
        </div>

        <div id="panel-registrarForms" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-lg font-black text-[#0f246c]">Registrar Forms</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel.</p>
            </div>
        </div>

        <div id="panel-accountStatement" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-black text-[#0f246c]">Account Statement</h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-300/80 text-amber-900 text-[11px] font-black">BETA</span>
                </div>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel.</p>
            </div>
        </div>

        <div id="panel-scholarship" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-lg font-black text-[#0f246c]">Scholarship</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel.</p>
            </div>
        </div>

        <div id="panel-scholarshipApplication" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-lg font-black text-[#0f246c]">Scholarship Application</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel.</p>
            </div>
        </div>

        <div id="panel-academicProgress" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-lg font-black text-[#0f246c]">Academic Progress</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel.</p>
            </div>
        </div>

        <div id="panel-lmsSubjects" class="content-panel">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6">
                <h2 class="text-lg font-black text-[#0f246c]">LMS Subjects</h2>
                <p class="text-sm font-bold text-slate-500 mt-1">Placeholder panel.</p>
            </div>
        </div>
    </main>
</div>

<script>
    function showPanel(panelId, navLink) {
        document.querySelectorAll('.content-panel').forEach(p => p.style.display = 'none');
        const target = document.getElementById('panel-' + panelId);
        if (target) target.style.display = 'block';

        document.querySelectorAll('.nav a.nav-item').forEach(a => a.classList.remove('active'));
        if (navLink) navLink.classList.add('active');
        return false;
    }

    function toggleSubmenu(element) {
        const parent = element.parentElement;
        document.querySelectorAll('.nav ul li.open').forEach(item => {
            if (item !== parent) item.classList.remove('open');
        });
        parent.classList.toggle('open');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const pageWrapper = document.getElementById('pageWrapper');
        const toggleBtn = document.getElementById('toggleBtn');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', e => {
                e.stopPropagation();
                if (window.innerWidth <= 900) {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('opacity-100', sidebar.classList.contains('show'));
                    overlay.classList.toggle('pointer-events-none', !sidebar.classList.contains('show'));
                } else {
                    sidebar.classList.toggle('collapsed');
                    pageWrapper.classList.toggle('expanded');
                }
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                overlay.classList.add('pointer-events-none');
            });
        }

        // Close dropdown on outside click
        const profileWrapper = document.getElementById('profileWrapper');
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');

        if (profileBtn && profileDropdown) {
            profileBtn.addEventListener('click', e => {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
            });
            document.addEventListener('click', e => {
                if (profileWrapper && !profileWrapper.contains(e.target)) profileDropdown.classList.remove('show');
            });
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') profileDropdown.classList.remove('show');
            });
        }

        // Optional clock display
        const clock = document.getElementById('clock');
        if (clock) {
            function updateClock() {
                const now = new Date();
                const phTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
                let hours = phTime.getHours();
                const mins = String(phTime.getMinutes()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12;
                clock.textContent = `${hours}:${mins} ${ampm}`;
            }
            updateClock();
            setInterval(updateClock, 1000);
        }
    });
</script>
</body>
</html>

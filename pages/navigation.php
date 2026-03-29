<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Students should not access admin navigation
$sessionRole = $_SESSION['role'] ?? '';
if (strtolower((string)$sessionRole) === 'student') {
    header("location: student_navigation.php");
    exit;
}

// SECURITY: Idle Session Timeout
$timeout_duration = 900; // 15 minutes (900 seconds)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("location: login.php?error=" . urlencode("Your session has expired due to inactivity."));
    exit;
}
$_SESSION['last_activity'] = time();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAS System</title>

    <!-- FIX #1: All <link> and <script> tags belong in <head>, not inside panel divs -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Barlow+Condensed:wght@700;800;900&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="/assets/img/siems.png" type="image/x-icon">
    <!-- FIX #2: Tailwind CDN must be in <head>, not inside a panel div -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
:root {
    --navy:        #0f246c;
    --navy-dark:   #0a1a50;
    --blue-500:    #3B82F6;
    --blue-600:    #2563EB;
    --blue-700:    #1E40AF;
    --blue-light:  #93C5FD;
    --bg:          #F0F4FF;
    --surface:     #FFFFFF;
    --border:      rgba(59, 130, 246, 0.14);
    --text-900:    #0F1E4A;
    --text-600:    #4B5E8A;
    --text-400:    #8EA0C4;
    --shadow-sm:   0 2px 8px rgba(15, 36, 108, 0.08);
    --shadow-md:   0 6px 20px rgba(15, 36, 108, 0.12);
    --r-sm: 8px;
    --r-md: 12px;
    --r-lg: 16px;
    --sidebar-w:  300px;
    --topbar-h:   64px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text-900);
    -webkit-font-smoothing: antialiased;
    overflow-x: hidden;
}

.page-wrapper {
    margin-left: var(--sidebar-w);
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.page-wrapper.expanded { margin-left: 0; }

.sidebar {
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    width: var(--sidebar-w);
    background: var(--navy);
    border-right: 1px solid rgba(59, 130, 246, 0.25);
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1000;
    transition: var(--transition);
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.4);
}

.sidebar.collapsed { transform: translateX(-100%); }
.sidebar::-webkit-scrollbar       { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: rgba(0,0,0,0.15); border-radius: 10px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }

.top-navbar {
    width: 100%;
    height: var(--topbar-h);
    background: var(--surface);
    position: relative;
    z-index: 999;
    box-shadow: 0 1px 0 var(--border), 0 2px 12px rgba(15, 36, 108, 0.06);
    overflow: visible;
}

.main-content { padding: 2rem; flex: 1; }

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 2px solid rgba(59, 130, 246, 0.25);
    position: relative;
    overflow: hidden;
}

.sidebar-header::before {
    content: '';
    position: absolute;
    top: -50%; right: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(59,130,246,0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

.brand { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; position: relative; z-index: 1; }

.logo {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, var(--blue-600), var(--blue-500));
    border-radius: 12px;
    border: 2px solid rgba(59,130,246,0.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: white; flex-shrink: 0;
    box-shadow: 0 2px 12px rgba(59,130,246,0.3);
    animation: logoFloat 3s ease-in-out infinite;
}

.brand-info h1 { font-size: 0.85rem; font-weight: 700; color: #fff; margin: 0; letter-spacing: 0.5px; }
.brand-info p  { font-size: 0.7rem; color: #CBD5E1; margin: 0; font-weight: 500; }

.system-status {
    background: rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    border: 1px solid #60A5FA;
    position: relative; z-index: 1;
    animation: statusGlow 2s ease-in-out infinite;
}

.status-content  { display: flex; align-items: center; justify-content: space-between; }
.status-left     { display: flex; align-items: center; gap: 0.5rem; }
.status-indicator{ width: 8px; height: 8px; background: #22c55e; border-radius: 50%; animation: pulse 2s infinite; box-shadow: 0 0 8px #60A5FA; }
.status-text     { font-size: 0.75rem; color: #fff; }

.nav { padding: 1.5rem 1rem; }

.section-title {
    font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.65);
    text-transform: uppercase; letter-spacing: 1.5px;
    padding: 1.5rem 1rem 0.75rem 1rem; margin-top: 1rem;
    display: flex; align-items: center; gap: 0.5rem; position: relative;
}

.section-title:first-child { margin-top: 0; }

.section-title::before {
    content: ''; width: 8px; height: 8px;
    background: var(--blue-600); border-radius: 50%;
    box-shadow: 0 0 12px var(--blue-600);
    animation: pulse 2s infinite;
}

.section-title::after {
    content: ''; flex: 1; height: 1px;
    background: linear-gradient(90deg, rgba(59,130,246,0.3), transparent);
    margin-left: 0.5rem;
}

.nav ul { list-style: none; display: flex; flex-direction: column; gap: 0.5rem; }
.nav ul li { position: relative; }

.nav ul li a {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.25rem; color: #EFF6FF; text-decoration: none;
    border-radius: 12px; transition: var(--transition);
    font-size: 0.9rem; font-weight: 500; position: relative;
    overflow: hidden; cursor: pointer; border: 1px solid transparent;
}

.nav ul li a::before {
    content: ''; position: absolute; left: 0; top: 0;
    height: 100%; width: 4px;
    background: linear-gradient(180deg, var(--blue-600), var(--blue-light));
    transform: scaleY(0); transition: var(--transition);
    box-shadow: 0 0 15px var(--blue-600);
}

.nav ul li a:hover {
    background: rgba(59,130,246,0.15); color: white;
    transform: translateX(8px); border-color: rgba(59,130,246,0.25);
    box-shadow: 0 6px 20px rgba(59,130,246,0.3), inset 0 1px 0 rgba(255,255,255,0.1);
}

.nav ul li a:hover::before        { transform: scaleY(1); }
.nav ul li a:hover .icon-wrapper  { background: rgba(59,130,246,0.25); transform: scale(1.15) rotate(8deg); box-shadow: 0 4px 12px rgba(59,130,246,0.4); }

.nav ul li a.active {
    background: rgba(59,130,246,0.25); color: white; font-weight: 600;
    border: 1px solid rgba(59,130,246,0.25);
    box-shadow: 0 4px 16px rgba(59,130,246,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
}

.nav ul li a.active::before       { transform: scaleY(1); }
.nav ul li a.active .icon-wrapper { background: rgba(59,130,246,0.3); box-shadow: 0 0 15px rgba(59,130,246,0.5); }

.left { display: flex; align-items: center; gap: 0.5rem; }
.icon { font-size: 1.3rem; transition: var(--transition); }

.icon-wrapper {
    width: 36px; height: 36px;
    background: rgba(59,130,246,0.15);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    transition: var(--transition);
}

.nav-label      { display: flex; flex-direction: column; gap: 0.15rem; }
.nav-label .main-text { font-size: 0.9rem; font-weight: 500; }
.nav-label .sub-text  { font-size: 0.7rem; opacity: 0.7; font-weight: 400; }

.sub-menu {
    max-height: 0; overflow: hidden;
    transition: max-height 0.5s cubic-bezier(0.4,0,0.2,1), opacity 0.3s ease;
    background: rgba(0,0,0,0.2); border-radius: 0 0 12px 12px;
    position: relative; opacity: 0; margin-top: 0.25rem;
}

.nav ul li.open .sub-menu { max-height: 600px; padding: 0.5rem 0 0.5rem 0.5rem; opacity: 1; }

.sub-menu::before {
    content: ''; position: absolute; left: 1.3rem; top: 0.5rem; bottom: 0.5rem;
    width: 2px;
    background: linear-gradient(180deg, var(--blue-600), rgba(59,130,246,0.1));
    border-radius: 2px;
}

.sub-menu li { position: relative; animation: slideIn 0.3s ease forwards; opacity: 0; }
.nav ul li.open .sub-menu li:nth-child(1) { animation-delay: 0.05s; }
.nav ul li.open .sub-menu li:nth-child(2) { animation-delay: 0.10s; }
.nav ul li.open .sub-menu li:nth-child(3) { animation-delay: 0.15s; }

.sub-menu li::before {
    content: ''; position: absolute; left: 1rem; top: 50%;
    width: 1rem; height: 2px;
    background: linear-gradient(90deg, rgba(59,130,246,0.5), rgba(59,130,246,0.2));
    pointer-events: none;
}

.sub-menu li::after { display: none; }

.sub-menu li a {
    padding: 0.65rem 0.75rem 0.65rem 2.5rem; font-size: 0.8rem; font-weight: 400;
    border-radius: 8px; margin: 0 0.5rem 0.25rem 1rem;
    display: flex; align-items: center; gap: 0.75rem; justify-content: flex-start;
}

.sub-menu-badge {
    margin-left: auto;
    min-width: 18px;
    height: 18px;
    padding: 0 6px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 800;
    line-height: 1;
    color: #a16207;
    background: #fef3c7;
    border: 1px solid #fcd34d;
}

.sub-menu li a:hover { transform: translateX(4px); background: rgba(59,130,246,0.2); }

.bx-chevron-down {
    font-size: 1.2rem;
    transition: transform 0.4s cubic-bezier(0.68,-0.55,0.265,1.55);
    color: var(--blue-600);
}

.nav ul li.open > a .bx-chevron-down { transform: rotate(180deg); filter: drop-shadow(0 0 6px var(--blue-600)); }

.sidebar-footer {
    margin: 1rem; padding: 0;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(59,130,246,0.25);
    border-radius: 16px; position: relative; overflow: hidden;
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}

.sidebar-footer::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--blue-500), var(--blue-600), var(--blue-light), var(--blue-500));
    background-size: 200% 100%;
    animation: gradientShift 3s linear infinite;
}

.footer-header {
    display: flex; align-items: center; gap: 1rem; padding: 1.25rem;
    background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(59,130,246,0.05));
}

.footer-icon {
    width: 48px; height: 48px;
    background: linear-gradient(135deg, var(--blue-500), var(--blue-700));
    border-radius: 14px; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 16px rgba(59,130,246,0.4); flex-shrink: 0;
}

.footer-icon i    { font-size: 1.5rem; color: #fff; }
.footer-content   { flex: 1; min-width: 0; }
.footer-title     { font-size: 1rem; font-weight: 700; color: #EFF6FF; margin-bottom: 0.25rem; }

.footer-subtitle {
    font-size: 0.75rem; color: #CBD5E1; font-weight: 500;
    display: flex; align-items: center; gap: 0.5rem;
}

.footer-subtitle::before {
    content: ''; width: 6px; height: 6px;
    background: var(--blue-500); border-radius: 50%; animation: pulse 2s infinite;
}

.footer-divider { height: 1px; background: linear-gradient(90deg, transparent, rgba(59,130,246,0.25), transparent); }

.footer-bottom {
    display: flex; justify-content: space-between; align-items: center;
    padding: 1rem 1.25rem; background: rgba(0,0,0,0.2);
}

.version {
    font-size: 0.75rem; color: rgba(255,255,255,0.65); font-weight: 700;
    background: rgba(59,130,246,0.15); padding: 0.35rem 0.75rem;
    border-radius: 8px; border: 1px solid rgba(59,130,246,0.3);
}

.status-online {
    display: flex; align-items: center; gap: 0.5rem;
    font-size: 0.75rem; color: var(--blue-500); font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px;
}

.status-online::before {
    content: ''; width: 8px; height: 8px;
    background: var(--blue-500); border-radius: 50%;
    animation: pulse-ring 2s infinite;
}

.navbar-container { max-width: 100%; padding: 0 2rem; height: 100%; overflow: visible; }

.navbar-content {
    display: flex; justify-content: space-between; align-items: center;
    height: 100%; overflow: visible;
}

.navbar-left  { display: flex; align-items: center; gap: 1.25rem; flex: 1; }
.navbar-right { display: flex; align-items: center; gap: 0.75rem; overflow: visible; }

.toggle-btn {
    background: transparent; border: 1px solid var(--border);
    width: 40px; height: 40px; border-radius: var(--r-sm);
    color: var(--text-600); font-size: 1.4rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: var(--transition);
}

.toggle-btn:hover { background: #EEF2FF; color: var(--navy); border-color: var(--blue-600); }

.icon-btn {
    background: transparent; border: none;
    width: 40px; height: 40px; border-radius: var(--r-sm);
    color: var(--text-600); font-size: 1.3rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: var(--transition); position: relative;
}

.icon-btn:hover { background: #EEF2FF; color: var(--navy); }

.badge-dot {
    position: absolute; top: 8px; right: 8px;
    width: 7px; height: 7px;
    background: #ef4444; border-radius: 50%; border: 2px solid var(--surface);
}

.time-display {
    display: flex; align-items: center; gap: 0.5rem;
    padding: 0.4rem 0.875rem; background: var(--bg);
    border: 1px solid var(--border); border-radius: 100px;
    color: var(--text-600); font-size: 0.85rem; font-weight: 500;
}

.date-separator { color: var(--text-400); font-size: 0.65rem; }

.profile-wrapper { position: relative; z-index: 1000; }

.profile-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: linear-gradient(135deg, var(--navy), var(--blue-600));
    color: #fff; font-size: 0.8rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; border: 2px solid rgba(255,255,255,0.3);
    box-shadow: 0 2px 10px rgba(15,36,108,0.3);
    transition: var(--transition); user-select: none;
}

.profile-avatar:hover { transform: scale(1.08); }

.profile-dropdown {
    position: absolute; top: calc(100% + 12px); right: 0;
    width: 250px; background: var(--surface);
    border: 1px solid var(--border); border-radius: var(--r-lg);
    box-shadow: 0 12px 40px rgba(15,36,108,0.14);
    z-index: 99999; opacity: 0; visibility: hidden;
    transform: translateY(-8px) scale(0.97);
    transition: all 0.22s cubic-bezier(0.4,0,0.2,1);
    overflow: hidden;
}

.profile-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0) scale(1); }

.dropdown-header {
    display: flex; align-items: center; gap: 12px; padding: 16px;
    background: linear-gradient(135deg, #EEF2FF, var(--surface));
}

.dropdown-avatar {
    width: 42px; height: 42px; border-radius: 50%;
    background: linear-gradient(135deg, var(--navy), var(--blue-600));
    color: #fff; font-size: 0.85rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

.dropdown-name  { font-size: 0.875rem; font-weight: 700; color: var(--text-900); }
.dropdown-email { font-size: 0.7rem; color: var(--text-400); margin-top: 1px; }

.dropdown-role {
    display: inline-block; margin-top: 4px; font-size: 0.65rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
    color: var(--blue-600); background: #EEF2FF;
    border: 1px solid rgba(59,130,246,0.2); padding: 2px 8px; border-radius: 20px;
}

.dropdown-divider { height: 1px; background: var(--border); }

.dropdown-item {
    display: flex; align-items: center; gap: 10px; padding: 10px 16px;
    font-size: 0.85rem; font-weight: 500; color: var(--text-900);
    text-decoration: none; transition: var(--transition);
}

.dropdown-item i           { font-size: 1.1rem; color: var(--text-400); transition: var(--transition); }
.dropdown-item:hover       { background: #EEF2FF; color: var(--blue-600); }
.dropdown-item:hover i     { color: var(--blue-600); }
.dropdown-logout           { color: #dc2626; }
.dropdown-logout i         { color: #dc2626; }
.dropdown-logout:hover     { background: #fff5f5; }

.page-header         { margin-bottom: 2rem; }
.page-header h2      { font-size: 1.6rem; font-weight: 700; color: var(--text-900); }
.page-header p       { color: var(--text-600); font-size: 0.9rem; margin-top: 0.3rem; }

.sec-hdr {
    font-size: 1rem; font-weight: 700; color: var(--text-900);
    margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;
}

.sec-hdr::before { content: ''; width: 4px; height: 18px; background: var(--blue-600); border-radius: 4px; }

.stats-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem; margin-bottom: 2rem;
}

.stat-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r-lg); padding: 1.5rem;
    display: flex; align-items: center; gap: 1rem;
    box-shadow: var(--shadow-sm); transition: var(--transition);
}

.stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }

.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
.stat-icon.blue  { background: #EEF2FF; color: var(--blue-600); }
.stat-icon.green { background: #f0fdf4; color: #16a34a; }
.stat-icon.amber { background: #fffbeb; color: #d97706; }
.stat-icon.red   { background: #fff5f5; color: #dc2626; }

.stat-info .label { font-size: 0.78rem; color: var(--text-400); font-weight: 500; }
.stat-info .value { font-size: 1.5rem; font-weight: 700; color: var(--text-900); line-height: 1.2; }
.stat-info .sub   { font-size: 0.75rem; color: #16a34a; font-weight: 500; margin-top: 2px; }

.table-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r-lg); box-shadow: var(--shadow-sm);
    overflow: hidden; margin-bottom: 2rem;
}

table               { width: 100%; border-collapse: collapse; }
thead tr            { background: var(--bg); border-bottom: 1px solid var(--border); }
th                  { padding: 0.875rem 1.25rem; font-size: 0.75rem; font-weight: 700; color: var(--text-400); text-transform: uppercase; letter-spacing: 0.05em; text-align: left; }
td                  { padding: 1rem 1.25rem; font-size: 0.875rem; color: var(--text-600); border-bottom: 1px solid var(--border); }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover      { background: #f8f9ff; }

.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 2rem; }

.info-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r-lg); box-shadow: var(--shadow-sm); padding: 1.5rem;
}

.activity-item { display: flex; align-items: flex-start; gap: 0.875rem; padding: 0.75rem 0; border-bottom: 1px solid var(--border); }
.activity-item:last-child { border-bottom: none; }
.activity-dot        { width: 10px; height: 10px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
.dot-blue            { background: var(--blue-500); }
.dot-green           { background: #22c55e; }
.dot-amber           { background: #f59e0b; }
.activity-text       { font-size: 0.85rem; color: var(--text-600); line-height: 1.4; }
.activity-text strong{ color: var(--text-900); }
.activity-time       { font-size: 0.72rem; color: var(--text-400); margin-top: 2px; }

.progress-item          { margin-bottom: 1.25rem; }
.progress-item:last-child { margin-bottom: 0; }
.progress-label { display: flex; justify-content: space-between; font-size: 0.82rem; color: var(--text-600); margin-bottom: 0.4rem; }
.progress-label span:last-child { font-weight: 700; color: var(--text-900); }
.progress-bar  { height: 8px; background: var(--bg); border-radius: 10px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 10px; background: linear-gradient(90deg, var(--blue-600), var(--blue-500)); }

.badge         { display: inline-block; padding: 0.25rem 0.65rem; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
.badge-paid    { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.badge-pending { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.badge-overdue { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; }

.overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.5);
    z-index: 998; display: none; opacity: 0; transition: opacity 0.3s;
}
.overlay.show { display: block; opacity: 1; }

/* ── User Management scoped styles ── */
#panel-userManagement { font-family: 'Plus Jakarta Sans', sans-serif; }
#panel-userManagement ::-webkit-scrollbar { width: 8px; height: 8px; }
#panel-userManagement ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
#panel-userManagement .table-row:hover { background-color: #f0f4ff; }
/* FIX #3: Scoped .badge class for user panel to not conflict with global .badge */
#panel-userManagement .badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
.badge-student    { background-color: #dbeafe; color: #1e40af; }
.badge-faculty    { background-color: #fef3c7; color: #92400e; }
.badge-admin      { background-color: #fce7f3; color: #9f1239; }
.badge-superadmin { background-color: #ede9fe; color: #6d28d9; }
.badge-registrar  { background-color: #e0e7ff; color: #3730a3; }
.badge-cashier    { background-color: #ffe4e6; color: #be123c; }
.badge-librarian  { background-color: #d1fae5; color: #065f46; }

.page-tab {
    transition: all 0.25s; color: #64748b; font-weight: 700;
    border-bottom: 3px solid transparent; background: none;
    border-top: none; border-left: none; border-right: none;
    padding-bottom: 4px; cursor: pointer; font-size: 0.875rem;
    text-transform: uppercase; letter-spacing: 0.05em;
    display: flex; align-items: center; gap: 0.5rem;
}
.page-tab.active { color: #0d2470; border-bottom-color: #1535a0; }
.page-tab:hover:not(.active) { color: #334155; }

.page-section { display: none; }
.page-section.active { display: block; animation: utFadeIn 0.3s ease; }

@keyframes utFadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.ut-modal {
    display: none; position: fixed; top: 0; left: 0;
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
    z-index: 2000; opacity: 0; transition: opacity 0.3s ease;
    align-items: center; justify-content: center; padding: 1rem;
}
.ut-modal.active { display: flex; opacity: 1; }
.ut-modal .modal-content { transform: scale(0.9); transition: transform 0.3s ease; }
.ut-modal.active .modal-content { transform: scale(1); }

.info-row { border-bottom: 1px solid #f1f5f9; padding: 0.75rem 0; }
.info-row:last-child { border-bottom: none; }

.glass-input {
    background: #ffffff; border: 1.5px solid #e2e8f0;
    transition: all 0.2s; border-radius: 0.75rem;
}
.glass-input:focus { border-color: #1535a0; box-shadow: 0 0 0 3px rgba(21,53,160,0.12); outline: none; }

.perm-tab-btn {
    transition: all 0.25s; color: #94a3b8; font-weight: 700;
    font-size: 0.7rem; border: none; background: none; cursor: pointer;
    padding: 0.5rem 1.25rem; border-radius: 0.5rem;
}
.perm-tab-btn.active { background: linear-gradient(135deg,#0d2470,#1535a0); color: white !important; box-shadow: 0 4px 12px rgba(13,36,112,0.3); }

.perm-view { display: none; }
.perm-view.active { display: block; animation: utFadeIn 0.3s ease; }

.role-card { transition: all 0.2s; cursor: pointer; border: 2px solid transparent; }
.role-card:hover { border-color: #cbd5e1; background-color: #f8fafc; }
.role-card.active { border-color: #1535a0; background-color: #eef2ff; }
.role-card.active .role-card-icon { background: linear-gradient(135deg,#0d2470,#1535a0); color: white; }
.role-card.active .role-card-name { color: #0d2470; }

/* ── Provision tab inner styles ── */
.prov-tab-btn {
    padding: 0.6rem 1.4rem; font-size: 0.75rem; font-weight: 800;
    font-family: 'Barlow Condensed', sans-serif; letter-spacing: 0.08em;
    text-transform: uppercase; border: none; background: transparent;
    cursor: pointer; color: #94a3b8; border-bottom: 3px solid transparent;
    transition: all 0.2s; display: flex; align-items: center; gap: 0.4rem;
}
.prov-tab-btn.active { color: #0d2470; border-bottom-color: #1535a0; }
.prov-tab-btn:hover:not(.active) { color: #475569; }
.prov-tab-section { display: none; }
.prov-tab-section.active { display: block; }

.btn-create-all {
    padding: 0.75rem 1.75rem;
    background: linear-gradient(135deg,#0d2470,#1535a0);
    color: white; border: none; border-radius: 0.6rem;
    font-size: 0.78rem; font-weight: 900;
    font-family: 'Barlow Condensed', sans-serif; cursor: pointer;
    transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.1em;
    box-shadow: 0 6px 20px rgba(13,36,112,0.3);
    display: flex; align-items: center; gap: 0.5rem;
}
.btn-create-all:hover { background: linear-gradient(135deg,#1535a0,#1d4ed8); transform: translateY(-1px); }
.btn-create-all:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.prov-tbl-row { transition: background 0.15s; border-bottom: 1px solid #f1f5f9; }
.prov-tbl-row:hover { background: #f0f4ff; }
.prov-tbl-row:last-child { border-bottom: none; }

.prov-btn-create { padding: 0.45rem 1rem; background: linear-gradient(135deg,#0d2470,#1535a0); color: white; border: none; border-radius: 0.45rem; font-size: 0.7rem; font-weight: 800; font-family: inherit; cursor: pointer; transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; box-shadow: 0 4px 12px rgba(13,36,112,0.25); }
.prov-btn-create:hover { background: linear-gradient(135deg,#1535a0,#1d4ed8); transform: translateY(-1px); }
.prov-btn-create:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
.prov-btn-approve { padding: 0.4rem 0.9rem; background: linear-gradient(135deg,#15803d,#16a34a); color: white; border: none; border-radius: 0.45rem; font-size: 0.7rem; font-weight: 800; font-family: inherit; cursor: pointer; transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; box-shadow: 0 4px 12px rgba(21,128,61,0.25); margin-right: 0.35rem; }
.prov-btn-approve:hover { background: linear-gradient(135deg,#16a34a,#22c55e); transform: translateY(-1px); }
.prov-btn-approve:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
.prov-btn-reject { padding: 0.4rem 0.9rem; background: white; color: #dc2626; border: 1.5px solid #fca5a5; border-radius: 0.45rem; font-size: 0.7rem; font-weight: 800; font-family: inherit; cursor: pointer; transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; }
.prov-btn-reject:hover { background: #fee2e2; }
.prov-cred-item { display:flex; align-items:center; gap:0.875rem; background:rgba(255,255,255,0.02); border:1px solid rgba(59,130,246,0.1); border-radius:0.75rem; padding:0.75rem 1rem; margin-bottom:0.5rem; transition:background 0.15s; }
.prov-cred-item:hover { background:rgba(59,130,246,0.06); }
.prov-copy-btn { background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.2); border-radius:0.4rem; color:#3b82f6; padding:4px 10px; font-size:9.5px; font-weight:700; cursor:pointer; transition:all 0.15s; flex-shrink:0; font-family:inherit; }
.prov-copy-btn:hover { background:rgba(59,130,246,0.18); color:#60a5fa; }
.prov-copy-btn.copied { color:#22c55e; border-color:rgba(34,197,94,0.3); background:rgba(34,197,94,0.08); }

.perm-row { transition: background 0.15s; }
.perm-row:hover { background-color: #f8fafc; }

.perm-toggle { position: relative; display: inline-block; width: 40px; height: 22px; flex-shrink: 0; }
.perm-toggle input { opacity: 0; width: 0; height: 0; }
.perm-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: 0.25s; border-radius: 22px; }
.perm-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: 0.25s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
.perm-toggle input:checked + .perm-slider { background: linear-gradient(135deg,#1535a0,#1d4ed8); }
.perm-toggle input:checked + .perm-slider:before { transform: translateX(18px); }

.module-header { background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-left: 3px solid; }
.audit-badge-success { background: #dcfce7; color: #15803d; }
.audit-badge-blocked { background: #fee2e2; color: #dc2626; }
.audit-badge-warning { background: #fef9c3; color: #a16207; }
.audit-toggle-icon { transition: transform 0.2s; }
tr.is-open .audit-toggle-icon { transform: rotate(90deg); }

.role-pill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; }
.role-pill-superadmin { background: #ede9fe; color: #6d28d9; }
.role-pill-registrar  { background: #e0f2fe; color: #0369a1; }
.role-pill-faculty    { background: #fef3c7; color: #92400e; }
.role-pill-student    { background: #dbeafe; color: #1e40af; }
.role-pill-cashier    { background: #fce7f3; color: #9f1239; }
.role-pill-librarian  { background: #d1fae5; color: #065f46; }

@keyframes rotate    { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes logoFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
@keyframes pulse     { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.6; transform: scale(0.95); } }
@keyframes pulse-ring{ 0% { box-shadow: 0 0 0 0 rgba(59,130,246,0.7); } 70% { box-shadow: 0 0 0 10px rgba(59,130,246,0); } 100% { box-shadow: 0 0 0 0 rgba(59,130,246,0); } }
@keyframes statusGlow{ 0%,100% { box-shadow: 0 0 20px rgba(59,130,246,0.3), inset 0 0 10px rgba(59,130,246,0.1); } 50% { box-shadow: 0 0 30px rgba(59,130,246,0.5), inset 0 0 20px rgba(59,130,246,0.2); } }
@keyframes gradientShift { 0% { background-position: 0% 50%; } 100% { background-position: 200% 50%; } }
@keyframes slideIn   { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }

@media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }
@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.show { transform: translateX(0); }
    .page-wrapper { margin-left: 0; }
    .navbar-container { padding: 0 1rem; }
    .main-content { padding: 1rem; }
}
/* ── User Management View Dropdown ── */
.um-dropdown-wrapper {
    position: relative;
    display: inline-block;
}
.um-dropdown-btn {
    display: flex; align-items: center; gap: 0.65rem;
    padding: 0.6rem 1rem 0.6rem 0.75rem;
    background: white;
    border: 1.5px solid rgba(21,53,160,0.18);
    border-radius: 0.75rem;
    cursor: pointer;
    font-size: 0.875rem; font-weight: 700; color: #0d2470;
    font-family: 'Plus Jakarta Sans', sans-serif;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(13,36,112,0.07);
    min-width: 220px;
}
.um-dropdown-btn:hover {
    border-color: #1535a0;
    box-shadow: 0 4px 14px rgba(13,36,112,0.13);
}
.um-dropdown-btn.open {
    border-color: #1535a0;
    box-shadow: 0 4px 14px rgba(13,36,112,0.13);
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}
.um-dropdown-icon {
    width: 28px; height: 28px; border-radius: 7px;
    background: #eef2ff; color: #1535a0;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem; flex-shrink: 0; transition: all 0.2s;
}
.um-dropdown-chevron {
    font-size: 0.7rem; color: #94a3b8; margin-left: auto;
    transition: transform 0.25s cubic-bezier(0.4,0,0.2,1);
}
.um-dropdown-btn.open .um-dropdown-chevron { transform: rotate(180deg); color: #1535a0; }

.um-dropdown-menu {
    display: none;
    position: absolute;
    top: 100%; left: 0;
    min-width: 100%;
    background: white;
    border: 1.5px solid #1535a0;
    border-top: none;
    border-bottom-left-radius: 0.75rem;
    border-bottom-right-radius: 0.75rem;
    box-shadow: 0 12px 32px rgba(13,36,112,0.15);
    z-index: 500;
    overflow: hidden;
    animation: umDropIn 0.18s ease;
}
.um-dropdown-menu.open { display: block; }

@keyframes umDropIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.um-dropdown-item {
    display: flex; align-items: center; gap: 0.75rem;
    width: 100%; padding: 0.75rem 1rem;
    background: none; border: none; cursor: pointer;
    text-align: left; font-family: 'Plus Jakarta Sans', sans-serif;
    transition: background 0.15s;
    border-bottom: 1px solid rgba(21,53,160,0.07);
}
.um-dropdown-item:last-child { border-bottom: none; }
.um-dropdown-item:hover { background: #f5f8ff; }
.um-dropdown-item.active { background: #eef2ff; }

.um-dropdown-item-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem; flex-shrink: 0;
}
.um-dropdown-item-text {
    display: flex; flex-direction: column; gap: 1px;
}
.um-dropdown-item-label {
    font-size: 0.82rem; font-weight: 700; color: #0d2470;
}
.um-dropdown-item-sub {
    font-size: 0.68rem; color: #94a3b8; font-weight: 500;
}
.um-dropdown-item.active .um-dropdown-item-label { color: #1535a0; }

/* ── Student Registration Panel ── */
.reg-input { background: #ffffff; border: 1.5px solid #dce4f0; transition: all 0.2s; border-radius: 0.6rem; }
.reg-input:focus { border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29,78,216,0.12); outline: none; }
@keyframes modalPop { from { opacity:0; transform:scale(0.88) translateY(30px); } to { opacity:1; transform:scale(1) translateY(0); } }

    </style>
</head>
<body>

<div class="overlay" id="overlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand">
            <div class="logo"><i class='bx bx-dollar-circle'></i></div>
            <div class="brand-info">
                <h1>PAYMENT & ACCOUNTING</h1>
                <p>Student Fees Management</p>
            </div>
        </div>
        <div class="system-status">
            <div class="status-content">
                <div class="status-left">
                    <span class="status-indicator"></span>
                    <span class="status-text">Online &amp; operational</span>
                </div>
            </div>
        </div>
    </div>
    <nav class="nav">
        <span class="section-title">Dashboard</span>
        <ul>
            <li><a href="#" class="active" onclick="showPanel('dashboard', this); return false;"><span class="left"><div class="icon-wrapper"><i class='bx bx-tachometer icon'></i></div><span class="nav-label"><span class="main-text">Dashboard</span><span class="sub-text">Overview &amp; Stats</span></span></span></a></li>
        </ul>
        <span class="section-title">Operations</span>
        <ul>
            <li><a href="#"><span class="left"><div class="icon-wrapper"><i class='bx bx-calculator icon'></i></div><span class="nav-label"><span class="main-text">Assessment of Fees</span><span class="sub-text">Calculate &amp; review student fees</span></span></span></a></li>
            <li><a href="#"><span class="left"><div class="icon-wrapper"><i class='bx bx-check-shield icon'></i></div><span class="nav-label"><span class="main-text">Payment Posting &amp; Validation</span><span class="sub-text">Record and verify all payments</span></span></span></a></li>
            <li><a href="#"><span class="left"><div class="icon-wrapper"><i class='bx bx-receipt icon'></i></div><span class="nav-label"><span class="main-text">Billing &amp; Statement of Account</span><span class="sub-text">Generate bills &amp; statements</span></span></span></a></li>
            <li><a href="#"><span class="left"><div class="icon-wrapper"><i class='bx bx-award icon'></i></div><span class="nav-label"><span class="main-text">Scholarships</span><span class="sub-text">Manage student scholarships</span></span></span></a></li>
            <li><a href="#"><span class="left"><div class="icon-wrapper"><i class='bx bx-list-ul icon'></i></div><span class="nav-label"><span class="main-text">Financial Transaction Log</span><span class="sub-text">Track all financial transactions</span></span></span></a></li>
        </ul>
        <span class="section-title">Enrollment</span>
        <ul>
            <li><a href="#" onclick="showPanel('studentRegistration', this); return false;"><span class="left"><div class="icon-wrapper"><i class='bx bx-user-plus icon'></i></div><span class="nav-label"><span class="main-text">User Registration</span><span class="sub-text">Create new user accounts</span></span></span></a></li>
        </ul>
        <span class="section-title">Administration</span>
        <ul>
            <li>
                <a onclick="toggleSubmenu(this)">
                    <span class="left"><div class="icon-wrapper"><i class='bx bx-group icon'></i></div><span class="nav-label"><span class="main-text">User Management</span><span class="sub-text">Accounts &amp; permissions</span></span></span>
                    <i class='bx bx-chevron-down'></i>
                </a>
                <ul class="sub-menu">
                    <li><a href="#" onclick="showPanel('userManagement', this); switchPageTab('users'); return false;"><span class="left"><i class='bx bx-table icon'></i><span>Users Table</span></span></a></li>
                    <li><a href="#" onclick="showPanel('userManagement', this); switchPageTab('permissions'); return false;"><span class="left"><i class='bx bx-shield-alt-2 icon'></i><span>Roles &amp; Permissions</span></span></a></li>
                    <li><a href="#" onclick="showPanel('userManagement', this); switchPageTab('provision'); return false;"><span class="left"><i class='bx bx-key icon'></i><span>Provision</span></span><span id="provisionTabBadge" class="sub-menu-badge" style="display:none;">0</span></a></li>
                </ul>
            </li>
        </ul>
        <span class="section-title">Settings</span>
        <ul>
            <li>
                <a onclick="toggleSubmenu(this)">
                    <span class="left"><div class="icon-wrapper"><i class='bx bx-cog icon'></i></div><span class="nav-label"><span class="main-text">System Settings</span><span class="sub-text">Configuration</span></span></span>
                    <i class='bx bx-chevron-down'></i>
                </a>
                <ul class="sub-menu">
                    <li><a href="#"><span class="left"><i class='bx bx-shield icon'></i><span>Settings</span></span></a></li>
                </ul>
            </li>
        </ul>
        <div class="sidebar-footer">
            <div class="footer-header">
                <div class="footer-icon"><i class='bx bx-shield-alt-2'></i></div>
                <div class="footer-content">
                    <div class="footer-title">Secure Platform</div>
                    <div class="footer-subtitle">All systems operational</div>
                </div>
            </div>
            <div class="footer-divider"></div>
            <div class="footer-bottom">
                <span class="version">System</span>
                <div class="status-online">Online</div>
            </div>
        </div>
    </nav>
</aside>

<div class="page-wrapper" id="pageWrapper">

    <nav class="top-navbar" id="topNavbar">
        <div class="navbar-container">
            <!-- FIX #4: Corrected unclosed <div class="navbar-content"> — was missing closing </div> before </nav> -->
            <div class="navbar-content">
                <div class="navbar-left">
                    <button class="toggle-btn" id="toggleBtn" aria-label="Toggle sidebar"><i class='bx bx-menu'></i></button>
                </div>
                <div class="navbar-right">
                    <div class="time-display">
                        <span id="currentTime"></span>
                        <span class="date-separator">•</span>
                        <span id="currentDate"></span>
                    </div>
                    <button class="icon-btn" title="Search"><i class='bx bx-search'></i></button>
                    <button class="icon-btn" title="Notifications"><i class='bx bx-bell'></i><span class="badge-dot"></span></button>
                    <div class="profile-wrapper" id="profileWrapper">
                        <div class="profile-avatar" id="profileBtn">AU</div>
                        <div class="profile-dropdown" id="profileDropdown">
                            <div class="dropdown-header">
                                <div class="dropdown-avatar" id="dropdownAvatar">AU</div>
                                <div>
                                    <div class="dropdown-name"><?php echo htmlspecialchars($_SESSION["full_name"]); ?></div>
                                    <div class="dropdown-email"><?php echo htmlspecialchars($_SESSION["email"]); ?></div>
                                    <div class="dropdown-role"><?php echo htmlspecialchars($_SESSION["role"]); ?></div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item"><i class='bx bx-user'></i><span>My Profile</span></a>
                            <a href="#" class="dropdown-item"><i class='bx bx-cog'></i><span>Settings</span></a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item dropdown-logout"><i class='bx bx-log-out'></i><span>Log Out</span></a>
                        </div>
                    </div>
                </div>
            </div><!-- /navbar-content -->
        </div><!-- /navbar-container -->
    </nav>

    <div class="main-content" id="mainContent">

        <!-- Dashboard Panel (default visible) -->
        <div id="panel-dashboard" class="content-panel">
            <div class="page-header">
                <h2>Dashboard</h2>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>.</p>
            </div>
        </div>

        <!-- Student Registration Panel (hidden by default) -->
        <div id="panel-studentRegistration" class="content-panel" style="display:none;">
            <div class="w-full bg-white overflow-hidden flex flex-col lg:flex-row" style="border-radius:1.25rem;box-shadow:0 32px 80px rgba(13,36,112,0.18),0 2px 8px rgba(13,36,112,0.08);border:1px solid rgba(29,78,216,0.1);min-height:82vh;">

                <!-- LEFT: FORM -->
                <div class="lg:w-2/3 flex flex-col bg-white" style="border-radius:1.25rem 0 0 1.25rem;">

                    <!-- Header -->
                    <div class="p-8 border-b" style="background:#ffffff;border-color:#e0e8f8;border-bottom:2px solid #1535a0;border-radius:1.25rem 0 0 0;">
                        <div class="flex justify-between items-center mb-6">
                            <div class="flex items-center gap-4">
                                <img src="../siems.png" alt="BCP" class="h-12 w-12 object-contain">
                                <div>
                                    <h2 id="reg_formTitle" style="font-family:'Barlow Condensed',sans-serif;font-size:1.75rem;font-weight:900;color:#0d2470;letter-spacing:-0.01em;line-height:1;margin-bottom:4px;">USER REGISTRATION</h2>
                                    <p style="color:#1535a0;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:0.2em;">SIEMS — Bestlink College of the Philippines</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Enrollment Portal</span>
                                <span style="font-size:0.7rem;font-weight:800;color:#0d2470;background:#eef2ff;border:1px solid rgba(21,53,160,0.2);padding:4px 14px;border-radius:999px;">AY 2026-2027</span>
                            </div>
                        </div>
                        <!-- Role Selector Tabs -->
                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;" id="reg_roleTabs">
                            <button onclick="reg_switchRole('student',this)" class="reg-role-tab reg-role-tab-active" data-role="student">
                                <i class="fas fa-user-graduate mr-1.5"></i>Student
                            </button>
                            <button onclick="reg_switchRole('faculty',this)" class="reg-role-tab" data-role="faculty">
                                <i class="fas fa-chalkboard-teacher mr-1.5"></i>Faculty
                            </button>
                            <button onclick="reg_switchRole('admin',this)" class="reg-role-tab" data-role="admin">
                                <i class="fas fa-user-shield mr-1.5"></i>Admin
                            </button>
                            <button onclick="reg_switchRole('registrar',this)" class="reg-role-tab" data-role="registrar">
                                <i class="fas fa-file-invoice mr-1.5"></i>Registrar
                            </button>
                            <button onclick="reg_switchRole('cashier',this)" class="reg-role-tab" data-role="cashier">
                                <i class="fas fa-cash-register mr-1.5"></i>Cashier
                            </button>
                            <button onclick="reg_switchRole('librarian',this)" class="reg-role-tab" data-role="librarian">
                                <i class="fas fa-book mr-1.5"></i>Librarian
                            </button>
                        </div>
                        <style>
                            .reg-role-tab {
                                padding: 0.45rem 1.1rem;
                                border-radius: 999px;
                                font-size: 0.75rem;
                                font-weight: 700;
                                font-family: inherit;
                                cursor: pointer;
                                border: 1.5px solid #c7d2fe;
                                color: #3b4eac;
                                background: #f0f4ff;
                                transition: all 0.18s;
                            }
                            .reg-role-tab:hover { background: #e0e7ff; border-color: #818cf8; }
                            .reg-role-tab-active {
                                background: linear-gradient(135deg,#0d2470,#1535a0) !important;
                                color: #fff !important;
                                border-color: #1535a0 !important;
                                box-shadow: 0 4px 14px rgba(13,36,112,0.25);
                            }
                        </style>
                    </div>

                    <!-- Form Content -->
                    <div class="p-10 overflow-y-auto flex-grow space-y-10">

                        <!-- 01: PERSONAL INFO -->
                        <section class="space-y-4">
                            <div class="section-header">
                                <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:0.85rem;font-weight:800;color:#0d2470;text-transform:uppercase;letter-spacing:0.12em;">01. Personal Information</h3>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">First Name <span class="text-red-400">*</span></label>
                                    <input type="text" id="reg_firstName" placeholder="Juan" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-semibold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Middle Name</label>
                                    <input type="text" id="reg_middleName" placeholder="Protacio" class="w-full reg-input px-4 py-3 rounded-xl text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Last Name <span class="text-red-400">*</span></label>
                                    <input type="text" id="reg_lastName" placeholder="Del Mundo" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-semibold">
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Birth Date <span class="text-red-400">*</span></label>
                                    <input type="date" id="reg_birthDate" class="w-full reg-input px-4 py-3 rounded-xl text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Gender <span class="text-red-400">*</span></label>
                                    <select id="reg_gender" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-semibold bg-white">
                                        <option>Male</option>
                                        <option>Female</option>
                                    </select>
                                </div>
                                <div></div>
                            </div>
                        </section>

                        <!-- 02: CONTACT -->
                        <section class="space-y-4">
                            <div class="section-header">
                                <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:0.85rem;font-weight:800;color:#0d2470;text-transform:uppercase;letter-spacing:0.12em;">02. Contact Details</h3>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Mobile Number <span class="text-red-400">*</span></label>
                                    <input type="tel" id="reg_mobileNumber" placeholder="09XX-XXX-XXXX" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-semibold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Personal Email <span class="text-red-400">*</span></label>
                                    <input type="email" id="reg_personalEmail" placeholder="juan@gmail.com" class="w-full reg-input px-4 py-3 rounded-xl text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Street Address</label>
                                    <input type="text" id="reg_streetAddress" placeholder="123 Rizal Street" class="w-full reg-input px-4 py-3 rounded-xl text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">City / Municipality</label>
                                    <input type="text" id="reg_city" placeholder="Caloocan City" class="w-full reg-input px-4 py-3 rounded-xl text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Province</label>
                                    <input type="text" id="reg_province" placeholder="Metro Manila" class="w-full reg-input px-4 py-3 rounded-xl text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">ZIP Code</label>
                                    <input type="text" id="reg_zipCode" placeholder="1400" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-mono">
                                </div>
                            </div>
                        </section>

                        <!-- 03: STUDENT — Academic Placement -->
                        <section id="reg_section_academic" class="space-y-4">
                            <div class="section-header">
                                <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:0.85rem;font-weight:800;color:#0d2470;text-transform:uppercase;letter-spacing:0.12em;">03. Academic Placement</h3>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Program / Course <span class="text-red-400">*</span></label>
                                    <select id="reg_studentProgram" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
                                        <option value="" disabled selected>Select Program</option>
                                        <optgroup label="College of Computer Studies">
                                            <option>BS Information Technology</option>
                                            <option>BS Computer Science</option>
                                        </optgroup>
                                        <optgroup label="College of Business &amp; Accountancy">
                                            <option>BS Accountancy</option>
                                            <option>BS Business Administration</option>
                                            <option>BS Marketing Management</option>
                                        </optgroup>
                                        <optgroup label="College of Education">
                                            <option>Bachelor of Elementary Education</option>
                                            <option>Bachelor of Secondary Education</option>
                                        </optgroup>
                                        <optgroup label="College of Criminology">
                                            <option>BS Criminology</option>
                                        </optgroup>
                                        <optgroup label="College of Hospitality Management">
                                            <option>BS Hospitality Management</option>
                                            <option>BS Tourism Management</option>
                                        </optgroup>
                                        <optgroup label="College of Engineering">
                                            <option>BS Civil Engineering</option>
                                            <option>BS Electrical Engineering</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Year Level <span class="text-red-400">*</span></label>
                                    <select id="reg_studentYearLevel" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
                                        <option>1st Year</option>
                                        <option>2nd Year</option>
                                        <option>3rd Year</option>
                                        <option>4th Year</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Major / Specialization</label>
                                    <input type="text" id="reg_major" placeholder="e.g., Network Technology" class="w-full reg-input px-4 py-3 rounded-xl text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Enrollment Status</label>
                                    <select id="reg_enrollmentStatus" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
                                        <option>Regular</option>
                                        <option>Irregular</option>
                                        <option>Transferee</option>
                                        <option>Returnee</option>
                                        <option>Freshmen</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Student Status</label>
                                    <select id="reg_studentLifeStatus" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
                                        <option value="Active">Active</option>
                                        <option value="Alumni">Alumni</option>
                                        <option value="Dropped">Dropped</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <!-- 03: STAFF — Employment Information (hidden by default) -->
                        <section id="reg_section_employment" class="space-y-4" style="display:none;">
                            <div class="section-header">
                                <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:0.85rem;font-weight:800;color:#0d2470;text-transform:uppercase;letter-spacing:0.12em;">03. Employment Information</h3>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Department <span class="text-red-400">*</span></label>
                                    <select id="reg_department" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
                                        <option value="" disabled selected>Select Department</option>
                                        <option>College of Computer Studies</option>
                                        <option>College of Business &amp; Accountancy</option>
                                        <option>College of Education</option>
                                        <option>College of Criminology</option>
                                        <option>College of Hospitality Management</option>
                                        <option>College of Engineering</option>
                                        <option>Registrar's Office</option>
                                        <option>Finance &amp; Cashier's Office</option>
                                        <option>Library Services</option>
                                        <option>Administration Office</option>
                                        <option>IT Department</option>
                                        <option>Human Resources</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Employment Type</label>
                                    <select id="reg_employmentType" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
                                        <option>Permanent</option>
                                        <option>Temporary</option>
                                        <option>Contractual</option>
                                        <option>Job Order</option>
                                        <option>Full-time</option>
                                        <option>Part-time</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Staff Status</label>
                                    <select id="reg_staffLifeStatus" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
                                        <option value="Active">Active</option>
                                        <option value="Terminated">Terminated</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Access Level — only show for Admin -->
                            <div id="reg_accessLevelRow" class="grid grid-cols-2 gap-4" style="display:none;">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Access Level</label>
                                    <select id="reg_accessLevel" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
                                        <option>Standard</option>
                                        <option>Elevated</option>
                                        <option>Full</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <!-- 04: STUDENT — Guardian -->
                        <section id="reg_section_guardian" class="space-y-4">
                            <div class="section-header">
                                <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:0.85rem;font-weight:800;color:#0d2470;text-transform:uppercase;letter-spacing:0.12em;">04. Guardian / Emergency Contact</h3>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Guardian Full Name <span class="text-red-400">*</span></label>
                                    <input type="text" id="reg_guardianName" placeholder="Maria Del Mundo" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-semibold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Relationship <span class="text-red-400">*</span></label>
                                    <select id="reg_guardianRelationship" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
                                        <option>Mother</option>
                                        <option>Father</option>
                                        <option>Guardian</option>
                                        <option>Sibling</option>
                                        <option>Spouse</option>
                                        <option>Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Contact Number <span class="text-red-400">*</span></label>
                                    <input type="tel" id="reg_guardianContact" placeholder="09XX-XXX-XXXX" class="w-full reg-input px-4 py-3 rounded-xl text-sm font-semibold">
                                </div>
                            </div>
                        </section>

                        <!-- 05: SUPPORTING DOCUMENTS (File Upload) -->
                        <section class="space-y-4">
                            <div class="section-header">
                                <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:0.85rem;font-weight:800;color:#0d2470;text-transform:uppercase;letter-spacing:0.12em;">05. Supporting Documents</h3>
                            </div>
                            <div id="reg_dropzone" class="w-full border-2 border-dashed border-blue-300 rounded-xl p-8 text-center cursor-pointer transition bg-blue-50 hover:bg-blue-100"
                                 ondrop="reg_handleDrop(event)" ondragover="event.preventDefault(); event.target.style.backgroundColor='#dbeafe';" ondragleave="event.target.style.backgroundColor='#eff6ff';">
                                <i class="fas fa-cloud-upload-alt text-4xl text-blue-400 mb-3 block"></i>
                                <h4 class="text-base font-bold text-blue-600 mb-1">Drag & Drop Files Here</h4>
                                <p class="text-xs text-blue-500 mb-4">Or click to browse (PDF, DOC, DOCX, XLS, XLSX, PNG, JPG, GIF - max 10MB)</p>
                                <input type="file" id="reg_fileInput" multiple style="display:none;" onchange="reg_handleFiles(event.target.files);">
                                <button type="button" onclick="document.getElementById('reg_fileInput').click();" 
                                    class="px-4 py-2 bg-blue-500 text-white rounded-lg text-sm font-semibold hover:bg-blue-600 transition">
                                    Select Files
                                </button>
                            </div>
                            <div id="reg_uploadedFilesList" class="space-y-2"></div>
                        </section>

                    </div><!-- /form content -->

                    <!-- Footer -->
                    <div class="p-8 flex justify-between items-center" style="border-top:2px solid #e0e8f8;background:#f5f8ff;border-radius:0 0 0 1.25rem;">
                        <div class="flex items-center gap-2 text-slate-400 text-xs">
                            <i class="fas fa-info-circle text-blue-400"></i>
                            <span>Fields marked <span class="text-red-400 font-bold">*</span> are required. No account is created at this step.</span>
                        </div>
                        <div class="flex gap-4">
                            <button onclick="reg_resetForm()" class="px-8 py-3 text-sm font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition">Reset Form</button>
                            <button id="reg_submitBtn" onclick="reg_submitRegistration()" style="padding:0.875rem 3rem;background:linear-gradient(135deg,#0d2470,#1535a0);color:white;border-radius:0.6rem;font-size:0.8rem;font-weight:900;font-family:'Barlow Condensed',sans-serif;letter-spacing:0.12em;text-transform:uppercase;box-shadow:0 8px 24px rgba(13,36,112,0.35);border:none;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='linear-gradient(135deg,#1535a0,#1d4ed8)'" onmouseout="this.style.background='linear-gradient(135deg,#0d2470,#1535a0)'">
                                Submit Registration
                            </button>
                        </div>
                    </div>
                </div><!-- /left form -->

                <!-- RIGHT: INFO PANEL -->
                <div class="lg:w-1/3 text-white flex flex-col justify-between relative overflow-hidden" style="background:linear-gradient(150deg,#0d2470 0%,#1535a0 40%,#1a3fb5 70%,#0d2470 100%);border-radius:0 1.25rem 1.25rem 0;">
                    <div class="absolute top-0 left-0 w-full h-full pointer-events-none overflow-hidden">
                        <div style="position:absolute;top:-80px;right:-80px;width:300px;height:300px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>
                        <div style="position:absolute;bottom:-60px;left:-40px;width:220px;height:220px;background:rgba(255,255,255,0.04);border-radius:50%;"></div>
                        <div style="position:absolute;top:40%;left:50%;width:180px;height:180px;background:rgba(96,165,250,0.06);border-radius:50%;transform:translate(-50%,-50%);"></div>
                    </div>
                    <div class="relative z-10 flex flex-col gap-6 flex-grow justify-center p-8">
                        <h2 style="font-family:'Barlow Condensed',sans-serif;font-size:2.5rem;font-weight:900;letter-spacing:-0.01em;line-height:1;color:white;">REGISTRATION<br>FLOW</h2>
                        <div class="space-y-3">
                            <div style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.22);border-radius:0.875rem;padding:1rem 1.25rem;display:flex;align-items:flex-start;gap:0.875rem;">
                                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(34,197,94,0.4);"><span style="font-size:11px;font-weight:900;color:white;">1</span></div>
                                <div>
                                    <p style="font-size:10px;font-weight:900;color:#22c55e;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:2px;">You Are Here</p>
                                    <p id="reg_flowStep1Label" style="font-size:0.85rem;font-weight:700;color:white;line-height:1.3;">User Registration</p>
                                    <p style="font-size:10px;color:rgba(255,255,255,0.65);margin-top:3px;">Submit personal &amp; role-specific info. Status: <span style="color:#fbbf24;font-weight:700;">pending</span>. No credentials yet.</p>
                                </div>
                            </div>
                            <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:0.875rem;padding:1rem 1.25rem;display:flex;align-items:flex-start;gap:0.875rem;opacity:0.65;">
                                <div style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><span style="font-size:11px;font-weight:900;color:rgba(255,255,255,0.6);">2</span></div>
                                <div>
                                    <p style="font-size:10px;font-weight:900;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:2px;">Admin Dashboard</p>
                                    <p style="font-size:0.85rem;font-weight:700;color:rgba(255,255,255,0.75);line-height:1.3;">Account Provisioning</p>
                                    <p style="font-size:10px;color:rgba(255,255,255,0.45);margin-top:3px;">Admin reviews the submission &amp; generates institutional email + password.</p>
                                </div>
                            </div>
                            <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:0.875rem;padding:1rem 1.25rem;display:flex;align-items:flex-start;gap:0.875rem;opacity:0.4;">
                                <div style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><span style="font-size:11px;font-weight:900;color:rgba(255,255,255,0.5);">3</span></div>
                                <div>
                                    <p style="font-size:10px;font-weight:900;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:2px;">New User</p>
                                    <p style="font-size:0.85rem;font-weight:700;color:rgba(255,255,255,0.5);line-height:1.3;">First Login &amp; Password Change</p>
                                    <p style="font-size:10px;color:rgba(255,255,255,0.3);margin-top:3px;">User logs in with generated credentials and sets a permanent password.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="relative z-10 p-8 pt-0 space-y-2.5">
                        <div style="padding:0.875rem;background:rgba(255,255,255,0.06);border-radius:0.75rem;border:1px solid rgba(255,255,255,0.1);font-size:10px;color:rgba(255,255,255,0.7);line-height:1.6;display:flex;align-items:flex-start;gap:0.5rem;">
                            <i class="fas fa-clock text-yellow-400 mt-0.5 text-xs flex-shrink-0"></i>
                            <span>After submission, await admin approval. You will receive login credentials via personal email once your account is provisioned.</span>
                        </div>
                        <div style="padding:0.875rem;background:rgba(255,255,255,0.04);border-radius:0.75rem;border:1px solid rgba(255,255,255,0.08);font-size:10px;color:rgba(255,255,255,0.6);line-height:1.6;display:flex;align-items:flex-start;gap:0.5rem;">
                            <i class="fas fa-shield-alt text-emerald-400 mt-0.5 text-xs flex-shrink-0"></i>
                            <span>No login credentials are issued at this step. Data is securely stored pending admin review.</span>
                        </div>
                    </div>
                </div><!-- /right info -->

            </div><!-- /registration card -->

            <!-- SUCCESS MODAL (registration panel) -->
            <div id="reg_successModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(4,10,28,0.88);backdrop-filter:blur(12px);align-items:center;justify-content:center;padding:1rem;">
                <div style="background:linear-gradient(160deg,#0a1628 0%,#0d1f3c 50%,#0a1628 100%);border:1px solid rgba(59,130,246,0.2);border-radius:1.5rem;width:100%;max-width:420px;box-shadow:0 40px 80px rgba(0,0,0,0.8);overflow:hidden;animation:modalPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both;">
                    <div style="background:linear-gradient(135deg,#14532d 0%,#166534 50%,#14532d 100%);padding:1.5rem 1.75rem;display:flex;align-items:center;gap:1rem;border-bottom:1px solid rgba(34,197,94,0.2);">
                        <div style="width:48px;height:48px;border-radius:0.9rem;background:linear-gradient(135deg,#16a34a,#22c55e);border:1px solid rgba(134,239,172,0.3);box-shadow:0 4px 16px rgba(22,163,74,0.4);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-check text-white text-lg"></i>
                        </div>
                        <div>
                            <p class="text-white text-xl font-black tracking-tight leading-none mb-0.5">Registration Submitted!</p>
                            <p class="text-green-200 text-[10px] font-semibold uppercase tracking-widest">Awaiting admin approval</p>
                        </div>
                    </div>
                    <div style="padding:1.5rem 1.75rem;">
                        <div style="font-size:9px;font-weight:800;letter-spacing:0.18em;text-transform:uppercase;color:#3b82f6;display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                            <span style="flex:1;height:1px;background:rgba(59,130,246,0.15);display:block;"></span>Registration Summary<span style="flex:1;height:1px;background:rgba(59,130,246,0.15);display:block;"></span>
                        </div>
                        <div id="reg_modalInfo"></div>
                        <div style="background:rgba(34,197,94,0.06);border:1px solid rgba(34,197,94,0.2);border-radius:0.75rem;padding:0.875rem 1rem;display:flex;align-items:flex-start;gap:0.75rem;margin:1rem 0;">
                            <i class="fas fa-info-circle text-green-400 text-sm flex-shrink-0 mt-0.5"></i>
                            <p class="text-green-300 text-[10px] font-semibold leading-relaxed">
                                Registration is <strong class="text-yellow-300">pending</strong> admin approval. Login credentials will be sent to <span id="reg_modalEmail" class="text-green-200 font-black"></span> once your account is created.
                            </p>
                        </div>
                        <div style="display:flex;gap:0.625rem;">
                            <button onclick="reg_closeModal()" style="flex:1;padding:0.8rem;background:rgba(255,255,255,0.03);border:1px solid rgba(59,130,246,0.2);border-radius:0.75rem;color:#64748b;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;cursor:pointer;font-family:inherit;"><i class="fas fa-plus mr-1.5"></i>New Registration</button>
                            <button onclick="reg_closeModal()" style="flex:1.5;padding:0.8rem;background:linear-gradient(135deg,#16a34a,#22c55e);border:1px solid rgba(134,239,172,0.3);border-radius:0.75rem;color:white;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;cursor:pointer;box-shadow:0 6px 20px rgba(22,163,74,0.4);font-family:inherit;"><i class="fas fa-check mr-1.5"></i>Done</button>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /panel-studentRegistration -->

        <!-- User Management Panel (hidden by default) -->
        <div id="panel-userManagement" class="content-panel" style="display:none;">
            <div class="max-w-full">

                <!-- Header -->
                <div style="background:white;border-radius:1.25rem;padding:1.5rem;margin-bottom:1.5rem;border:1px solid rgba(21,53,160,0.12);box-shadow:0 8px 32px rgba(13,36,112,0.1);">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div style="width:3rem;height:3rem;background:linear-gradient(135deg,#0d2470,#1535a0);border-radius:0.75rem;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(13,36,112,0.3);">
                                <i id="umPanelIcon" class="fas fa-users text-white text-xl"></i>
                            </div>
                            <div>
                                <h1 id="umPanelTitle" style="font-family:'Barlow Condensed',sans-serif;font-size:1.75rem;font-weight:900;color:#0d2470;letter-spacing:-0.01em;line-height:1;">USERS TABLE</h1>
                                <p id="umPanelSub" style="font-size:0.75rem;color:#1535a0;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;">User Management — Accounts &amp; permissions</p>
                            </div>
                        </div>
                    </div>
                    <!-- JS badge now attached to sidebar Provision item -->
                </div>

                <!-- USERS TABLE SECTION -->
                <div id="usersSection" class="page-section active">
                    <div style="background:white;border-radius:1.25rem;padding:1.25rem;margin-bottom:1.5rem;border:1px solid rgba(21,53,160,0.1);box-shadow:0 4px 16px rgba(13,36,112,0.07);">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <div class="relative">
                                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                                    <input type="text" id="searchInput" placeholder="Search by name, ID, or email..."
                                        class="w-full pl-11 pr-4 py-3 rounded-xl text-sm font-medium focus:outline-none" style="border:1.5px solid #dce4f0;transition:all 0.2s;" onfocus="this.style.borderColor='#1535a0';this.style.boxShadow='0 0 0 3px rgba(21,53,160,0.1)'" onblur="this.style.borderColor='#dce4f0';this.style.boxShadow='none'"
                                        onkeyup="filterUsers()">
                                </div>
                            </div>
                            <div>
                                <select id="roleFilter" onchange="filterUsers()"
                                    class="px-6 py-3 rounded-xl text-sm font-bold focus:outline-none bg-white" style="border:1.5px solid #dce4f0;">
                                    <option value="all">All Roles</option>
                                    <option value="student">Student</option>
                                    <option value="faculty">Faculty</option>
                                    <option value="admin">Admin</option>
                                    <option value="superadmin">Super Admin</option>
                                    <option value="registrar">Registrar</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="librarian">Librarian</option>
                                </select>
                            </div>
                            <button onclick="loadUsers()"
                                style="padding:0.75rem 1.5rem;background:linear-gradient(135deg,#0d2470,#1535a0);color:white;border-radius:0.75rem;font-size:0.8rem;font-weight:800;display:flex;align-items:center;gap:0.5rem;border:none;cursor:pointer;box-shadow:0 4px 12px rgba(13,36,112,0.25);transition:all 0.2s;" onmouseover="this.style.background='linear-gradient(135deg,#1535a0,#1d4ed8)'" onmouseout="this.style.background='linear-gradient(135deg,#0d2470,#1535a0)'">
                                <i class="fas fa-sync-alt"></i>
                                <span>REFRESH</span>
                            </button>
                        </div>
                    </div>
                    <div style="background:white;border-radius:1.25rem;overflow:hidden;border:1px solid rgba(21,53,160,0.1);box-shadow:0 4px 16px rgba(13,36,112,0.07);">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead style="background:linear-gradient(135deg,#f0f4ff,#e8edf8);border-bottom:2px solid rgba(21,53,160,0.15);">
                                    <tr>
                                        <th style="padding:1rem 1.5rem;text-align:left;font-size:0.7rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.1em;font-family:'Barlow Condensed',sans-serif;">USER ID</th>
                                        <th style="padding:1rem 1.5rem;text-align:left;font-size:0.7rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.1em;font-family:'Barlow Condensed',sans-serif;">FULL NAME</th>
                                        <th style="padding:1rem 1.5rem;text-align:left;font-size:0.7rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.1em;font-family:'Barlow Condensed',sans-serif;">ROLE</th>
                                        <th style="padding:1rem 1.5rem;text-align:left;font-size:0.7rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.1em;font-family:'Barlow Condensed',sans-serif;">STATUS</th>
                                        <th style="padding:1rem 1.5rem;text-align:left;font-size:0.7rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.1em;font-family:'Barlow Condensed',sans-serif;">EMAIL</th>
                                        <th style="padding:1rem 1.5rem;text-align:left;font-size:0.7rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.1em;font-family:'Barlow Condensed',sans-serif;">CONTACT</th>
                                        <th style="padding:1rem 1.5rem;text-align:left;font-size:0.7rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.1em;font-family:'Barlow Condensed',sans-serif;">ENROLLED</th>
                                        <th style="padding:1rem 1.5rem;text-align:center;font-size:0.7rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.1em;font-family:'Barlow Condensed',sans-serif;">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody" class="divide-y divide-slate-100">
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center">
                                            <i class="fas fa-spinner fa-spin text-4xl text-slate-300 mb-3"></i>
                                            <p class="text-slate-400 font-medium">Loading users...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- END USERS TABLE -->

                <!-- ROLE & PERMISSIONS SECTION -->
                <div id="permissionsSection" class="page-section">
                    <div style="background:white;border-radius:1.25rem;border:1px solid rgba(21,53,160,0.1);box-shadow:0 4px 16px rgba(13,36,112,0.07);margin-bottom:1.25rem;padding:1rem 1.5rem;display:flex;align-items:center;">
                        <div style="display:flex;background:rgba(13,36,112,0.06);padding:0.25rem;border-radius:0.75rem;gap:0.25rem;">
                            <button onclick="switchPermTab('matrix', this)" class="perm-tab-btn active px-5 py-2 rounded-lg uppercase tracking-wider">
                                <i class="fas fa-table-cells mr-1.5"></i>Permissions Matrix
                            </button>
                            <button onclick="switchPermTab('roles', this)" class="perm-tab-btn px-5 py-2 rounded-lg uppercase tracking-wider">
                                <i class="fas fa-user-tag mr-1.5"></i>Role Assignments
                            </button>
                            <button onclick="switchPermTab('audit', this)" class="perm-tab-btn px-5 py-2 rounded-lg uppercase tracking-wider">
                                <i class="fas fa-scroll mr-1.5"></i>Audit Log
                            </button>
                        </div>
                    </div>

                    <!-- Matrix View -->
                    <div id="matrixView" class="perm-view active">
                        <div class="flex gap-5">
                            <div class="w-56 flex-shrink-0 space-y-2">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1 mb-3">System Roles</p>
                                <div class="role-card active bg-white rounded-xl p-3.5 flex items-center gap-3 shadow-sm" onclick="selectRoleMatrix('superadmin', this)">
                                    <div class="role-card-icon w-9 h-9 rounded-lg bg-violet-100 flex items-center justify-center transition-colors"><i class="fas fa-crown text-violet-700 text-sm"></i></div>
                                    <div><p class="role-card-name text-xs font-black text-slate-800 transition-colors">Super Admin</p><p class="text-[10px] text-slate-400">Full system access</p></div>
                                </div>
                                <div class="role-card bg-white rounded-xl p-3.5 flex items-center gap-3 shadow-sm" onclick="selectRoleMatrix('registrar', this)">
                                    <div class="role-card-icon w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center transition-colors"><i class="fas fa-file-invoice text-blue-700 text-sm"></i></div>
                                    <div><p class="role-card-name text-xs font-black text-slate-800 transition-colors">Registrar</p><p class="text-[10px] text-slate-400">Enrollment &amp; records</p></div>
                                </div>
                                <div class="role-card bg-white rounded-xl p-3.5 flex items-center gap-3 shadow-sm" onclick="selectRoleMatrix('faculty', this)">
                                    <div class="role-card-icon w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center transition-colors"><i class="fas fa-chalkboard-teacher text-amber-700 text-sm"></i></div>
                                    <div><p class="role-card-name text-xs font-black text-slate-800 transition-colors">Faculty</p><p class="text-[10px] text-slate-400">Teaching &amp; grades</p></div>
                                </div>
                                <div class="role-card bg-white rounded-xl p-3.5 flex items-center gap-3 shadow-sm" onclick="selectRoleMatrix('cashier', this)">
                                    <div class="role-card-icon w-9 h-9 rounded-lg bg-rose-100 flex items-center justify-center transition-colors"><i class="fas fa-cash-register text-rose-700 text-sm"></i></div>
                                    <div><p class="role-card-name text-xs font-black text-slate-800 transition-colors">Cashier</p><p class="text-[10px] text-slate-400">Payments &amp; finance</p></div>
                                </div>
                                <div class="role-card bg-white rounded-xl p-3.5 flex items-center gap-3 shadow-sm" onclick="selectRoleMatrix('librarian', this)">
                                    <div class="role-card-icon w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center transition-colors"><i class="fas fa-book text-emerald-700 text-sm"></i></div>
                                    <div><p class="role-card-name text-xs font-black text-slate-800 transition-colors">Librarian</p><p class="text-[10px] text-slate-400">Library resources</p></div>
                                </div>
                                <div class="role-card bg-white rounded-xl p-3.5 flex items-center gap-3 shadow-sm" onclick="selectRoleMatrix('student', this)">
                                    <div class="role-card-icon w-9 h-9 rounded-lg bg-sky-100 flex items-center justify-center transition-colors"><i class="fas fa-user-graduate text-sky-700 text-sm"></i></div>
                                    <div><p class="role-card-name text-xs font-black text-slate-800 transition-colors">Student</p><p class="text-[10px] text-slate-400">Own records only</p></div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-slate-100">
                                    <button onclick="openNewRoleModal()" style="width:100%;padding:0.625rem;border:2px dashed rgba(21,53,160,0.2);border-radius:0.75rem;font-size:0.7rem;font-weight:800;color:#1535a0;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.5rem;background:none;transition:all 0.2s;font-family:inherit;" onmouseover="this.style.borderColor='#1535a0';this.style.background='rgba(21,53,160,0.05)'" onmouseout="this.style.borderColor='rgba(21,53,160,0.2)';this.style.background='none'">
                                        <i class="fas fa-plus"></i> New Role
                                    </button>
                                </div>
                            </div>
                            <!-- Right: Permission Grid -->
                            <div class="flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);">
                                    <div class="flex items-center gap-3">
                                        <div id="matrixRoleIcon" class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center"><i class="fas fa-crown text-violet-700 text-xs"></i></div>
                                        <div>
                                            <p id="matrixRoleTitle" class="text-sm font-black text-slate-800">Super Admin</p>
                                            <p id="matrixRoleDesc" class="text-[10px] text-slate-400">Full system access — cannot be restricted</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center gap-2 text-xs font-bold text-slate-500 cursor-pointer">
                                            <input type="checkbox" id="selectAllToggle" checked onchange="toggleAllPerms(this.checked)" class="w-4 h-4 rounded accent-blue-700">
                                            Select All
                                        </label>
                                        <button onclick="savePermissions()" style="padding:0.5rem 1.25rem;background:linear-gradient(135deg,#0d2470,#1535a0);color:white;border:none;border-radius:0.6rem;font-size:0.7rem;font-weight:900;cursor:pointer;display:flex;align-items:center;gap:0.5rem;font-family:inherit;">
                                            <i class="fas fa-save"></i> Save Changes
                                        </button>
                                    </div>
                                </div>
                                <div id="permissionGrid" class="divide-y divide-slate-50 overflow-y-auto" style="max-height:60vh;">
                                    <div class="px-6 py-3 flex items-center justify-between border-l-4 border-blue-400" style="background:linear-gradient(135deg,#eff6ff,#dbeafe)"><div class="flex items-center gap-2"><i class="fas fa-graduation-cap text-blue-600 text-xs"></i><span class="text-xs font-black text-blue-800 uppercase tracking-wider">Enrollment</span></div><button onclick="toggleModule('enrollment', this)" class="text-[10px] font-bold text-blue-500 hover:text-blue-700">Grant All</button></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="enrollment"><div><p class="text-sm font-bold text-slate-700">View Enrollment Records</p><p class="text-xs text-slate-400 mt-0.5">Browse student enrollment applications and statuses</p></div><label class="perm-toggle"><input type="checkbox" data-perm="enrollment.view" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="enrollment"><div><p class="text-sm font-bold text-slate-700">Create Enrollment</p><p class="text-xs text-slate-400 mt-0.5">Submit new enrollment applications on behalf of students</p></div><label class="perm-toggle"><input type="checkbox" data-perm="enrollment.create" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="enrollment"><div><p class="text-sm font-bold text-slate-700">Approve / Reject Enrollment</p><p class="text-xs text-slate-400 mt-0.5">Finalize or deny student enrollment requests</p></div><label class="perm-toggle"><input type="checkbox" data-perm="enrollment.approve" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="enrollment"><div><p class="text-sm font-bold text-slate-700">Manage Schedule & Subjects</p><p class="text-xs text-slate-400 mt-0.5">Assign subjects, sections, and class schedules</p></div><label class="perm-toggle"><input type="checkbox" data-perm="enrollment.schedule" checked><span class="perm-slider"></span></label></div>
                                    <div class="px-6 py-3 flex items-center justify-between border-l-4 border-green-400" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7)"><div class="flex items-center gap-2"><i class="fas fa-book-open text-green-600 text-xs"></i><span class="text-xs font-black text-green-800 uppercase tracking-wider">Academic Records</span></div><button onclick="toggleModule('academic', this)" class="text-[10px] font-bold text-green-500 hover:text-green-700">Grant All</button></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="academic"><div><p class="text-sm font-bold text-slate-700">View Grades</p><p class="text-xs text-slate-400 mt-0.5">Access student grades and academic performance data</p></div><label class="perm-toggle"><input type="checkbox" data-perm="academic.grades.view" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="academic"><div><p class="text-sm font-bold text-slate-700">Edit / Encode Grades</p><p class="text-xs text-slate-400 mt-0.5">Enter midterm and final grades for assigned subjects</p></div><label class="perm-toggle"><input type="checkbox" data-perm="academic.grades.edit" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="academic"><div><p class="text-sm font-bold text-slate-700">Issue Transcript of Records</p><p class="text-xs text-slate-400 mt-0.5">Generate and release official TOR documents</p></div><label class="perm-toggle"><input type="checkbox" data-perm="academic.transcript" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="academic"><div><p class="text-sm font-bold text-slate-700">Manage Subjects & Curriculum</p><p class="text-xs text-slate-400 mt-0.5">Add, edit, or archive subjects and curriculum structures</p></div><label class="perm-toggle"><input type="checkbox" data-perm="academic.subjects" checked><span class="perm-slider"></span></label></div>
                                    <div class="px-6 py-3 flex items-center justify-between border-l-4 border-amber-400" style="background:linear-gradient(135deg,#fffbeb,#fef3c7)"><div class="flex items-center gap-2"><i class="fas fa-coins text-amber-600 text-xs"></i><span class="text-xs font-black text-amber-800 uppercase tracking-wider">Finance</span></div><button onclick="toggleModule('finance', this)" class="text-[10px] font-bold text-amber-500 hover:text-amber-700">Grant All</button></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="finance"><div><p class="text-sm font-bold text-slate-700">View Financial Records</p><p class="text-xs text-slate-400 mt-0.5">Access student balances, SOAs, and payment histories</p></div><label class="perm-toggle"><input type="checkbox" data-perm="finance.view" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="finance"><div><p class="text-sm font-bold text-slate-700">Post Payments</p><p class="text-xs text-slate-400 mt-0.5">Record cash, online, or check payments from students</p></div><label class="perm-toggle"><input type="checkbox" data-perm="finance.post" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="finance"><div><p class="text-sm font-bold text-slate-700">Manage Scholarships</p><p class="text-xs text-slate-400 mt-0.5">Tag students as scholars and apply tuition discounts</p></div><label class="perm-toggle"><input type="checkbox" data-perm="finance.scholarship" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="finance"><div><p class="text-sm font-bold text-slate-700">Place / Lift Financial Holds</p><p class="text-xs text-slate-400 mt-0.5">Block or unblock enrollment due to outstanding balances</p></div><label class="perm-toggle"><input type="checkbox" data-perm="finance.holds" checked><span class="perm-slider"></span></label></div>
                                    <div class="px-6 py-3 flex items-center justify-between border-l-4 border-purple-400" style="background:linear-gradient(135deg,#faf5ff,#ede9fe)"><div class="flex items-center gap-2"><i class="fas fa-users text-purple-600 text-xs"></i><span class="text-xs font-black text-purple-800 uppercase tracking-wider">User Management</span></div><button onclick="toggleModule('users', this)" class="text-[10px] font-bold text-purple-500 hover:text-purple-700">Grant All</button></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="users"><div><p class="text-sm font-bold text-slate-700">View All Users</p><p class="text-xs text-slate-400 mt-0.5">Browse the full user directory (students, faculty, staff)</p></div><label class="perm-toggle"><input type="checkbox" data-perm="users.view" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="users"><div><p class="text-sm font-bold text-slate-700">Create &amp; Edit Users</p><p class="text-xs text-slate-400 mt-0.5">Add new accounts and modify user profile information</p></div><label class="perm-toggle"><input type="checkbox" data-perm="users.edit" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="users"><div><p class="text-sm font-bold text-slate-700">Delete Users</p><p class="text-xs text-slate-400 mt-0.5">Permanently remove user accounts from the system</p></div><label class="perm-toggle"><input type="checkbox" data-perm="users.delete" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="users"><div><p class="text-sm font-bold text-slate-700">Reset Passwords</p><p class="text-xs text-slate-400 mt-0.5">Force password resets and manage account credentials</p></div><label class="perm-toggle"><input type="checkbox" data-perm="users.password" checked><span class="perm-slider"></span></label></div>
                                    <div class="px-6 py-3 flex items-center justify-between border-l-4 border-rose-400" style="background:linear-gradient(135deg,#fff1f2,#ffe4e6)"><div class="flex items-center gap-2"><i class="fas fa-shield-halved text-rose-600 text-xs"></i><span class="text-xs font-black text-rose-800 uppercase tracking-wider">System &amp; Security</span></div><button onclick="toggleModule('system', this)" class="text-[10px] font-bold text-rose-500 hover:text-rose-700">Grant All</button></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="system"><div><p class="text-sm font-bold text-slate-700">Manage Roles &amp; Permissions</p><p class="text-xs text-slate-400 mt-0.5">Configure RBAC roles, assign permissions, view audit logs</p></div><label class="perm-toggle"><input type="checkbox" data-perm="system.rbac" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="system"><div><p class="text-sm font-bold text-slate-700">View Audit Trail</p><p class="text-xs text-slate-400 mt-0.5">Read system logs of all administrative and security actions</p></div><label class="perm-toggle"><input type="checkbox" data-perm="system.audit" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="system"><div><p class="text-sm font-bold text-slate-700">System Configuration</p><p class="text-xs text-slate-400 mt-0.5">Modify system settings, academic calendar, and school parameters</p></div><label class="perm-toggle"><input type="checkbox" data-perm="system.config" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="system"><div><p class="text-sm font-bold text-slate-700">Database Backup &amp; Restore</p><p class="text-xs text-slate-400 mt-0.5">Trigger manual backups and restore data from backup points</p></div><label class="perm-toggle"><input type="checkbox" data-perm="system.backup" checked><span class="perm-slider"></span></label></div>
                                    <div class="px-6 py-3 flex items-center justify-between border-l-4 border-indigo-400" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff)"><div class="flex items-center gap-2"><i class="fas fa-chart-bar text-indigo-600 text-xs"></i><span class="text-xs font-black text-indigo-800 uppercase tracking-wider">Reports &amp; Analytics</span></div><button onclick="toggleModule('reports', this)" class="text-[10px] font-bold text-indigo-500 hover:text-indigo-700">Grant All</button></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="reports"><div><p class="text-sm font-bold text-slate-700">View Reports &amp; Dashboards</p><p class="text-xs text-slate-400 mt-0.5">Access enrollment stats, grade distributions, and financial summaries</p></div><label class="perm-toggle"><input type="checkbox" data-perm="reports.view" checked><span class="perm-slider"></span></label></div>
                                    <div class="perm-row px-6 py-3.5 flex items-center justify-between" data-module="reports"><div><p class="text-sm font-bold text-slate-700">Export Reports</p><p class="text-xs text-slate-400 mt-0.5">Download reports as PDF, Excel, or CSV files</p></div><label class="perm-toggle"><input type="checkbox" data-perm="reports.export" checked><span class="perm-slider"></span></label></div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /matrixView -->

                    <!-- Role Assignments View -->
                    <div id="rolesView" class="perm-view">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
                                <div><h3 class="text-base font-black text-slate-800 flex items-center gap-2 mb-1"><i class="fas fa-user-plus text-blue-600"></i> Assign a Role</h3><p class="text-xs text-slate-400">Search for a user and grant them a system role.</p></div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Search User</label>
                                    <div class="relative"><i class="fas fa-search absolute left-3.5 top-3.5 text-slate-300 text-xs"></i><input type="text" id="assignSearchInput" oninput="filterAssignUsers()" onfocus="filterAssignUsers()" placeholder="Type name or email..." class="glass-input w-full pl-9 pr-4 py-3 text-sm font-medium" autocomplete="off"></div>
                                    <div id="assignSearchResults" class="hidden bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden max-h-52 overflow-y-auto" style="position:relative;z-index:100;"></div>
                                </div>
                                <div id="selectedUserCard" class="hidden bg-slate-50 rounded-xl p-4 border border-slate-200">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div id="assignUserIcon" class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center"><i class="fas fa-user text-blue-600"></i></div>
                                        <div><p id="assignUserName" class="text-sm font-black text-slate-800">—</p><p id="assignUserEmail" class="text-xs text-slate-400">—</p></div>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Current Role</p>
                                    <p id="assignUserCurrentRole" class="text-sm font-black text-slate-700 capitalize mb-3">—</p>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">New Role</label>
                                    <select id="assignRoleSelect" class="glass-input w-full px-4 py-3 text-sm font-bold bg-white"><option value="">Choose a role...</option><option value="student">Student</option><option value="faculty">Faculty</option><option value="admin">Admin</option><option value="superadmin">Super Admin</option><option value="registrar">Registrar</option><option value="cashier">Cashier</option><option value="librarian">Librarian</option></select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Reason <span class="normal-case font-normal">(optional)</span></label>
                                    <textarea id="assignReason" rows="2" placeholder="e.g. Promoted to department head..." class="glass-input w-full px-4 py-3 text-sm resize-none"></textarea>
                                </div>
                                <button onclick="submitRoleAssignment()" style="width:100%;padding:0.75rem;background:linear-gradient(135deg,#0d2470,#1535a0);color:white;border:none;border-radius:0.75rem;font-size:0.75rem;font-weight:900;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.5rem;font-family:inherit;"><i class="fas fa-check-circle"></i> Confirm Role Assignment</button>
                            </div>
                            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                                    <div><h3 class="text-sm font-black text-slate-800">All Role Assignments</h3><p class="text-xs text-slate-400 mt-0.5">Showing <span id="assignmentCount">0</span> users</p></div>
                                    <input type="text" placeholder="Filter..." oninput="renderAssignmentTable(this.value.toLowerCase())" class="glass-input px-3 py-2 text-xs font-medium w-48">
                                </div>
                                <div class="overflow-y-auto" style="max-height:60vh;"><table class="w-full"><thead class="bg-slate-50 border-b border-slate-100 sticky top-0"><tr><th class="px-5 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">User</th><th class="px-5 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">Role</th><th class="px-5 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">Granted By</th><th class="px-5 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">Date</th><th class="px-5 py-3 text-center text-[10px] font-black text-slate-400 uppercase tracking-wider">Action</th></tr></thead><tbody id="assignmentTableBody" class="divide-y divide-slate-50"><tr><td colspan="5" class="px-5 py-8 text-center text-xs text-slate-400">Loading...</td></tr></tbody></table></div>
                            </div>
                        </div>
                    </div><!-- /rolesView -->

                    <!-- Audit Log View -->
                    <div id="auditView" class="perm-view">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                                <div><h3 class="text-sm font-black text-slate-800 flex items-center gap-2"><i class="fas fa-scroll text-slate-500"></i> Security &amp; Access Audit Log</h3><p class="text-xs text-slate-400 mt-0.5">Every role assignment, permission change, and access event is recorded here.</p></div>
                                <div class="flex items-center gap-2">
                                    <select class="glass-input px-3 py-2 text-xs font-bold bg-white" onchange="auditCurrentPage=1;renderAuditLog(this.value,1)"><option>All Events</option><option>Login Events</option><option>Password Reset</option><option>Role Changes</option><option>Permission Changes</option><option>User Creation</option><option>User Deletion</option><option>Failed Access</option></select>
                                    <button class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50 transition flex items-center gap-1.5"><i class="fas fa-file-export"></i> Export</button>
                                </div>
                            </div>
                            <div class="overflow-y-auto" style="max-height:62vh;"><table class="w-full"><thead class="bg-slate-50 border-b border-slate-100 sticky top-0"><tr><th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">Timestamp</th><th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">Performed By</th><th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">Event</th><th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">Affected</th><th class="px-6 py-3 text-center text-[10px] font-black text-slate-400 uppercase tracking-wider">Result</th></tr></thead><tbody id="auditTableBody" class="divide-y divide-slate-50"></tbody></table></div>
                            <div id="auditPagination" style="display:none;padding:0.75rem 1.5rem;border-top:1px solid #f1f5f9;background:#f8fafc;display:flex;align-items:center;justify-content:space-between;flex-wrap:gap:8px;">
                                <p id="auditPageInfo" style="font-size:11px;color:#94a3b8;font-weight:600;"></p>
                                <div id="auditPageBtns" style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;"></div>
                            </div>
                        </div>
                    </div><!-- /auditView -->
                </div>
                <!-- END ROLE & PERMISSIONS -->

                <!-- PROVISION SECTION -->
                <div id="provisionSection" class="page-section">

                    <!-- Provision Stats -->
                    <div style="background:white;border-radius:1.25rem;padding:1.25rem 1.5rem;margin-bottom:1.25rem;border:1px solid rgba(21,53,160,0.12);box-shadow:0 4px 16px rgba(13,36,112,0.07);">
                        <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                            <div>
                                <h2 style="font-family:'Barlow Condensed',sans-serif;font-size:1.25rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.04em;">Account Provisioning</h2>
                                <p style="font-size:0.72rem;color:#64748b;font-weight:600;margin-top:2px;">Review new registrations and create system accounts for approved students and staff.</p>
                            </div>
                            <button id="prov-createAllBtn" onclick="prov_createAllAccounts()" class="btn-create-all" disabled>
                                <i class="fas fa-bolt"></i><span>Create All Accounts</span>
                            </button>
                        </div>
                        <div class="grid grid-cols-4 gap-4 pt-4" style="border-top:1px solid rgba(21,53,160,0.08);">
                            <div style="background:#fefce8;border-radius:0.75rem;padding:1rem 1.25rem;border:1px solid rgba(234,179,8,0.2);">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pending Approval</p>
                                <p id="prov-statPendingApproval" style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:900;color:#a16207;line-height:1;">—</p>
                            </div>
                            <div style="background:#f5f8ff;border-radius:0.75rem;padding:1rem 1.25rem;border:1px solid rgba(21,53,160,0.08);">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Approved — No Account</p>
                                <p id="prov-statPending" style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:900;color:#0d2470;line-height:1;">—</p>
                            </div>
                            <div style="background:#f0fdf4;border-radius:0.75rem;padding:1rem 1.25rem;border:1px solid rgba(21,163,74,0.15);">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Accounts Created Today</p>
                                <p id="prov-statCreated" style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:900;color:#15803d;line-height:1;">—</p>
                            </div>
                            <div style="background:#eff6ff;border-radius:0.75rem;padding:1rem 1.25rem;border:1px solid rgba(59,130,246,0.15);">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Registered</p>
                                <p id="prov-statTotal" style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:900;color:#1d4ed8;line-height:1;">—</p>
                            </div>
                        </div>
                    </div>

                    <!-- Provision Tab Card -->
                    <div style="background:white;border-radius:1.25rem;border:1px solid rgba(21,53,160,0.12);box-shadow:0 4px 16px rgba(13,36,112,0.07);overflow:hidden;">

                        <!-- Type Switcher: Student / Staff -->
                        <div class="flex items-center gap-2 px-5 pt-4 pb-3" style="border-bottom:1px solid #f0f4ff;background:#fafbff;">
                            <span style="font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:0.12em;color:#94a3b8;margin-right:4px;">Viewing:</span>
                            <button id="prov-type-student" onclick="prov_switchType('student')"
                                style="padding:0.35rem 1rem;border-radius:9999px;font-size:0.72rem;font-weight:800;font-family:inherit;cursor:pointer;border:1.5px solid #1535a0;background:linear-gradient(135deg,#0d2470,#1535a0);color:white;transition:all 0.18s;display:flex;align-items:center;gap:0.4rem;">
                                <i class="fas fa-user-graduate" style="font-size:10px;"></i> Students
                            </button>
                            <button id="prov-type-staff" onclick="prov_switchType('staff')"
                                style="padding:0.35rem 1rem;border-radius:9999px;font-size:0.72rem;font-weight:800;font-family:inherit;cursor:pointer;border:1.5px solid #c7d2fe;background:#f0f4ff;color:#3b4eac;transition:all 0.18s;display:flex;align-items:center;gap:0.4rem;">
                                <i class="fas fa-user-tie" style="font-size:10px;"></i> Staff
                            </button>
                        </div>

                        <!-- Inner Tab Bar -->
                        <div class="flex items-center gap-1 px-5 pt-3" style="border-bottom:2px solid #e8edf5;">
                            <button class="prov-tab-btn active" id="prov-tab-approve" onclick="prov_switchTab('approve')">
                                <i class="fas fa-clock mr-1"></i> Pending Approval
                                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;border-radius:9999px;font-size:9px;font-weight:900;padding:0 5px;background:#fef9c3;color:#a16207;" id="prov-pendingBadge">0</span>
                            </button>
                            <button class="prov-tab-btn" id="prov-tab-provision" onclick="prov_switchTab('provision')">
                                <i class="fas fa-key mr-1"></i> Create Accounts
                                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;border-radius:9999px;font-size:9px;font-weight:900;padding:0 5px;background:#dbeafe;color:#1d4ed8;" id="prov-approvedBadge">0</span>
                            </button>
                        </div>

                        <!-- TAB 1: PENDING APPROVAL -->
                        <div id="prov-section-approve" class="prov-tab-section active">
                            <div class="flex items-center justify-between gap-4 p-5 flex-wrap">
                                <div>
                                    <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.05em;">Pending Registration Review</h3>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5">Review new student registrations. <span class="text-green-600 font-bold">Approve</span> to move to account creation, or <span class="text-red-500 font-bold">Reject</span> to decline.</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                                        <input type="text" id="prov-searchPending" placeholder="Search name or program..." oninput="prov_filterPending()"
                                            style="background:#fff;border:1.5px solid #e0e8f8;border-radius:0.6rem;padding:0.6rem 1rem 0.6rem 2.5rem;font-size:0.82rem;font-family:inherit;outline:none;width:240px;">
                                    </div>
                                    <button onclick="prov_loadAll()" style="padding:0.6rem 1rem;border:1.5px solid rgba(21,53,160,0.2);color:#0d2470;border-radius:0.5rem;font-size:0.72rem;font-weight:800;font-family:'Barlow Condensed',sans-serif;letter-spacing:0.08em;text-transform:uppercase;background:#f5f8ff;cursor:pointer;display:flex;align-items:center;gap:0.4rem;" onmouseover="this.style.background='#eef2ff'" onmouseout="this.style.background='#f5f8ff'">
                                        <i class="fas fa-sync-alt text-xs"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full" style="border-collapse:collapse;">
                                    <thead>
                                        <tr style="background:#fefce8;border-bottom:1px solid #fde68a;">
                                            <th class="text-left px-5 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Student</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Program</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Year</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Personal Email</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Registered</th>
                                            <th class="px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prov-pendingTableBody">
                                        <tr><td colspan="6" class="text-center py-16"><i class="fas fa-spinner fa-spin text-amber-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Staff Pending Table (hidden until type = staff) -->
                            <div class="overflow-x-auto" id="prov-staffPendingWrap" style="display:none;">
                                <table class="w-full" style="border-collapse:collapse;">
                                    <thead>
                                        <tr style="background:#fefce8;border-bottom:1px solid #fde68a;">
                                            <th class="text-left px-5 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Staff Member</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Role</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Department</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Personal Email</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Registered</th>
                                            <th class="px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prov-staffPendingTableBody">
                                        <tr><td colspan="6" class="text-center py-16"><i class="fas fa-spinner fa-spin text-amber-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 2: CREATE ACCOUNTS -->
                        <div id="prov-section-provision" class="prov-tab-section" style="display:none;">
                            <div class="flex items-center justify-between gap-4 p-5 flex-wrap">
                                <div>
                                    <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.05em;" id="prov-createAccountsTitle">Approved Students — Awaiting Account Creation</h3>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5" id="prov-createAccountsSubtitle">Only students with status = <span class="text-green-600 font-bold">approved</span> and no existing account are listed below.</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                                        <input type="text" id="prov-searchInput" placeholder="Search name or program..." oninput="prov_filterTable()"
                                            style="background:#fff;border:1.5px solid #e0e8f8;border-radius:0.6rem;padding:0.6rem 1rem 0.6rem 2.5rem;font-size:0.82rem;font-family:inherit;outline:none;width:240px;">
                                    </div>
                                    <button onclick="prov_loadAll()" style="padding:0.6rem 1rem;border:1.5px solid rgba(21,53,160,0.2);color:#0d2470;border-radius:0.5rem;font-size:0.72rem;font-weight:800;font-family:'Barlow Condensed',sans-serif;letter-spacing:0.08em;text-transform:uppercase;background:#f5f8ff;cursor:pointer;display:flex;align-items:center;gap:0.4rem;" onmouseover="this.style.background='#eef2ff'" onmouseout="this.style.background='#f5f8ff'">
                                        <i class="fas fa-sync-alt text-xs"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full" style="border-collapse:collapse;">
                                    <thead>
                                        <tr style="background:#f8faff;border-bottom:1px solid #e0e8f8;">
                                            <th class="text-left px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Student</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Program</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Year</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Personal Email</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Registered</th>
                                            <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prov-studentsTableBody">
                                        <tr><td colspan="6" class="text-center py-16"><i class="fas fa-spinner fa-spin text-blue-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Staff Accounts Table (hidden until type = staff) -->
                            <div class="overflow-x-auto" id="prov-staffAccountsWrap" style="display:none;">
                                <table class="w-full" style="border-collapse:collapse;">
                                    <thead>
                                        <tr style="background:#f8faff;border-bottom:1px solid #e0e8f8;">
                                            <th class="text-left px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Staff Member</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Role</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Department</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Personal Email</th>
                                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Registered</th>
                                            <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prov-staffAccountsTableBody">
                                        <tr><td colspan="6" class="text-center py-16"><i class="fas fa-spinner fa-spin text-blue-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div><!-- /provision tab card -->

                    <!-- Provision Success Modal -->
                    <div id="prov-successModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(4,10,28,0.88);backdrop-filter:blur(12px);align-items:center;justify-content:center;padding:1rem;">
                        <div style="background:linear-gradient(160deg,#0a1628 0%,#0d1f3c 50%,#0a1628 100%);border:1px solid rgba(59,130,246,0.2);border-radius:1.5rem;width:100%;max-width:440px;box-shadow:0 40px 80px rgba(0,0,0,0.8);overflow:hidden;animation:modalPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both;">
                            <div style="background:linear-gradient(135deg,#1a3a6e 0%,#1e3a8a 50%,#1a3a6e 100%);padding:1.5rem 1.75rem;display:flex;align-items:center;gap:1rem;border-bottom:1px solid rgba(59,130,246,0.15);position:relative;overflow:hidden;">
                                <div style="width:48px;height:48px;border-radius:0.9rem;background:linear-gradient(135deg,#1d4ed8,#2563eb);border:1px solid rgba(96,165,250,0.3);box-shadow:0 4px 16px rgba(37,99,235,0.4);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-check text-white text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-white text-xl font-black tracking-tight leading-none mb-0.5">Account Provisioned!</p>
                                    <p id="prov-modalSubtitle" class="text-blue-200 text-[10px] font-semibold uppercase tracking-widest">Student account created successfully</p>
                                </div>
                                <div class="ml-auto">
                                    <span id="prov-modalTypeBadge" style="background:rgba(255,255,255,0.1);color:white;font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:0.1em;padding:4px 12px;border-radius:9999px;border:1px solid rgba(255,255,255,0.2);">Student</span>
                                </div>
                            </div>
                            <div style="padding:1.5rem 1.75rem;">
                                <div style="font-size:9px;font-weight:800;letter-spacing:0.18em;text-transform:uppercase;color:#3b82f6;display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                                    <span style="flex:1;height:1px;background:rgba(59,130,246,0.15);"></span>
                                    Account Credentials
                                    <span style="flex:1;height:1px;background:rgba(59,130,246,0.15);"></span>
                                </div>
                                <div id="prov-modalCreds"></div>
                                <div style="background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.15);border-radius:0.75rem;padding:0.75rem 1rem;display:flex;align-items:center;gap:0.75rem;margin:1rem 0;">
                                    <i class="fas fa-paper-plane text-blue-400 text-sm flex-shrink-0"></i>
                                    <p class="text-blue-300 text-[10px] font-semibold leading-relaxed">Credentials sent to <span id="prov-modalSentEmail" class="text-blue-200 font-black"></span></p>
                                </div>
                                <div style="display:flex;gap:0.625rem;">
                                    <button onclick="prov_closeModal()" style="flex:1;padding:0.8rem;background:rgba(255,255,255,0.03);border:1px solid rgba(59,130,246,0.2);border-radius:0.75rem;color:#64748b;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;cursor:pointer;font-family:inherit;"><i class="fas fa-times mr-1.5"></i>Close</button>
                                    <button onclick="prov_closeModal()" style="flex:1.5;padding:0.8rem;background:linear-gradient(135deg,#1d4ed8,#2563eb);border:1px solid rgba(96,165,250,0.3);border-radius:0.75rem;color:white;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;cursor:pointer;box-shadow:0 6px 20px rgba(37,99,235,0.4);font-family:inherit;"><i class="fas fa-check mr-1.5"></i>Done</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- END PROVISION SECTION -->

            </div><!-- /max-w-full -->
        </div><!-- /panel-userManagement -->

    </div><!-- /main-content -->

    <!-- User Profile Modal -->
    <div id="userModal" class="ut-modal" onclick="closeModalOnBackdrop(event)">
        <div class="modal-content bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="relative bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 rounded-t-3xl">
                <button onclick="closeModal()" class="absolute top-6 right-6 text-white hover:text-gray-200 transition"><i class="fas fa-times text-2xl"></i></button>
                <div class="flex items-center gap-6">
                    <div class="w-32 h-32 bg-white rounded-2xl flex items-center justify-center border-4 border-blue-400 shadow-xl"><div class="text-center"><i id="modalPhotoIcon" class="fas fa-user text-5xl text-blue-600"></i><p class="text-[8px] text-slate-400 mt-1 font-bold">PHOTO</p></div></div>
                    <div class="flex-1 text-white"><h2 id="modalFullName" class="text-3xl font-black mb-1">Loading...</h2><p id="modalUserId" class="text-blue-100 font-mono text-lg font-bold mb-2">ID: ---</p><span id="modalRoleBadge" class="inline-block px-4 py-1 bg-white text-blue-600 rounded-full text-xs font-black uppercase">Role</span></div>
                </div>
            </div>
            <div class="p-8">
                <div class="mb-8"><h3 class="text-sm font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2"><i class="fas fa-envelope"></i> Contact Information</h3><div class="bg-slate-50 rounded-2xl p-6 space-y-3"><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Institutional Email</span><span id="modalInstitutionalEmail" class="text-slate-800 font-bold text-sm">---</span></div><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Personal Email</span><span id="modalPersonalEmail" class="text-slate-800 font-bold text-sm">---</span></div><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Mobile Number</span><span id="modalMobileNumber" class="text-slate-800 font-bold text-sm">---</span></div></div></div>
                <div class="mb-8"><h3 class="text-sm font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2"><i class="fas fa-id-card"></i> Personal Information</h3><div class="bg-slate-50 rounded-2xl p-6 space-y-3"><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Birth Date</span><span id="modalBirthDate" class="text-slate-800 font-bold text-sm">---</span></div><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Gender</span><span id="modalGender" class="text-slate-800 font-bold text-sm">---</span></div><div id="modalLrnRow" class="info-row flex justify-between" style="display:none;"><span class="text-slate-500 text-sm font-medium">Student Ref</span><span id="modalLrn" class="text-slate-800 font-bold text-sm font-mono">---</span></div><div id="modalEmployeeIdRow" class="info-row flex justify-between" style="display:none;"><span class="text-slate-500 text-sm font-medium">Employee ID</span><span id="modalEmployeeId" class="text-slate-800 font-bold text-sm font-mono">---</span></div></div></div>
                <div class="mb-8"><h3 id="modalAcademicTitle" class="text-sm font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2"><i class="fas fa-graduation-cap"></i> Academic Information</h3><div class="bg-slate-50 rounded-2xl p-6 space-y-3"><div class="info-row flex justify-between"><span id="modalProgramLabel" class="text-slate-500 text-sm font-medium">Program</span><span id="modalProgram" class="text-slate-800 font-bold text-sm">---</span></div><div id="modalYearRow" class="info-row flex justify-between"><span id="modalYearLabel" class="text-slate-500 text-sm font-medium">Year Level</span><span id="modalYear" class="text-slate-800 font-bold text-sm">---</span></div><div id="modalMajorRow" class="info-row flex justify-between" style="display:none;"><span class="text-slate-500 text-sm font-medium">Major</span><span id="modalMajor" class="text-slate-800 font-bold text-sm">---</span></div><div id="modalStatusRow" class="info-row flex justify-between" style="display:none;"><span class="text-slate-500 text-sm font-medium">Enrollment Status</span><span id="modalStatus" class="text-slate-800 font-bold text-sm">---</span></div><div id="modalSpecializationRow" class="info-row flex justify-between" style="display:none;"><span class="text-slate-500 text-sm font-medium">Specialization</span><span id="modalSpecialization" class="text-slate-800 font-bold text-sm">---</span></div><div id="modalEmploymentTypeRow" class="info-row flex justify-between" style="display:none;"><span class="text-slate-500 text-sm font-medium">Employment Type</span><span id="modalEmploymentType" class="text-slate-800 font-bold text-sm">---</span></div><div id="modalAccessLevelRow" class="info-row flex justify-between" style="display:none;"><span class="text-slate-500 text-sm font-medium">Access Level</span><span id="modalAccessLevel" class="text-slate-800 font-bold text-sm">---</span></div><div id="modalLifeStatusRow" class="info-row flex justify-between" style="display:none;"><span class="text-slate-500 text-sm font-medium">Status</span><span id="modalLifeStatus" class="text-slate-800 font-bold text-sm">---</span></div></div></div>
                <div id="modalGuardianSection" class="mb-8" style="display:none;"><h3 class="text-sm font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2"><i class="fas fa-user-friends"></i> Guardian Information</h3><div class="bg-slate-50 rounded-2xl p-6 space-y-3"><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Guardian Name</span><span id="modalGuardianName" class="text-slate-800 font-bold text-sm">---</span></div><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Guardian Contact</span><span id="modalGuardianContact" class="text-slate-800 font-bold text-sm">---</span></div><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Relationship</span><span id="modalGuardianRelationship" class="text-slate-800 font-bold text-sm">---</span></div></div></div>
                <div class="mb-8"><h3 class="text-sm font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2"><i class="fas fa-map-marker-alt"></i> Address</h3><div class="bg-slate-50 rounded-2xl p-6 space-y-3"><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Street Address</span><span id="modalStreetAddress" class="text-slate-800 font-bold text-sm">---</span></div><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Barangay</span><span id="modalBarangay" class="text-slate-800 font-bold text-sm">---</span></div><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">City</span><span id="modalCity" class="text-slate-800 font-bold text-sm">---</span></div><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Province</span><span id="modalProvince" class="text-slate-800 font-bold text-sm">---</span></div><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">ZIP Code</span><span id="modalZipCode" class="text-slate-800 font-bold text-sm">---</span></div></div></div>
                <div class="mb-8"><h3 class="text-sm font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2"><i class="fas fa-cog"></i> System Information</h3><div class="bg-slate-50 rounded-2xl p-6 space-y-3"><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Date Enrolled</span><span id="modalCreatedAt" class="text-slate-800 font-bold text-sm">---</span></div><div class="info-row flex justify-between"><span class="text-slate-500 text-sm font-medium">Last Updated</span><span id="modalUpdatedAt" class="text-slate-800 font-bold text-sm">---</span></div></div></div>
                <div class="flex gap-3 pt-4 border-t border-slate-100">
                    <button onclick="closeModal()" class="flex-1 py-3 border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition">Close</button>
                    <button onclick="openEditModal()" class="flex-1 py-3 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition flex items-center justify-center gap-2"><i class="fas fa-edit"></i> Edit Info</button>
                    <button onclick="openResetPasswordModal()" class="flex-1 py-3 bg-amber-500 text-white rounded-xl text-sm font-bold hover:bg-amber-600 transition flex items-center justify-center gap-2"><i class="fas fa-key"></i> Reset Password</button>
                    <button onclick="deleteUserFromModal()" class="flex-1 py-3 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 transition flex items-center justify-center gap-2"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Edit User Metadata Modal ───────────────────────────────────────── -->
    <div id="editUserModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)closeEditModal()">
        <div style="background:white;border-radius:1.25rem;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,0.2);">
            <div style="background:linear-gradient(135deg,#1535a0,#1d4ed8);padding:1.5rem 1.75rem;border-radius:1.25rem 1.25rem 0 0;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <p style="font-size:9px;font-weight:800;letter-spacing:0.15em;text-transform:uppercase;color:rgba(255,255,255,0.6);margin-bottom:2px;">User Management</p>
                    <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:1.4rem;font-weight:900;color:white;text-transform:uppercase;">Edit User Information</h3>
                </div>
                <button onclick="closeEditModal()" style="background:rgba(255,255,255,0.15);border:none;color:white;width:32px;height:32px;border-radius:50%;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
            </div>
            <div style="padding:1.75rem;">
                <div id="editModalMsg" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:0.6rem;font-size:0.8rem;font-weight:600;"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">First Name</label>
                        <input id="edit_firstName" type="text" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#1535a0'">
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">Last Name</label>
                        <input id="edit_lastName" type="text" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#1535a0'">
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">Middle Name</label>
                        <input id="edit_middleName" type="text" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#1535a0'">
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">Mobile Number</label>
                        <input id="edit_mobile" type="text" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#1535a0'">
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">Personal Email</label>
                        <input id="edit_personalEmail" type="email" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#1535a0'">
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">Gender</label>
                        <select id="edit_gender" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;background:white;">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">Birth Date</label>
                        <input id="edit_birthDate" type="date" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#1535a0'">
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">Street Address</label>
                        <input id="edit_street" type="text" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#1535a0'">
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">City</label>
                        <input id="edit_city" type="text" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#1535a0'">
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">Province</label>
                        <input id="edit_province" type="text" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#1535a0'">
                    </div>
                    <div>
                        <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">ZIP Code</label>
                        <input id="edit_zip" type="text" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#1535a0'">
                    </div>
                </div>
                <!-- Status field — options change based on user role -->
                <div style="margin-bottom:1rem;">
                    <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">Account Status</label>
                    <select id="edit_lifeStatus" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:700;outline:none;background:white;" onchange="this.style.borderColor='#1535a0'">
                        <!-- Options injected dynamically by openEditModal() based on role -->
                    </select>
                </div>
                <div style="display:flex;gap:0.75rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
                    <button onclick="closeEditModal()" style="flex:1;padding:0.7rem;border:1.5px solid #e2e8f0;background:white;border-radius:0.6rem;font-size:0.8rem;font-weight:700;cursor:pointer;">Cancel</button>
                    <button onclick="submitEditUser()" id="editSaveBtn" style="flex:2;padding:0.7rem;background:linear-gradient(135deg,#1535a0,#1d4ed8);color:white;border:none;border-radius:0.6rem;font-size:0.8rem;font-weight:800;cursor:pointer;text-transform:uppercase;letter-spacing:0.05em;">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Reset Password Modal ───────────────────────────────────────────── -->
    <div id="resetPwModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)closeResetPasswordModal()">
        <div style="background:white;border-radius:1.25rem;width:100%;max-width:420px;box-shadow:0 24px 64px rgba(0,0,0,0.2);">
            <div style="background:linear-gradient(135deg,#92400e,#d97706);padding:1.5rem 1.75rem;border-radius:1.25rem 1.25rem 0 0;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <p style="font-size:9px;font-weight:800;letter-spacing:0.15em;text-transform:uppercase;color:rgba(255,255,255,0.6);margin-bottom:2px;">Admin Action</p>
                    <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:1.4rem;font-weight:900;color:white;text-transform:uppercase;">Reset User Password</h3>
                </div>
                <button onclick="closeResetPasswordModal()" style="background:rgba(255,255,255,0.15);border:none;color:white;width:32px;height:32px;border-radius:50%;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
            </div>
            <div style="padding:1.75rem;">
                <div id="resetPwMsg" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:0.6rem;font-size:0.8rem;font-weight:600;"></div>
                <p style="font-size:0.85rem;color:#64748b;margin-bottom:1.25rem;line-height:1.55;">Set a new password for <strong id="resetPwUserName" style="color:#0f172a;"></strong>. The user will log in with this password directly — no change prompt. Any future password resets must be done through an admin.</p>
                <div style="margin-bottom:1rem;">
                    <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">New Password</label>
                    <div style="position:relative;">
                        <input id="resetPwInput" type="password" placeholder="Min 10 chars · upper · lower · number · symbol" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 2.5rem 0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#d97706';resetPwStrength(this.value)">
                        <button type="button" onclick="toggleResetPwEye()" style="position:absolute;right:0.6rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1rem;"><i id="resetPwEyeIcon" class="bx bx-show"></i></button>
                    </div>
                    <div id="resetPwStrengthBar" style="margin-top:6px;height:4px;border-radius:4px;background:#e2e8f0;overflow:hidden;"><div id="resetPwStrengthFill" style="height:100%;width:0%;border-radius:4px;transition:all 0.3s;background:#ef4444;"></div></div>
                    <p id="resetPwStrengthLabel" style="font-size:10px;font-weight:700;color:#94a3b8;margin-top:3px;">Enter a password</p>
                </div>
                <div style="margin-bottom:1.25rem;">
                    <label style="font-size:10px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px;">Confirm Password</label>
                    <input id="resetPwConfirm" type="password" placeholder="Re-enter password" style="width:100%;border:1.5px solid #e2e8f0;border-radius:0.6rem;padding:0.6rem 0.75rem;font-size:0.875rem;font-weight:600;outline:none;" oninput="this.style.borderColor='#d97706'">
                </div>
                <div style="display:flex;gap:0.75rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
                    <button onclick="closeResetPasswordModal()" style="flex:1;padding:0.7rem;border:1.5px solid #e2e8f0;background:white;border-radius:0.6rem;font-size:0.8rem;font-weight:700;cursor:pointer;">Cancel</button>
                    <button onclick="submitResetPassword()" id="resetPwSaveBtn" style="flex:2;padding:0.7rem;background:linear-gradient(135deg,#92400e,#d97706);color:white;border:none;border-radius:0.6rem;font-size:0.8rem;font-weight:800;cursor:pointer;text-transform:uppercase;letter-spacing:0.05em;">Reset Password</button>
                </div>
            </div>
        </div>
    </div>

</div><!-- /page-wrapper -->

<script>
// ── Content panel switcher ─────────────────────────────────────────────────
// FIX #5: showPanel now correctly hides ALL panels, not just .content-panel class
// (dashboard panel has class content-panel, so the querySelector works)
// Also added 'dashboard' panel link in sidebar with showPanel call.
function showPanel(panelId, navLink) {
    document.querySelectorAll('.content-panel').forEach(p => p.style.display = 'none');
    const target = document.getElementById('panel-' + panelId);
    if (target) target.style.display = 'block';

    document.querySelectorAll('.nav a').forEach(a => a.classList.remove('active'));
    if (navLink) navLink.classList.add('active');

    if (panelId === 'userManagement' && typeof loadUsers === 'function') {
        loadUsers();
        const defaultCard = document.querySelector('.role-card.active');
        if (defaultCard) selectRoleMatrix('superadmin', defaultCard);
    }
}

// FIX #6: Profile avatar initials moved inside DOMContentLoaded so the DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Set profile initials from PHP session
    const fullName = "<?php echo htmlspecialchars($_SESSION['full_name'], ENT_QUOTES); ?>";
    const initials = fullName.split(' ').map(n => n[0]).filter(Boolean).join('').substring(0, 2).toUpperCase();
    const profileBtn = document.getElementById('profileBtn');
    const dropdownAvatar = document.getElementById('dropdownAvatar');
    if (profileBtn) profileBtn.textContent = initials;
    if (dropdownAvatar) dropdownAvatar.textContent = initials;

    const sidebar     = document.getElementById('sidebar');
    const pageWrapper = document.getElementById('pageWrapper');
    const toggleBtn   = document.getElementById('toggleBtn');
    const overlay     = document.getElementById('overlay');
    const profileDD   = document.getElementById('profileDropdown');
    const profileWrap = document.getElementById('profileWrapper');

    // Clock
    function updateTime() {
        const now = new Date();
        const phTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
        let hours = phTime.getHours();
        const mins = String(phTime.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        const days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        document.getElementById('currentTime').textContent = `${hours}:${mins} ${ampm}`;
        document.getElementById('currentDate').textContent = `${days[phTime.getDay()]}, ${months[phTime.getMonth()]} ${phTime.getDate()}`;
    }
    updateTime();
    setInterval(updateTime, 1000);

    // Sidebar toggle
    toggleBtn.addEventListener('click', e => {
        e.stopPropagation();
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            pageWrapper.classList.toggle('expanded');
        }
    });

    overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); });

    document.querySelectorAll('.nav a').forEach(link => {
        link.addEventListener('click', function () {
            if (this.hasAttribute('onclick')) return;
            document.querySelectorAll('.nav a').forEach(a => a.classList.remove('active'));
            this.classList.add('active');
            if (window.innerWidth <= 768) { sidebar.classList.remove('show'); overlay.classList.remove('show'); }
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('show', 'collapsed');
            overlay.classList.remove('show');
            pageWrapper.classList.remove('expanded');
        }
    });

    // Profile dropdown
    profileBtn.addEventListener('click', e => { e.stopPropagation(); profileDD.classList.toggle('show'); });
    document.addEventListener('click', e => { if (!profileWrap.contains(e.target)) profileDD.classList.remove('show'); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') profileDD.classList.remove('show'); });
});

function toggleSubmenu(element) {
    const parent = element.parentElement;
    document.querySelectorAll('.nav ul li.open').forEach(item => { if (item !== parent) item.classList.remove('open'); });
    parent.classList.toggle('open');
}

// ════════════════════════════════════════════════════════════════════
//  USER MANAGEMENT
// ════════════════════════════════════════════════════════════════════
const API_BASE_URL = '../modules/user-creation/api';
let allUsers = [];
let currentUser = null;

const umPanelMeta = {
    users:       { title: 'USERS TABLE',          sub: 'User Management — View &amp; manage all accounts',         icon: 'fa-users' },
    permissions: { title: 'ROLES &amp; PERMISSIONS', sub: 'User Management — Access control matrix &amp; audit log', icon: 'fa-shield-halved' },
    provision:   { title: 'PROVISION',             sub: 'User Management — Approve registrations &amp; create accounts', icon: 'fa-key' },
};

function switchPageTab(tab) {
    document.getElementById('usersSection').classList.remove('active');
    document.getElementById('permissionsSection').classList.remove('active');
    document.getElementById('provisionSection').classList.remove('active');

    // Update panel header
    const meta = umPanelMeta[tab];
    if (meta) {
        document.getElementById('umPanelTitle').innerHTML = meta.title;
        document.getElementById('umPanelSub').innerHTML   = meta.sub;
        document.getElementById('umPanelIcon').className  = `fas ${meta.icon} text-white text-xl`;
    }

    if (tab === 'users') {
        document.getElementById('usersSection').classList.add('active');
        document.getElementById('addUserBtn').style.display = 'flex';
    } else if (tab === 'permissions') {
        document.getElementById('permissionsSection').classList.add('active');
        document.getElementById('addUserBtn').style.display = 'none';
    } else if (tab === 'provision') {
        document.getElementById('provisionSection').classList.add('active');
        document.getElementById('addUserBtn').style.display = 'none';
        prov_onTabActivate();
    }
}

const roleDefaults = {
    superadmin: { all: true, icon: '<i class="fas fa-crown text-violet-700 text-xs"></i>', bgClass: 'bg-violet-100', title: 'Super Admin', desc: 'Full system access — cannot be restricted' },
    registrar:  { perms: ['enrollment.view','enrollment.create','enrollment.approve','enrollment.schedule','academic.grades.view','academic.transcript','academic.subjects','users.view','reports.view','reports.export'], icon: '<i class="fas fa-file-invoice text-blue-700 text-xs"></i>', bgClass: 'bg-blue-100', title: 'Registrar', desc: 'Enrollment processing, records management, document issuance' },
    faculty:    { perms: ['enrollment.view','academic.grades.view','academic.grades.edit','users.view','reports.view'], icon: '<i class="fas fa-chalkboard-teacher text-amber-700 text-xs"></i>', bgClass: 'bg-amber-100', title: 'Faculty', desc: 'Grade encoding, class management, student records (read-only)' },
    cashier:    { perms: ['finance.view','finance.post','finance.scholarship','finance.holds','users.view','reports.view','reports.export'], icon: '<i class="fas fa-cash-register text-rose-700 text-xs"></i>', bgClass: 'bg-rose-100', title: 'Cashier', desc: 'Payment posting, receipts, financial holds, scholarship tagging' },
    librarian:  { perms: ['users.view','reports.view'], icon: '<i class="fas fa-book text-emerald-700 text-xs"></i>', bgClass: 'bg-emerald-100', title: 'Librarian', desc: 'Library resources and student lookup only' },
    student:    { perms: ['academic.grades.view','finance.view'], icon: '<i class="fas fa-user-graduate text-sky-700 text-xs"></i>', bgClass: 'bg-sky-100', title: 'Student', desc: 'View own grades and billing statements only' },
};
let currentMatrixRole = 'superadmin';

function switchPermTab(mode, btn) {
    document.querySelectorAll('.perm-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    ['matrixView','rolesView','auditView'].forEach(id => { const el = document.getElementById(id); if (el) el.classList.remove('active'); });
    const target = document.getElementById(mode + 'View');
    if (target) target.classList.add('active');
    if (mode === 'audit') {
        const filterEl = document.querySelector('#auditView select');
        auditCurrentPage = 1;
        renderAuditLog(filterEl ? filterEl.value : 'All Events', 1);
    }
}

async function selectRoleMatrix(role, card) {
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');
    currentMatrixRole = role;
    const def = roleDefaults[role];
    const iconEl = document.getElementById('matrixRoleIcon');
    // FIX #7: Was using split(' bg-') which produced broken class names.
    // Now using a separate bgClass property so there's no string-splitting needed.
    iconEl.className = `w-8 h-8 rounded-lg ${def.bgClass} flex items-center justify-center`;
    iconEl.innerHTML = def.icon;
    document.getElementById('matrixRoleTitle').innerText = def.title;
    document.getElementById('matrixRoleDesc').innerText = def.desc;
    const allBoxes = document.querySelectorAll('#permissionGrid input[type="checkbox"]');
    try {
        const res = await fetch(`${API_BASE_URL}/get_permission.php?role=${encodeURIComponent(role)}`);
        const data = await res.json();
        if (data.success && data.permissions) {
            allBoxes.forEach(cb => { if (!cb.dataset.perm) return; cb.checked = data.permissions[cb.dataset.perm] === true; });
        } else {
            allBoxes.forEach(cb => { const p = cb.dataset.perm; if (!p) return; cb.checked = def.all ? true : (def.perms || []).includes(p); });
        }
    } catch (err) {
        allBoxes.forEach(cb => { const p = cb.dataset.perm; if (!p) return; cb.checked = def.all ? true : (def.perms || []).includes(p); });
    }
    const allChecked = [...allBoxes].every(cb => cb.checked);
    document.getElementById('selectAllToggle').checked = allChecked;
}

function toggleAllPerms(checked) { document.querySelectorAll('#permissionGrid input[data-perm]').forEach(cb => cb.checked = checked); }
function toggleModule(module, btn) { const rows = document.querySelectorAll(`[data-module="${module}"] input[type="checkbox"]`); const allChecked = [...rows].every(cb => cb.checked); rows.forEach(cb => cb.checked = !allChecked); btn.textContent = allChecked ? 'Grant All' : 'Revoke All'; }

async function savePermissions() {
    const perms = {};
    document.querySelectorAll('#permissionGrid input[data-perm]').forEach(cb => { perms[cb.dataset.perm] = cb.checked; });
    try {
        const response = await fetch(`${API_BASE_URL}/save_permissions.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ role: currentMatrixRole, permissions: perms }) });
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        showToast(`✓ Permissions saved for ${roleDefaults[currentMatrixRole].title}`);
        const auditFilterEl = document.querySelector('#auditView select');
        auditCurrentPage = 1;
        renderAuditLog(auditFilterEl ? auditFilterEl.value : 'All Events', 1);
    } catch (err) { showToast('✗ Failed to save permissions: ' + err.message, 'warn'); }
}

function openNewRoleModal() { showToast('Custom role creation coming soon!'); }

let roleAssignments = {};
let selectedAssignUser = null;

function getRoleStyle(role) {
    const map = {
        superadmin: { pill: 'role-pill-superadmin', icon: 'fa-crown',              bg: 'bg-violet-100', iconColor: 'text-violet-600' },
        registrar:  { pill: 'role-pill-registrar',  icon: 'fa-file-invoice',       bg: 'bg-blue-100',   iconColor: 'text-blue-600'   },
        faculty:    { pill: 'role-pill-faculty',     icon: 'fa-chalkboard-teacher', bg: 'bg-amber-100',  iconColor: 'text-amber-600'  },
        admin:      { pill: 'role-pill-superadmin',  icon: 'fa-user-shield',        bg: 'bg-violet-100', iconColor: 'text-violet-600' },
        cashier:    { pill: 'role-pill-cashier',     icon: 'fa-cash-register',      bg: 'bg-rose-100',   iconColor: 'text-rose-600'   },
        librarian:  { pill: 'role-pill-librarian',   icon: 'fa-book',               bg: 'bg-emerald-100',iconColor: 'text-emerald-600'},
        student:    { pill: 'role-pill-student',     icon: 'fa-user-graduate',      bg: 'bg-sky-100',    iconColor: 'text-sky-600'    },
    };
    return map[role] || { pill: 'role-pill-student', icon: 'fa-user', bg: 'bg-slate-100', iconColor: 'text-slate-500' };
}

function getEffectiveRole(user) { return roleAssignments[user.user_id]?.role || user.role; }

function stampUpdatedAt(userId) {
    const now = new Date().toISOString();
    const user = allUsers.find(u => u.user_id === userId);
    if (!user) return;
    user.updated_at = now;
    if (currentUser && currentUser.user_id === userId) {
        const formatted = new Date(now).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });
        const el = document.getElementById('modalUpdatedAt');
        if (el) { el.innerText = formatted; el.style.transition = 'color 0.3s'; el.style.color = '#2563eb'; setTimeout(() => { el.style.color = ''; }, 1500); }
    }
}

function filterAssignUsers() {
    const val = document.getElementById('assignSearchInput').value.toLowerCase().trim();
    const results = document.getElementById('assignSearchResults');
    if (!allUsers.length) { results.innerHTML = '<div class="px-4 py-3 text-xs text-slate-400 text-center">No users loaded yet</div>'; results.classList.remove('hidden'); return; }
    const filtered = val ? allUsers.filter(u => { const full = (u.first_name + ' ' + (u.middle_name||'') + ' ' + u.last_name).toLowerCase(); return full.includes(val) || u.institutional_email.toLowerCase().includes(val) || u.user_id.toLowerCase().includes(val); }) : allUsers;
    if (!filtered.length) { results.innerHTML = '<div class="px-4 py-3 text-xs text-slate-400 text-center">No users found</div>'; results.classList.remove('hidden'); return; }
    const items = filtered.slice(0, 8).map(u => { const full = u.first_name + (u.middle_name ? ' ' + u.middle_name : '') + ' ' + u.last_name; const effRole = getEffectiveRole(u); const s = getRoleStyle(effRole); return `<div onclick="selectAssignUserObj('${u.user_id}')" class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50 cursor-pointer border-b border-slate-50 last:border-0"><div class="w-7 h-7 ${s.bg} rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas ${s.icon} ${s.iconColor} text-xs"></i></div><div class="flex-1 min-w-0"><p class="text-xs font-bold text-slate-800 truncate">${full}</p><p class="text-[10px] text-slate-400 truncate">${u.institutional_email}</p></div><span class="role-pill ${s.pill} text-[9px] flex-shrink-0">${effRole}</span></div>`; }).join('');
    results.innerHTML = items + (filtered.length > 8 ? `<div class="px-4 py-2 text-[10px] text-slate-400 text-center bg-slate-50">${filtered.length - 8} more — type to narrow results</div>` : '');
    results.classList.remove('hidden');
}

function selectAssignUserObj(userId) {
    const user = allUsers.find(u => u.user_id === userId);
    if (!user) return;
    selectedAssignUser = user;
    const full = user.first_name + (user.middle_name ? ' ' + user.middle_name : '') + ' ' + user.last_name;
    const effRole = getEffectiveRole(user);
    const s = getRoleStyle(effRole);
    document.getElementById('assignSearchInput').value = full;
    document.getElementById('assignSearchResults').classList.add('hidden');
    document.getElementById('selectedUserCard').classList.remove('hidden');
    document.getElementById('assignUserName').innerText = full;
    document.getElementById('assignUserEmail').innerText = user.institutional_email;
    document.getElementById('assignUserCurrentRole').innerText = effRole.charAt(0).toUpperCase() + effRole.slice(1);
    const iconEl = document.getElementById('assignUserIcon');
    iconEl.className = `w-10 h-10 ${s.bg} rounded-xl flex items-center justify-center`;
    iconEl.innerHTML = `<i class="fas ${s.icon} ${s.iconColor}"></i>`;
}

document.addEventListener('click', function(e) {
    const input = document.getElementById('assignSearchInput');
    const results = document.getElementById('assignSearchResults');
    if (input && results && !input.contains(e.target) && !results.contains(e.target)) results.classList.add('hidden');
});

async function submitRoleAssignment() {
    if (!selectedAssignUser) { showToast('⚠ Please select a user first.', 'warn'); return; }
    const newRole = document.getElementById('assignRoleSelect').value;
    if (!newRole) { showToast('⚠ Please choose a role.', 'warn'); return; }
    const full = selectedAssignUser.first_name + (selectedAssignUser.middle_name ? ' ' + selectedAssignUser.middle_name : '') + ' ' + selectedAssignUser.last_name;
    const oldRole = getEffectiveRole(selectedAssignUser);
    const reason = document.getElementById('assignReason').value.trim();
    const userId = selectedAssignUser.user_id;
    if (oldRole === newRole) { showToast('⚠ User already has that role.', 'warn'); return; }
    const btn = document.querySelector('button[onclick="submitRoleAssignment()"]');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...'; }
    try {
        const response = await fetch(`${API_BASE_URL}/update_role.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ userId, newRole, reason }) });
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        const userInMemory = allUsers.find(u => u.user_id === userId);
        if (userInMemory) { userInMemory.user_id = result.data.newUserId; userInMemory.role = newRole; userInMemory.updated_at = new Date().toISOString(); }
        roleAssignments[result.data.newUserId] = { role: newRole, previousRole: oldRole, previousId: userId, assignedDate: new Date().toISOString().slice(0, 10), assignedBy: 'admin@bcp.edu.ph' };
        stampUpdatedAt(userId); renderAuditLog(); displayUsers(allUsers); renderAssignmentTable();
        document.getElementById('assignSearchInput').value = ''; document.getElementById('assignRoleSelect').value = ''; document.getElementById('assignReason').value = ''; document.getElementById('selectedUserCard').classList.add('hidden'); selectedAssignUser = null;
        showToast(`✓ ${full} is now ${newRole} — saved to database`);
    } catch (err) { showToast('✗ Failed: ' + err.message, 'warn'); }
    finally { if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Role Assignment'; } }
}

async function revokeRole(btn, userId) {
    const user = allUsers.find(u => u.user_id === userId);
    if (!user) return;
    const full = user.first_name + (user.middle_name ? ' ' + user.middle_name : '') + ' ' + user.last_name;
    const currentRole = getEffectiveRole(user);
    const roles = ['student','faculty','admin','superadmin','registrar','cashier','librarian'];
    const otherRoles = roles.filter(r => r !== currentRole);
    const newRole = await showRolePickerModal(full, currentRole, otherRoles);
    if (!newRole) return;
    btn.disabled = true; btn.innerText = 'Saving...';
    try {
        const response = await fetch(`${API_BASE_URL}/update_role.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ userId, newRole, reason: 'Role changed via Revoke/Reassign' }) });
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        const userInMemory = allUsers.find(u => u.user_id === userId);
        if (userInMemory) { userInMemory.user_id = result.data.newUserId; userInMemory.role = newRole; userInMemory.updated_at = new Date().toISOString(); }
        roleAssignments[result.data.newUserId] = { role: newRole, previousRole: currentRole, previousId: userId, assignedDate: new Date().toISOString().slice(0, 10), assignedBy: 'admin@bcp.edu.ph' };
        displayUsers(allUsers); renderAssignmentTable(); showToast(`✓ ${full} changed from ${currentRole} → ${newRole}`);
    } catch (err) { showToast('✗ Failed: ' + err.message, 'warn'); }
    finally { btn.disabled = false; btn.innerText = 'Reassign'; }
}

async function revertRole(btn, userId, previousRole) {
    const user = allUsers.find(u => u.user_id === userId);
    if (!user) return;
    const full = user.first_name + (user.middle_name ? ' ' + user.middle_name : '') + ' ' + user.last_name;
    const currentRole = getEffectiveRole(user);

    if (!confirm(`Revert ${full} from "${currentRole}" back to "${previousRole}"?`)) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Reverting...';

    try {
        const response = await fetch(`${API_BASE_URL}/update_role.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ userId, newRole: previousRole, reason: `Reverted from '${currentRole}' back to '${previousRole}'` })
        });
        const result = await response.json();
        if (!result.success) throw new Error(result.message);

        const userInMemory = allUsers.find(u => u.user_id === userId);
        if (userInMemory) {
            userInMemory.user_id   = result.data.newUserId;
            userInMemory.role      = previousRole;
            userInMemory.updated_at = new Date().toISOString();
        }

        // Clear the previousRole tracking since we've reverted — it's back to original
        delete roleAssignments[result.data.newUserId];

        displayUsers(allUsers);
        renderAssignmentTable();
        showToast(`✓ ${full} reverted to ${previousRole}`);
    } catch (err) {
        showToast('✗ Revert failed: ' + err.message, 'warn');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-rotate-left"></i> Revert';
    }
}

function showRolePickerModal(fullName, currentRole, otherRoles) {
    return new Promise(resolve => {
        const existing = document.getElementById('rolePickerOverlay'); if (existing) existing.remove();
        const roleLabels = { student:'Student', faculty:'Faculty', admin:'Admin', superadmin:'Super Admin', registrar:'Registrar', cashier:'Cashier', librarian:'Librarian' };
        const options = otherRoles.map(r => `<option value="${r}">${roleLabels[r] || r}</option>`).join('');
        const overlay = document.createElement('div');
        overlay.id = 'rolePickerOverlay';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:2000;display:flex;align-items:center;justify-content:center;padding:1rem;';
        overlay.innerHTML = `<div style="background:white;border-radius:1.5rem;padding:2rem;width:100%;max-width:400px;box-shadow:0 25px 50px rgba(0,0,0,0.2);"><h3 style="font-size:1rem;font-weight:900;color:#1e293b;margin-bottom:0.25rem;">Reassign Role</h3><p style="font-size:0.75rem;color:#64748b;margin-bottom:1.5rem;"><strong>${fullName}</strong> is currently <strong>${currentRole}</strong>. Select a new role:</p><select id="rolePickerSelect" style="width:100%;padding:0.75rem 1rem;border:1.5px solid #e2e8f0;border-radius:0.75rem;font-size:0.875rem;font-weight:700;background:white;margin-bottom:1rem;outline:none;"><option value="">Choose new role...</option>${options}</select><div style="display:flex;gap:0.75rem;"><button id="rolePickerCancel" style="flex:1;padding:0.75rem;border:1.5px solid #e2e8f0;border-radius:0.75rem;font-size:0.8rem;font-weight:700;color:#64748b;background:white;cursor:pointer;">Cancel</button><button id="rolePickerConfirm" style="flex:1;padding:0.75rem;background:#1e3a8a;color:white;border:none;border-radius:0.75rem;font-size:0.8rem;font-weight:900;cursor:pointer;">Confirm</button></div></div>`;
        document.body.appendChild(overlay);
        document.getElementById('rolePickerCancel').onclick = () => { overlay.remove(); resolve(null); };
        document.getElementById('rolePickerConfirm').onclick = () => { const val = document.getElementById('rolePickerSelect').value; if (!val) { document.getElementById('rolePickerSelect').style.borderColor = '#ef4444'; return; } overlay.remove(); resolve(val); };
        overlay.addEventListener('click', e => { if (e.target === overlay) { overlay.remove(); resolve(null); } });
    });
}

function renderAssignmentTable(filterVal = '') {
    const tbody = document.getElementById('assignmentTableBody');
    if (!allUsers.length) return;
    let users = allUsers.filter(u => { if (!filterVal) return true; const full = (u.first_name + ' ' + (u.middle_name||'') + ' ' + u.last_name).toLowerCase(); const effRole = getEffectiveRole(u).toLowerCase(); return full.includes(filterVal) || effRole.includes(filterVal) || u.institutional_email.toLowerCase().includes(filterVal); });
    document.getElementById('assignmentCount').innerText = users.length;
    if (!users.length) { tbody.innerHTML = `<tr><td colspan="5" class="px-5 py-8 text-center text-xs text-slate-400">No users match your filter</td></tr>`; return; }
    tbody.innerHTML = users.map(u => {
        const full = u.first_name + (u.middle_name ? ' ' + u.middle_name : '') + ' ' + u.last_name;
        const effRole = getEffectiveRole(u); const s = getRoleStyle(effRole); const override = roleAssignments[u.user_id];
        const dateSource = override?.assignedDate || (u.updated_at ? u.updated_at.slice(0,10) : null) || u.created_at?.slice(0,10) || '—';
        const grantedBy = override?.assignedBy || 'System';
        const prevRole  = override?.previousRole || null;
        const prevStyle = prevRole ? getRoleStyle(prevRole) : null;

        const revertBtn = prevRole
            ? `<button onclick="revertRole(this,'${u.user_id}','${prevRole}')"
                title="Revert back to ${prevRole}"
                style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:10px;font-weight:800;cursor:pointer;border:1.5px solid #fca5a5;color:#dc2626;background:white;transition:all 0.15s;font-family:inherit;"
                onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='white'">
                <i class="fas fa-rotate-left"></i> Revert to <span class="${prevStyle.pill}" style="margin-left:3px;font-size:9px;">${prevRole}</span>
              </button>`
            : '';

        return `<tr class="hover:bg-slate-50 transition">
            <td class="px-5 py-3.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 ${s.bg} rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas ${s.icon} ${s.iconColor} text-xs"></i></div>
                    <div><p class="text-xs font-bold text-slate-800">${full}</p><p class="text-[10px] text-slate-400">${u.institutional_email}</p></div>
                </div>
            </td>
            <td class="px-5 py-3.5">
                <span class="role-pill ${s.pill}"><i class="fas ${s.icon} text-[9px]"></i> ${effRole}</span>
                ${prevRole ? `<p class="text-[9px] text-slate-400 mt-0.5">was: ${prevRole}</p>` : ''}
            </td>
            <td class="px-5 py-3.5 text-xs text-slate-500">${grantedBy}</td>
            <td class="px-5 py-3.5 text-xs font-mono text-slate-400">${dateSource}</td>
            <td class="px-5 py-3.5">
                <div class="flex items-center gap-2 justify-center flex-wrap">
                    ${revertBtn}
                    <button onclick="revokeRole(this,'${u.user_id}')"
                        style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:10px;font-weight:800;cursor:pointer;border:1.5px solid #cbd5e1;color:#475569;background:white;transition:all 0.15s;font-family:inherit;"
                        onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">
                        <i class="fas fa-arrows-rotate"></i> Reassign
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── Audit Log — live data from get_audit_log.php ────────────────────────────
let auditCurrentPage   = 1;
const AUDIT_PER_PAGE   = 10;

const roleIconMap = {
    superadmin: { iconClass: 'fas fa-crown text-violet-600',            bgClass: 'bg-violet-100'  },
    registrar:  { iconClass: 'fas fa-file-invoice text-blue-600',        bgClass: 'bg-blue-100'    },
    faculty:    { iconClass: 'fas fa-chalkboard-teacher text-amber-600', bgClass: 'bg-amber-100'   },
    admin:      { iconClass: 'fas fa-user-shield text-pink-600',         bgClass: 'bg-pink-100'    },
    cashier:    { iconClass: 'fas fa-cash-register text-rose-600',       bgClass: 'bg-rose-100'    },
    librarian:  { iconClass: 'fas fa-book text-emerald-600',             bgClass: 'bg-emerald-100' },
    student:    { iconClass: 'fas fa-user-graduate text-sky-600',        bgClass: 'bg-sky-100'     },
};

async function renderAuditLog(filter = 'All Events', page = 1) {
    const tbody = document.getElementById('auditTableBody');
    if (!tbody) return;
    auditCurrentPage = page;

    tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-10 text-center">
        <i class="fas fa-spinner fa-spin text-2xl text-slate-300 mb-2 block"></i>
        <p class="text-xs text-slate-400 font-medium">Loading audit log...</p>
    </td></tr>`;

    // Hide pagination while loading
    const paginationEl = document.getElementById('auditPagination');
    if (paginationEl) paginationEl.style.display = 'none';

    try {
        const url = `${API_BASE_URL}/get_audit_log.php?filter=${encodeURIComponent(filter)}&page=${page}&per_page=${AUDIT_PER_PAGE}`;
        const res  = await fetch(url);
        const data = await res.json();

        if (!data.success) throw new Error(data.message || 'Unknown error');

        const logs = data.logs;
        if (!logs.length) {
            tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-10 text-center">
                <i class="fas fa-check-circle text-2xl text-slate-300 mb-2 block"></i>
                <p class="text-xs text-slate-400 font-medium">No log entries found for this filter.</p>
            </td></tr>`;
            return;
        }

        tbody.innerHTML = logs.map((e, idx) => {
            const role   = (e.byRole || '').toLowerCase();
            const status = (e.status || '').toLowerCase();
            const style  = roleIconMap[role] || { iconClass: 'fas fa-user text-slate-500', bgClass: 'bg-slate-100' };
            const isPermChange = (e.event || '').toLowerCase().includes('permission');

            let badgeCls, label;
            if (status === 'success') {
                badgeCls = 'audit-badge-success'; label = 'Success';
            } else if (status === 'blocked' || status === 'failed') {
                badgeCls = 'audit-badge-blocked'; label = status.charAt(0).toUpperCase() + status.slice(1);
            } else {
                badgeCls = 'audit-badge-warning'; label = e.status || 'Info';
            }

            const ts = e.ts ? new Date(e.ts).toLocaleString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric',
                hour: 'numeric', minute: '2-digit', hour12: true
            }) : '—';

            const detailId = `audit-detail-${idx}`;

            // Parse "Granted: 'x', 'y'. Revoked: 'z'." into chip lists
            let detailHtml = '';
            if (isPermChange) {
                const detail = e.detail || '';
                const grantedMatch = detail.match(/Granted:\s*(.+?)(?:\s*\|\s*Revoked:|$)/i);
                const revokedMatch = detail.match(/Revoked:\s*(.+?)$/i);
                const noChange     = detail.toLowerCase().includes('no changes');

            // Full permission label + description map
            const permMeta = {
                'enrollment.view':       { label: 'View Enrollment Records',      desc: 'Browse student enrollment applications and statuses',                  module: 'Enrollment',          color: '#1d4ed8', bg: '#eff6ff', border: '#bfdbfe' },
                'enrollment.create':     { label: 'Create Enrollment',            desc: 'Submit new enrollment applications on behalf of students',             module: 'Enrollment',          color: '#1d4ed8', bg: '#eff6ff', border: '#bfdbfe' },
                'enrollment.approve':    { label: 'Approve / Reject Enrollment',  desc: 'Finalize or deny student enrollment requests',                        module: 'Enrollment',          color: '#1d4ed8', bg: '#eff6ff', border: '#bfdbfe' },
                'enrollment.schedule':   { label: 'Manage Schedule & Subjects',   desc: 'Assign subjects, sections, and class schedules',                      module: 'Enrollment',          color: '#1d4ed8', bg: '#eff6ff', border: '#bfdbfe' },
                'academic.grades.view':  { label: 'View Grades',                  desc: 'Access student grades and academic performance data',                 module: 'Academic Records',    color: '#15803d', bg: '#f0fdf4', border: '#bbf7d0' },
                'academic.grades.edit':  { label: 'Edit / Encode Grades',         desc: 'Enter midterm and final grades for assigned subjects',                module: 'Academic Records',    color: '#15803d', bg: '#f0fdf4', border: '#bbf7d0' },
                'academic.transcript':   { label: 'Issue Transcript of Records',  desc: 'Generate and release official TOR documents',                        module: 'Academic Records',    color: '#15803d', bg: '#f0fdf4', border: '#bbf7d0' },
                'academic.subjects':     { label: 'Manage Subjects & Curriculum', desc: 'Add, edit, or archive subjects and curriculum structures',            module: 'Academic Records',    color: '#15803d', bg: '#f0fdf4', border: '#bbf7d0' },
                'finance.view':          { label: 'View Financial Records',       desc: 'Access student balances, SOAs, and payment histories',               module: 'Finance',             color: '#b45309', bg: '#fffbeb', border: '#fde68a' },
                'finance.post':          { label: 'Post Payments',                desc: 'Record cash, online, or check payments from students',               module: 'Finance',             color: '#b45309', bg: '#fffbeb', border: '#fde68a' },
                'finance.scholarship':   { label: 'Manage Scholarships',          desc: 'Tag students as scholars and apply tuition discounts',               module: 'Finance',             color: '#b45309', bg: '#fffbeb', border: '#fde68a' },
                'finance.holds':         { label: 'Place / Lift Financial Holds', desc: 'Block or unblock enrollment due to outstanding balances',            module: 'Finance',             color: '#b45309', bg: '#fffbeb', border: '#fde68a' },
                'users.view':            { label: 'View All Users',               desc: 'Browse the full user directory (students, faculty, staff)',          module: 'User Management',     color: '#7c3aed', bg: '#faf5ff', border: '#ddd6fe' },
                'users.edit':            { label: 'Create & Edit Users',          desc: 'Add new accounts and modify user profile information',               module: 'User Management',     color: '#7c3aed', bg: '#faf5ff', border: '#ddd6fe' },
                'users.delete':          { label: 'Delete Users',                 desc: 'Permanently remove user accounts from the system',                  module: 'User Management',     color: '#7c3aed', bg: '#faf5ff', border: '#ddd6fe' },
                'users.password':        { label: 'Reset Passwords',              desc: 'Force password resets and manage account credentials',              module: 'User Management',     color: '#7c3aed', bg: '#faf5ff', border: '#ddd6fe' },
                'system.rbac':           { label: 'Manage Roles & Permissions',   desc: 'Configure RBAC roles, assign permissions, view audit logs',         module: 'System & Security',   color: '#be123c', bg: '#fff1f2', border: '#fecdd3' },
                'system.audit':          { label: 'View Audit Trail',             desc: 'Read system logs of all administrative and security actions',       module: 'System & Security',   color: '#be123c', bg: '#fff1f2', border: '#fecdd3' },
                'system.config':         { label: 'System Configuration',         desc: 'Modify system settings, academic calendar, and school parameters',  module: 'System & Security',   color: '#be123c', bg: '#fff1f2', border: '#fecdd3' },
                'system.backup':         { label: 'Database Backup & Restore',    desc: 'Trigger manual backups and restore data from backup points',        module: 'System & Security',   color: '#be123c', bg: '#fff1f2', border: '#fecdd3' },
                'reports.view':          { label: 'View Reports & Dashboards',    desc: 'Access enrollment stats, grade distributions, and financial summaries', module: 'Reports & Analytics', color: '#3730a3', bg: '#eef2ff', border: '#c7d2fe' },
                'reports.export':        { label: 'Export Reports',               desc: 'Download reports as PDF, Excel, or CSV files',                     module: 'Reports & Analytics', color: '#3730a3', bg: '#eef2ff', border: '#c7d2fe' },
            };

            // Module-to-keys map — for legacy entries that stored just the module name
            const moduleKeys = {
                'enrollment': ['enrollment.view','enrollment.create','enrollment.approve','enrollment.schedule'],
                'academic':   ['academic.grades.view','academic.grades.edit','academic.transcript','academic.subjects'],
                'finance':    ['finance.view','finance.post','finance.scholarship','finance.holds'],
                'users':      ['users.view','users.edit','users.delete','users.password'],
                'system':     ['system.rbac','system.audit','system.config','system.backup'],
                'reports':    ['reports.view','reports.export'],
            };

            const moduleOrder = [
                { key: 'enrollment', label: 'Enrollment',          icon: 'fa-graduation-cap',    color: '#1d4ed8', bg: '#eff6ff', border: '#bfdbfe' },
                { key: 'academic',   label: 'Academic Records',     icon: 'fa-book-open',          color: '#15803d', bg: '#f0fdf4', border: '#bbf7d0' },
                { key: 'finance',    label: 'Finance',              icon: 'fa-coins',              color: '#b45309', bg: '#fffbeb', border: '#fde68a' },
                { key: 'users',      label: 'User Management',      icon: 'fa-users',              color: '#7c3aed', bg: '#faf5ff', border: '#ddd6fe' },
                { key: 'system',     label: 'System & Security',    icon: 'fa-shield-halved',      color: '#be123c', bg: '#fff1f2', border: '#fecdd3' },
                { key: 'reports',    label: 'Reports & Analytics',  icon: 'fa-chart-bar',          color: '#3730a3', bg: '#eef2ff', border: '#c7d2fe' },
            ];

            const expandKeys = (str) => {
                const rawKeys = str.replace(/'/g, '').split(',').map(p => p.trim()).filter(Boolean);
                const expanded = [];
                rawKeys.forEach(k => {
                    if (moduleKeys[k]) moduleKeys[k].forEach(sub => expanded.push(sub));
                    else expanded.push(k);
                });
                return expanded;
            };

            const makeChips = (str, type) => {
                const isGrant = type === 'granted';
                const iconColor = isGrant ? '#15803d' : '#dc2626';
                const cardBg    = isGrant ? '#f0fdf4' : '#fff5f5';
                const cardBdr   = isGrant ? '#bbf7d0' : '#fecaca';
                const iconCls   = isGrant ? 'fa-plus-circle' : 'fa-minus-circle';
                const keys = expandKeys(str);

                // Group by module
                const groups = {};
                keys.forEach(key => {
                    const meta = permMeta[key];
                    const grp  = meta ? meta.module : 'Other';
                    if (!groups[grp]) groups[grp] = [];
                    groups[grp].push(key);
                });

                return moduleOrder.filter(m => groups[m.label]).map(m => {
                    const groupKeys = groups[m.label];
                    const cards = groupKeys.map(key => {
                        const meta = permMeta[key];
                        if (!meta) return `<div style="display:flex;align-items:center;gap:6px;padding:5px 10px;background:${cardBg};border:1px solid ${cardBdr};border-radius:8px;margin-bottom:4px;">
                            <i class="fas ${iconCls}" style="color:${iconColor};font-size:10px;"></i>
                            <span style="font-size:11px;font-weight:700;color:${iconColor};font-family:monospace;">${key}</span>
                        </div>`;
                        return `<div style="display:flex;align-items:flex-start;gap:8px;padding:7px 10px;background:${cardBg};border:1px solid ${cardBdr};border-radius:8px;margin-bottom:4px;">
                            <i class="fas ${iconCls}" style="color:${iconColor};font-size:11px;margin-top:2px;flex-shrink:0;"></i>
                            <div>
                                <p style="font-size:11px;font-weight:800;color:${iconColor};margin:0 0 1px;">${meta.label}</p>
                                <p style="font-size:10px;color:#94a3b8;margin:0;">${meta.desc}</p>
                            </div>
                        </div>`;
                    }).join('');

                    return `<div style="margin-bottom:10px;">
                        <div style="display:flex;align-items:center;gap:5px;padding:4px 8px;background:${m.bg};border:1px solid ${m.border};border-radius:6px;margin-bottom:5px;width:fit-content;">
                            <i class="fas ${m.icon}" style="color:${m.color};font-size:9px;"></i>
                            <span style="font-size:9px;font-weight:900;color:${m.color};text-transform:uppercase;letter-spacing:.07em;">${m.label}</span>
                            <span style="font-size:9px;font-weight:700;color:${m.color};opacity:.7;">(${groupKeys.length})</span>
                        </div>
                        ${cards}
                    </div>`;
                }).join('') + (groups['Other'] ? groups['Other'].map(key =>
                    `<div style="display:flex;align-items:center;gap:6px;padding:5px 10px;background:${cardBg};border:1px solid ${cardBdr};border-radius:8px;margin-bottom:4px;">
                        <i class="fas ${iconCls}" style="color:${iconColor};font-size:10px;"></i>
                        <span style="font-size:11px;font-weight:700;color:${iconColor};font-family:monospace;">${key}</span>
                    </div>`).join('') : '');
            };

                if (noChange) {
                    detailHtml = `<p style="font-size:12px;color:#94a3b8;font-style:italic;">No permission changes were made.</p>`;
                } else {
                    const countKeys = (str) => expandKeys(str).length;
                    const sections = [];
                    if (grantedMatch) sections.push(`
                        <div style="margin-bottom:14px;">
                            <p style="font-size:10px;font-weight:900;color:#15803d;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;display:flex;align-items:center;gap:5px;">
                                <i class="fas fa-shield-check"></i> Granted (${countKeys(grantedMatch[1])})
                            </p>
                            ${makeChips(grantedMatch[1], 'granted')}
                        </div>`);
                    if (revokedMatch) sections.push(`
                        <div>
                            <p style="font-size:10px;font-weight:900;color:#dc2626;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;display:flex;align-items:center;gap:5px;">
                                <i class="fas fa-ban"></i> Revoked (${countKeys(revokedMatch[1])})
                            </p>
                            ${makeChips(revokedMatch[1], 'revoked')}
                        </div>`);
                    detailHtml = sections.length ? sections.join('') : `<p style="font-size:12px;color:#64748b;">${detail}</p>`;
                }
            }

            const mainRow = `<tr class="${isPermChange ? 'cursor-pointer' : ''} transition" style="${isPermChange ? 'cursor:pointer;' : ''}"
                ${isPermChange ? `onclick="toggleAuditDetail('${detailId}', this)"` : ''}>
                <td class="px-6 py-4 text-xs font-mono text-slate-400 whitespace-nowrap">${ts}</td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 ${style.bgClass} rounded flex items-center justify-center flex-shrink-0">
                            <i class="${style.iconClass} text-[9px]"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-800">${e.by || '—'}</p>
                            <span class="role-pill role-pill-${role} text-[9px]">${role || '—'}</span>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div style="display:flex;align-items:center;gap:6px;">
                        ${isPermChange ? `<i class="fas fa-chevron-right audit-toggle-icon" style="font-size:9px;color:#818cf8;transition:transform 0.2s;flex-shrink:0;"></i>` : ''}
                        <div>
                            <p class="text-xs font-bold text-slate-700">${e.event || '—'}</p>
                            <p style="font-size:10px;color:#94a3b8;margin-top:2px;">${isPermChange ? (e.affected || '') : (e.detail || '')}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-xs text-slate-500">${e.affected || '—'}</td>
                <td class="px-6 py-4 text-center">
                    <span class="${badgeCls} px-2.5 py-1 rounded-lg text-[9px] font-black uppercase">${label}</span>
                </td>
            </tr>`;

            const expandRow = isPermChange ? `<tr id="${detailId}" style="display:none;">
                <td colspan="5" style="padding:0;border-bottom:2px solid #e0e7ff;">
                    <div style="background:linear-gradient(135deg,#f8faff,#eef2ff);padding:1rem 1.5rem 1rem 3.5rem;border-left:4px solid #6366f1;">
                        <p style="font-size:10px;font-weight:900;color:#6366f1;text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px;">
                            <i class="fas fa-list-check" style="margin-right:4px;"></i>Permission Changes Detail
                        </p>
                        ${detailHtml}
                    </div>
                </td>
            </tr>` : '';

            return mainRow + expandRow;
        }).join('');

        // ── Render pagination bar ────────────────────────────────────────────
        const totalPages = data.total_pages || 1;
        const total      = data.total || 0;
        const start      = total === 0 ? 0 : (page - 1) * AUDIT_PER_PAGE + 1;
        const end        = Math.min(page * AUDIT_PER_PAGE, total);
        const curFilter  = document.querySelector('#auditView select')?.value || 'All Events';

        if (paginationEl && totalPages > 1) {
            paginationEl.style.display = 'flex';
            document.getElementById('auditPageInfo').textContent =
                `Showing ${start}–${end} of ${total} entries`;

            const btns = document.getElementById('auditPageBtns');
            const btnStyle = (active) =>
                `display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:28px;padding:0 8px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;border:1.5px solid;transition:all 0.15s;font-family:inherit;` +
                (active
                    ? `background:linear-gradient(135deg,#0d2470,#1535a0);color:white;border-color:#1535a0;`
                    : `background:white;color:#475569;border-color:#e2e8f0;`);

            // Build page number list with ellipsis
            const pageNums = [];
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                    pageNums.push(i);
                } else if (pageNums[pageNums.length - 1] !== '...') {
                    pageNums.push('...');
                }
            }

            btns.innerHTML =
                // Prev
                `<button onclick="renderAuditLog('${curFilter}', ${page - 1})" ${page === 1 ? 'disabled' : ''}
                    style="${btnStyle(false)}opacity:${page === 1 ? '.4' : '1'};">
                    <i class="fas fa-chevron-left" style="font-size:9px;"></i>
                </button>` +
                // Page numbers
                pageNums.map(n => n === '...'
                    ? `<span style="font-size:11px;color:#94a3b8;padding:0 4px;">…</span>`
                    : `<button onclick="renderAuditLog('${curFilter}', ${n})"
                        style="${btnStyle(n === page)}">${n}</button>`
                ).join('') +
                // Next
                `<button onclick="renderAuditLog('${curFilter}', ${page + 1})" ${page === totalPages ? 'disabled' : ''}
                    style="${btnStyle(false)}opacity:${page === totalPages ? '.4' : '1'};">
                    <i class="fas fa-chevron-right" style="font-size:9px;"></i>
                </button>`;
        } else if (paginationEl && total > 0) {
            // Show count even when there's only 1 page
            paginationEl.style.display = 'flex';
            document.getElementById('auditPageInfo').textContent = `Showing all ${total} entries`;
            document.getElementById('auditPageBtns').innerHTML = '';
        }

    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-10 text-center">
            <i class="fas fa-exclamation-triangle text-2xl text-amber-300 mb-2 block"></i>
            <p class="text-xs text-red-400 font-medium">Failed to load audit log: ${err.message}</p>
            <button onclick="renderAuditLog()" class="mt-2 px-3 py-1 text-[10px] font-bold text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 transition">Retry</button>
        </td></tr>`;
    }
}

function toggleAuditDetail(detailId, mainRow) {
    const detailRow  = document.getElementById(detailId);
    const chevron    = mainRow.querySelector('.audit-toggle-icon');
    const isOpen     = detailRow.style.display !== 'none';

    detailRow.style.display = isOpen ? 'none' : 'table-row';
    if (chevron) chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
    mainRow.style.background = isOpen ? '' : '#eef2ff';
}

function showToast(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = `fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl text-sm font-bold shadow-xl transition-all ${type === 'warn' ? 'bg-amber-500 text-white' : 'bg-slate-800 text-white'}`;
    t.innerText = msg; document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 2500);
}

async function loadUsers() {
    const roleEl = document.getElementById('roleFilter');
    const searchEl = document.getElementById('searchInput');
    if (!roleEl || !searchEl) return;
    const role = roleEl.value; const search = searchEl.value;
    try {
        document.getElementById('usersTableBody').innerHTML = `<tr><td colspan="8" class="px-6 py-12 text-center"><i class="fas fa-spinner fa-spin text-4xl text-slate-300 mb-3"></i><p class="text-slate-400 font-medium">Loading users...</p></td></tr>`;
        let url = `${API_BASE_URL}/get_users.php?role=${role}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        const response = await fetch(url);
        const data = await response.json();
        if (data.success) { allUsers = data.users; displayUsers(data.users); renderAssignmentTable(); }
        else throw new Error(data.message);
    } catch (error) {
        document.getElementById('usersTableBody').innerHTML = `<tr><td colspan="8" class="px-6 py-12 text-center"><i class="fas fa-exclamation-triangle text-4xl text-red-300 mb-3"></i><p class="text-red-400 font-medium">Error: ${error.message}</p><p class="text-slate-400 text-sm mt-1">Make sure XAMPP is running.</p></td></tr>`;
    }
}

const roleCardConfig = {
    student:    { label:'Students',    icon:'fa-user-graduate',      bg:'bg-blue-100',    iconColor:'text-blue-600'    },
    faculty:    { label:'Faculty',     icon:'fa-chalkboard-teacher', bg:'bg-amber-100',   iconColor:'text-amber-600'   },
    admin:      { label:'Admin',       icon:'fa-user-shield',        bg:'bg-pink-100',    iconColor:'text-pink-600'    },
    superadmin: { label:'Super Admin', icon:'fa-crown',              bg:'bg-violet-100',  iconColor:'text-violet-600'  },
    registrar:  { label:'Registrar',   icon:'fa-file-invoice',       bg:'bg-indigo-100',  iconColor:'text-indigo-600'  },
    cashier:    { label:'Cashier',     icon:'fa-cash-register',      bg:'bg-rose-100',    iconColor:'text-rose-600'    },
    librarian:  { label:'Librarian',   icon:'fa-book',               bg:'bg-emerald-100', iconColor:'text-emerald-600' },
};

function displayUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    if (users.length === 0) { tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-12 text-center"><i class="fas fa-users-slash text-4xl text-slate-300 mb-3"></i><p class="text-slate-400 font-medium">No users found</p></td></tr>`; return; }
    tbody.innerHTML = users.map(user => {
        const fullName = `${user.first_name} ${user.middle_name ? user.middle_name + ' ' : ''}${user.last_name}`;
        const effRole = getEffectiveRole(user);
        const roleBadgeMap = { student:'badge-student', faculty:'badge-faculty', admin:'badge-admin', superadmin:'badge-superadmin', registrar:'badge-registrar', cashier:'badge-cashier', librarian:'badge-librarian' };
        const roleBadge = roleBadgeMap[effRole] || 'badge-admin';
        const enrolledDate = new Date(user.created_at).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
        const isOverridden = !!roleAssignments[user.user_id];
        const lsKey = (user.life_status || '').toLowerCase();
        const lsStyles = { active:'background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;', alumni:'background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;', dropped:'background:#fef9c3;color:#854d0e;border:1px solid #fde047;', terminated:'background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;' };
        const lsPill = user.life_status
            ? `<span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:10px;font-weight:800;${lsStyles[lsKey]||'background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;'}">${user.life_status}</span>`
            : `<span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:10px;font-weight:800;background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0;">—</span>`;
        return `<tr class="table-row"><td class="px-6 py-4"><span class="font-mono text-sm font-bold text-slate-700">${user.user_id}</span></td><td class="px-6 py-4"><p class="font-bold text-slate-800">${fullName}</p><p class="text-xs text-slate-400">${user.gender || 'N/A'}</p></td><td class="px-6 py-4"><span class="badge ${roleBadge}">${effRole}</span>${isOverridden ? '<span class="ml-1 text-[9px] font-bold text-amber-500 uppercase tracking-wide">override</span>' : ''}</td><td class="px-6 py-4">${lsPill}</td><td class="px-6 py-4"><p class="text-sm font-medium text-slate-700">${user.institutional_email}</p><p class="text-xs text-slate-400">${user.personal_email || 'N/A'}</p></td><td class="px-6 py-4"><p class="text-sm text-slate-600">${user.mobile_number || 'N/A'}</p></td><td class="px-6 py-4"><p class="text-sm text-slate-600">${enrolledDate}</p></td><td class="px-6 py-4 text-center"><button onclick="viewUser('${user.user_id}')" class="text-blue-600 hover:text-blue-800 mx-1" title="View Details"><i class="fas fa-eye"></i></button><button onclick='deleteUser("${user.user_id}", "${fullName.replace(/"/g, '&quot;')}")' class="text-red-600 hover:text-red-800 mx-1" title="Delete User"><i class="fas fa-trash"></i></button></td></tr>`;
    }).join('');
}

function filterUsers() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    let filtered = allUsers;
    if (roleFilter !== 'all') filtered = filtered.filter(u => getEffectiveRole(u) === roleFilter);
    if (searchTerm) filtered = filtered.filter(u => { const full = `${u.first_name} ${u.middle_name || ''} ${u.last_name}`.toLowerCase(); return full.includes(searchTerm) || u.user_id.toLowerCase().includes(searchTerm) || u.institutional_email.toLowerCase().includes(searchTerm); });
    displayUsers(filtered);
}

function viewUser(userId) {
    const user = allUsers.find(u => u.user_id === userId);
    if (!user) return;
    currentUser = user;
    const fullName = `${user.first_name} ${user.middle_name ? user.middle_name + ' ' : ''}${user.last_name}`;
    const effRole = getEffectiveRole(user);
    document.getElementById('modalFullName').innerText = fullName;
    document.getElementById('modalUserId').innerText = `ID: ${user.user_id}`;
    document.getElementById('modalRoleBadge').innerText = effRole.toUpperCase();
    const photoIcon = document.getElementById('modalPhotoIcon');
    if (effRole === 'student') photoIcon.className = 'fas fa-user-graduate text-5xl text-blue-600';
    else if (effRole === 'faculty') photoIcon.className = 'fas fa-chalkboard-teacher text-5xl text-amber-600';
    else if (effRole === 'superadmin') photoIcon.className = 'fas fa-crown text-5xl text-violet-600';
    else if (effRole === 'cashier') photoIcon.className = 'fas fa-cash-register text-5xl text-rose-500';
    else if (effRole === 'librarian') photoIcon.className = 'fas fa-book text-5xl text-emerald-600';
    else photoIcon.className = 'fas fa-user-shield text-5xl text-pink-600';
    document.getElementById('modalInstitutionalEmail').innerText = user.institutional_email || 'N/A';
    document.getElementById('modalPersonalEmail').innerText = user.personal_email || 'N/A';
    document.getElementById('modalMobileNumber').innerText = user.mobile_number || 'N/A';
    document.getElementById('modalBirthDate').innerText = user.birth_date ? new Date(user.birth_date).toLocaleDateString('en-US', { month:'long', day:'numeric', year:'numeric' }) : 'N/A';
    document.getElementById('modalGender').innerText = user.gender || 'N/A';
    if (['faculty','admin','superadmin','registrar','cashier','librarian'].includes(effRole) && user.employee_id) { document.getElementById('modalEmployeeIdRow').style.display = 'flex'; document.getElementById('modalEmployeeId').innerText = user.employee_id; }
    else { document.getElementById('modalEmployeeIdRow').style.display = 'none'; }
    document.getElementById('modalLrnRow').style.display = 'none';
    ['modalMajorRow','modalStatusRow','modalSpecializationRow','modalEmploymentTypeRow','modalAccessLevelRow','modalLifeStatusRow'].forEach(id => { document.getElementById(id).style.display = 'none'; });
    // Life status pill in modal header area
    if (user.life_status) {
        const lsKey = user.life_status.toLowerCase();
        const lsColors = { active:'#15803d', alumni:'#1d4ed8', dropped:'#854d0e', terminated:'#b91c1c' };
        const lsBg     = { active:'#dcfce7', alumni:'#dbeafe', dropped:'#fef9c3', terminated:'#fee2e2' };
        const el = document.getElementById('modalLifeStatus');
        el.innerHTML = `<span style="padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:800;background:${lsBg[lsKey]||'#f1f5f9'};color:${lsColors[lsKey]||'#64748b'}">${user.life_status}</span>`;
        document.getElementById('modalLifeStatusRow').style.display = 'flex';
    }
    if (effRole === 'student') {
        document.getElementById('modalAcademicTitle').innerHTML = '<i class="fas fa-graduation-cap"></i> Academic Information';
        document.getElementById('modalProgramLabel').innerText = 'Program'; document.getElementById('modalProgram').innerText = user.department_program || 'N/A';
        document.getElementById('modalYearRow').style.display = 'flex'; document.getElementById('modalYearLabel').innerText = 'Year Level'; document.getElementById('modalYear').innerText = user.year_position || 'N/A';
        if (user.major) { document.getElementById('modalMajorRow').style.display = 'flex'; document.getElementById('modalMajor').innerText = user.major; }
        if (user.enrollment_status) { document.getElementById('modalStatusRow').style.display = 'flex'; document.getElementById('modalStatus').innerText = user.enrollment_status; }
        document.getElementById('modalGuardianSection').style.display = 'block';
        document.getElementById('modalGuardianName').innerText = user.guardian_name || 'N/A'; document.getElementById('modalGuardianContact').innerText = user.guardian_contact || 'N/A'; document.getElementById('modalGuardianRelationship').innerText = user.guardian_relationship || 'N/A';
    } else if (effRole === 'faculty') {
        document.getElementById('modalAcademicTitle').innerHTML = '<i class="fas fa-briefcase"></i> Employment Information';
        document.getElementById('modalProgramLabel').innerText = 'Department'; document.getElementById('modalProgram').innerText = user.department_program || 'N/A';
        document.getElementById('modalYearRow').style.display = 'flex'; document.getElementById('modalYearLabel').innerText = 'Employment Type'; document.getElementById('modalYear').innerText = user.employment_type || 'N/A';
        if (user.specialization) { document.getElementById('modalSpecializationRow').style.display = 'flex'; document.getElementById('modalSpecialization').innerText = user.specialization; }
       document.getElementById('modalEmploymentTypeRow').style.display = 'none';
        document.getElementById('modalGuardianSection').style.display = 'none';
    } else {
        document.getElementById('modalAcademicTitle').innerHTML = '<i class="fas fa-briefcase"></i> Employment Information';
        document.getElementById('modalProgramLabel').innerText = 'Department'; document.getElementById('modalProgram').innerText = user.department_program || 'N/A';
        document.getElementById('modalYearRow').style.display = 'flex'; document.getElementById('modalYearLabel').innerText = 'Employment Type'; document.getElementById('modalYear').innerText = user.employment_type || 'N/A';
        if (user.access_level) { document.getElementById('modalAccessLevelRow').style.display = 'flex'; document.getElementById('modalAccessLevel').innerText = user.access_level; }
        document.getElementById('modalGuardianSection').style.display = 'none';
    }
    document.getElementById('modalStreetAddress').innerText = user.street_address || 'N/A';
    document.getElementById('modalBarangay').innerText = user.barangay || 'N/A';
    document.getElementById('modalCity').innerText = user.city || 'N/A';
    document.getElementById('modalProvince').innerText = user.province || 'N/A';
    document.getElementById('modalZipCode').innerText = user.zip_code || 'N/A';
    document.getElementById('modalCreatedAt').innerText = new Date(user.created_at).toLocaleString('en-US', { month:'long', day:'numeric', year:'numeric', hour:'numeric', minute:'numeric', hour12:true });
    document.getElementById('modalUpdatedAt').innerText = user.updated_at ? new Date(user.updated_at).toLocaleString('en-US', { month:'long', day:'numeric', year:'numeric', hour:'numeric', minute:'numeric', hour12:true }) : 'N/A';
    openModal();
}

function openModal() { document.getElementById('userModal').classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeModal() { document.getElementById('userModal').classList.remove('active'); document.body.style.overflow = 'auto'; currentUser = null; }
function closeModalOnBackdrop(event) { if (event.target.id === 'userModal') closeModal(); }

// FIX #9: Replaced browser confirm() with a custom modal for delete confirmation
// to avoid issues in restricted contexts (iframes, some browsers).
async function deleteUser(userId, fullName) {
    const confirmed = await showConfirmModal(`Delete ${fullName} (${userId})? This cannot be undone.`);
    if (!confirmed) return;
    try {
        const response = await fetch(`${API_BASE_URL}/delete_user.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ userId }) });
        const result = await response.json();
        if (result.success) { showToast('✓ User deleted successfully!'); loadUsers(); renderAuditLog(); }
        else showToast('✗ Error: ' + result.message, 'warn');
    } catch (error) { showToast('✗ Connection Error: ' + error.message, 'warn'); }
}

function showConfirmModal(message) {
    return new Promise(resolve => {
        const existing = document.getElementById('confirmModalOverlay'); if (existing) existing.remove();
        const overlay = document.createElement('div');
        overlay.id = 'confirmModalOverlay';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:3000;display:flex;align-items:center;justify-content:center;padding:1rem;';
        overlay.innerHTML = `<div style="background:white;border-radius:1.5rem;padding:2rem;width:100%;max-width:400px;box-shadow:0 25px 50px rgba(0,0,0,0.2);">
            <div style="width:3rem;height:3rem;background:#fee2e2;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
                <i class="fas fa-trash text-red-600"></i>
            </div>
            <h3 style="font-size:1rem;font-weight:900;color:#1e293b;margin-bottom:0.5rem;">Confirm Delete</h3>
            <p style="font-size:0.8rem;color:#64748b;margin-bottom:1.5rem;">${message}</p>
            <div style="display:flex;gap:0.75rem;">
                <button id="confirmCancel" style="flex:1;padding:0.75rem;border:1.5px solid #e2e8f0;border-radius:0.75rem;font-size:0.8rem;font-weight:700;color:#64748b;background:white;cursor:pointer;">Cancel</button>
                <button id="confirmDelete" style="flex:1;padding:0.75rem;background:#dc2626;color:white;border:none;border-radius:0.75rem;font-size:0.8rem;font-weight:900;cursor:pointer;">Delete</button>
            </div>
        </div>`;
        document.body.appendChild(overlay);
        document.getElementById('confirmCancel').onclick = () => { overlay.remove(); resolve(false); };
        document.getElementById('confirmDelete').onclick = () => { overlay.remove(); resolve(true); };
        overlay.addEventListener('click', e => { if (e.target === overlay) { overlay.remove(); resolve(false); } });
    });
}

function deleteUserFromModal() {
    if (!currentUser) return;
    const fullName = `${currentUser.first_name} ${currentUser.middle_name ? currentUser.middle_name + ' ' : ''}${currentUser.last_name}`;
    closeModal(); deleteUser(currentUser.user_id, fullName);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ════════════════════════════════════════════════════════════════════
// PROVISION TAB — Account Provisioning (ported from user-creation.html)
// All identifiers prefixed with prov_ / prov- to avoid conflicts
// ════════════════════════════════════════════════════════════════════
const PROV_API = '../modules/integration/api';
let prov_allStudents     = [];
let prov_pendingStudents = [];

/* Called when the Provision page-tab becomes active */
function prov_onTabActivate() {
    prov_loadAll();
}

/* Inner tab switching (Pending Approval ↔ Create Accounts) */
function prov_switchTab(name) {
    document.querySelectorAll('.prov-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.prov-tab-section').forEach(s => { s.classList.remove('active'); s.style.display = 'none'; });
    document.getElementById('prov-tab-' + name).classList.add('active');
    const sec = document.getElementById('prov-section-' + name);
    sec.style.display = 'block'; sec.classList.add('active');
    document.getElementById('prov-createAllBtn').style.display = name === 'provision' ? 'flex' : 'none';
}

async function prov_loadAll() {
    await Promise.all([prov_loadPending(), prov_loadApproved()]);
}

/* ── Pending Approval ── */
async function prov_loadPending() {
    const tbody = document.getElementById('prov-pendingTableBody');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-16"><i class="fas fa-spinner fa-spin text-amber-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>`;
    try {
        const res  = await fetch(`${PROV_API}/get_pending_registrations.php`);
        const data = await res.json();
        if (data.success) {
            prov_pendingStudents = data.students || [];
            document.getElementById('prov-statPendingApproval').textContent = prov_pendingStudents.length;
            document.getElementById('prov-pendingBadge').textContent         = prov_pendingStudents.length;
            prov_setBadge(prov_pendingStudents.length);
            prov_renderPendingTable(prov_pendingStudents);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-red-400 font-semibold text-sm">${data.message}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-red-400 font-semibold text-sm">Connection Error: ${err.message}</td></tr>`;
    }
}

function prov_renderPendingTable(students) {
    const tbody = document.getElementById('prov-pendingTableBody');
    if (!students.length) {
            tbody.innerHTML = `<tr><td colspan="6"><div style="text-align:center;padding:3rem 2rem;">
            <i class="fas fa-inbox" style="color:#fde68a;font-size:1.75rem;margin-bottom:0.75rem;display:block;"></i>
            <p style="font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:900;color:#0d2470;text-transform:uppercase;">No Pending Registrations</p>
            <p style="color:#94a3b8;font-size:0.8rem;margin-top:0.25rem;">All registrations have been reviewed.</p>
        </div></td></tr>`;
        return;
    }
    tbody.innerHTML = students.map(s => {
        const fullName = `${s.first_name}${s.middle_name ? ' ' + s.middle_name.charAt(0) + '.' : ''} ${s.last_name}`;
        const regDate  = s.registered_at ? new Date(s.registered_at).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : 'N/A';
        const initials = s.first_name.charAt(0) + s.last_name.charAt(0);
        return `
        <tr class="prov-tbl-row" id="prov-prow-${s.student_id}">
            <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#d97706,#f59e0b);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-size:12px;font-weight:900;color:white;">${initials}</span>
                    </div>
                    <div>
                        <p style="font-weight:700;font-size:0.875rem;color:#334155;">${fullName}</p>
                        <p style="font-size:10px;color:#94a3b8;font-weight:600;">${s.mobile_number || '—'}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:600;color:#475569;">${s.program || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:700;color:#64748b;">${s.year_level || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;color:#64748b;">${s.personal_email || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;color:#94a3b8;font-weight:600;">${regDate}</span></td>
            <td class="px-4 py-4 text-center">
                <button class="prov-btn-approve" id="prov-abtn-${s.student_id}" onclick="prov_approveStudent('${s.student_id}')">
                    <i class="fas fa-check mr-1"></i>Approve
                </button>
                <button class="prov-btn-reject" id="prov-rbtn-${s.student_id}" onclick="prov_rejectStudent('${s.student_id}', '${fullName.replace(/'/g,"\\'")}')">
                    <i class="fas fa-times mr-1"></i>Reject
                </button>
            </td>
        </tr>`;
    }).join('');
}

function prov_filterPending() {
    if (prov_currentType === 'staff') { prov_filterStaffPending(); return; }
    const q = document.getElementById('prov-searchPending').value.toLowerCase();
    prov_renderPendingTable(q ? prov_pendingStudents.filter(s =>
        `${s.first_name} ${s.last_name}`.toLowerCase().includes(q) ||
        (s.program || '').toLowerCase().includes(q)
    ) : prov_pendingStudents);
}

async function prov_approveStudent(studentId) {
    const abtn = document.getElementById(`prov-abtn-${studentId}`);
    const rbtn = document.getElementById(`prov-rbtn-${studentId}`);
    abtn.disabled = rbtn.disabled = true;
    abtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Approving...';
    try {
        const res    = await fetch(`${PROV_API}/approve_registration.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ student_id: studentId, action: 'approve' }) });
        const result = await res.json();
        if (result.success) {
            const row = document.getElementById(`prov-prow-${studentId}`);
            row.style.background = '#f0fdf4'; row.style.opacity = '0.5';
            setTimeout(() => row.remove(), 600);
            prov_pendingStudents = prov_pendingStudents.filter(s => s.student_id != studentId);
            document.getElementById('prov-statPendingApproval').textContent = prov_pendingStudents.length;
            document.getElementById('prov-pendingBadge').textContent         = prov_pendingStudents.length;
            prov_setBadge(prov_pendingStudents.length);
            showToast('✓ Student approved! Now visible in Create Accounts tab.');
                await prov_loadApproved();
                prov_switchTab('provision');
        } else {
            alert('Failed: ' + result.message);
            abtn.disabled = rbtn.disabled = false;
            abtn.innerHTML = '<i class="fas fa-check mr-1"></i>Approve';
        }
    } catch (err) {
        alert('Connection Error: ' + err.message);
        abtn.disabled = rbtn.disabled = false;
        abtn.innerHTML = '<i class="fas fa-check mr-1"></i>Approve';
    }
}

async function prov_rejectStudent(studentId, fullName) {
    if (!confirm(`Reject registration for ${fullName}?\n\nThis will mark their application as rejected.`)) return;
    const abtn = document.getElementById(`prov-abtn-${studentId}`);
    const rbtn = document.getElementById(`prov-rbtn-${studentId}`);
    abtn.disabled = rbtn.disabled = true;
    rbtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Rejecting...';
    try {
        const res    = await fetch(`${PROV_API}/approve_registration.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ student_id: studentId, action: 'reject' }) });
        const result = await res.json();
        if (result.success) {
            const row = document.getElementById(`prov-prow-${studentId}`);
            row.style.background = '#fee2e2'; row.style.opacity = '0.5';
            setTimeout(() => row.remove(), 600);
            prov_pendingStudents = prov_pendingStudents.filter(s => s.student_id != studentId);
            document.getElementById('prov-statPendingApproval').textContent = prov_pendingStudents.length;
            document.getElementById('prov-pendingBadge').textContent         = prov_pendingStudents.length;
            prov_setBadge(prov_pendingStudents.length);
            showToast('✗ Registration rejected.', 'warn');
        } else {
            alert('Failed: ' + result.message);
            abtn.disabled = rbtn.disabled = false;
            rbtn.innerHTML = '<i class="fas fa-times mr-1"></i>Reject';
        }
    } catch (err) {
        alert('Connection Error: ' + err.message);
        abtn.disabled = rbtn.disabled = false;
        rbtn.innerHTML = '<i class="fas fa-times mr-1"></i>Reject';
    }
}

/* ── Create Accounts ── */
async function prov_loadApproved() {
    const tbody = document.getElementById('prov-studentsTableBody');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-16"><i class="fas fa-spinner fa-spin text-blue-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>`;
    try {
        const res  = await fetch(`${PROV_API}/check_registration_status.php`);
        const data = await res.json();
        if (data.success) {
            prov_allStudents = data.students || [];
            prov_updateStats(data.stats || {});
            prov_renderTable(prov_allStudents);
        } else {
            prov_showError(data.message || 'Failed to load students.');
        }
    } catch (err) {
        prov_showError('Connection Error — Make sure XAMPP is running.\n' + err.message);
    }
}

function prov_updateStats(s) {
    document.getElementById('prov-statPending').textContent  = s.pending ?? '0';
    document.getElementById('prov-statCreated').textContent  = s.today   ?? '0';
    document.getElementById('prov-statTotal').textContent    = s.total   ?? '0';
    document.getElementById('prov-approvedBadge').textContent = s.pending ?? '0';
    document.getElementById('prov-createAllBtn').disabled    = (s.pending ?? 0) === 0;
}

function prov_renderTable(students) {
    const tbody = document.getElementById('prov-studentsTableBody');
    if (!students.length) {
            tbody.innerHTML = `<tr><td colspan="6"><div style="text-align:center;padding:4rem 2rem;">
            <div style="width:56px;height:56px;background:#eef2ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i class="fas fa-check-circle" style="color:#60a5fa;font-size:1.5rem;"></i>
            </div>
            <p style="font-family:'Barlow Condensed',sans-serif;font-size:1.2rem;font-weight:900;color:#0d2470;text-transform:uppercase;">All Caught Up!</p>
            <p style="color:#94a3b8;font-size:0.875rem;margin-top:0.25rem;">No approved students are waiting for account creation.</p>
        </div></td></tr>`;
        return;
    }
    tbody.innerHTML = students.map(s => {
        const fullName = `${s.first_name}${s.middle_name ? ' ' + s.middle_name.charAt(0) + '.' : ''} ${s.last_name}`;
        const regDate  = s.registered_at ? new Date(s.registered_at).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : 'N/A';
        const initials = s.first_name.charAt(0) + s.last_name.charAt(0);
        return `
        <tr class="prov-tbl-row" id="prov-row-${s.student_id}">
            <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0d2470,#1535a0);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-size:12px;font-weight:900;color:white;">${initials}</span>
                    </div>
                    <div>
                        <p style="font-weight:700;font-size:0.875rem;color:#334155;">${fullName}</p>
                        <p style="font-size:10px;color:#94a3b8;font-weight:600;">${s.mobile_number || '—'}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:600;color:#475569;">${s.program || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:700;color:#64748b;">${s.year_level || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;color:#64748b;">${s.personal_email || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;color:#94a3b8;font-weight:600;">${regDate}</span></td>
            <td class="px-4 py-4 text-center">
                <button class="prov-btn-create" id="prov-btn-${s.student_id}" onclick="prov_createAccount('${s.student_id}')">
                    <i class="fas fa-key mr-1"></i> Create Account
                </button>
            </td>
        </tr>`;
    }).join('');
}

function prov_filterTable() {
    if (prov_currentType === 'staff') { prov_filterStaffTable(); return; }
    const q = document.getElementById('prov-searchInput').value.toLowerCase();
    prov_renderTable(q ? prov_allStudents.filter(s =>
        `${s.first_name} ${s.last_name}`.toLowerCase().includes(q) ||
        (s.program || '').toLowerCase().includes(q)
    ) : prov_allStudents);
}

async function prov_createAccount(studentId) {
    const btn  = document.getElementById(`prov-btn-${studentId}`);
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Creating...';
    try {
        const res    = await fetch(`${PROV_API}/provision_account.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ student_id: studentId }) });
        const result = await res.json();
        if (result.success) {
            prov_markRowCreated(studentId);
            prov_allStudents = prov_allStudents.filter(s => s.student_id != studentId);
            const sp = document.getElementById('prov-statPending');
            sp.textContent = Math.max(0, parseInt(sp.textContent || 0) - 1);
            document.getElementById('prov-approvedBadge').textContent = Math.max(0, parseInt(document.getElementById('prov-approvedBadge').textContent || 0) - 1);
            const sc = document.getElementById('prov-statCreated');
            sc.textContent = parseInt(sc.textContent || 0) + 1;
            document.getElementById('prov-createAllBtn').disabled = prov_allStudents.length === 0;
            prov_showSuccessModal(result.credentials, result.personal_email);
        } else {
            alert('❌ Failed\n\n' + result.message);
            btn.disabled = false; btn.innerHTML = orig;
        }
    } catch (err) {
        alert('❌ Connection Error\n\n' + err.message);
        btn.disabled = false; btn.innerHTML = orig;
    }
}

function prov_markRowCreated(studentId) {
    const row = document.getElementById(`prov-row-${studentId}`);
    if (!row) return;
    const btn = document.getElementById(`prov-btn-${studentId}`);
    if (btn) { btn.innerHTML = '<i class="fas fa-check mr-1"></i> Created'; btn.style.background = 'linear-gradient(135deg,#15803d,#16a34a)'; btn.disabled = true; }
    setTimeout(() => { if (row) row.remove(); }, 1500);
}

async function prov_createAllAccounts() {
    if (!prov_allStudents.length) { showToast('No approved students to provision.'); return; }
    if (!confirm(`Create accounts for all ${prov_allStudents.length} approved student(s)?`)) return;
    const btn = document.getElementById('prov-createAllBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Creating All...';
    let ok = 0, fail = 0;
    for (const s of [...prov_allStudents]) {
        try {
            const res    = await fetch(`${PROV_API}/provision_account.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ student_id: s.student_id }) });
            const result = await res.json();
            if (result.success) { prov_markRowCreated(s.student_id); prov_allStudents = prov_allStudents.filter(x => x.student_id != s.student_id); ok++; }
            else fail++;
        } catch { fail++; }
    }
    document.getElementById('prov-statPending').textContent   = prov_allStudents.length;
    document.getElementById('prov-approvedBadge').textContent  = prov_allStudents.length;
    document.getElementById('prov-statCreated').textContent    = parseInt(document.getElementById('prov-statCreated').textContent || 0) + ok;
    btn.innerHTML = '<i class="fas fa-bolt"></i><span>Create All Accounts</span>';
    btn.disabled  = prov_allStudents.length === 0;
    showToast(`✓ ${ok} account(s) created${fail ? ` · ${fail} failed` : ''}.`);
}

/* ── Modal & Helpers ── */
function prov_showSuccessModal(data, personalEmail, type = 'student') {
    document.getElementById('prov-modalSentEmail').textContent = personalEmail || '—';
    const isStaff   = type === 'staff';
    const idLabel   = isStaff ? 'Staff ID' : 'Student ID';
    const typeLabel = isStaff ? (data.role || 'Staff') : 'Student';
    document.getElementById('prov-modalSubtitle').textContent  = `${typeLabel} account created successfully`;
    document.getElementById('prov-modalTypeBadge').textContent = typeLabel;
    const creds = [
        { icon:'fa-user',     iconColor:'#94a3b8', label:'Full Name',          value: data.fullName },
        { icon:'fa-id-badge', iconColor:'#22d3ee', label: idLabel,             value: data.userId },
        { icon:'fa-envelope', iconColor:'#60a5fa', label:'Institutional Email', value: data.institutionalEmail },
        { icon:'fa-key',      iconColor:'#fbbf24', label:'Temporary Password',  value: data.temporaryPassword },
    ];
    document.getElementById('prov-modalCreds').innerHTML = creds.map(c => `
        <div class="prov-cred-item">
            <div style="width:32px;height:32px;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.75rem;background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.15);">
                <i class="fas ${c.icon}" style="color:${c.iconColor}"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <span style="font-size:8.5px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#3b5a8a;display:block;margin-bottom:2px;">${c.label}</span>
                <span style="font-size:0.8rem;font-weight:700;color:#cbd5e1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;" title="${c.value}">${c.value}</span>
            </div>
            <button class="prov-copy-btn" onclick="prov_copyVal(this,'${c.value.replace(/'/g,"\\'")}')">
                <i class="fas fa-copy mr-1"></i>Copy
            </button>
        </div>`).join('');
    const modal = document.getElementById('prov-successModal');
    modal.style.display = 'flex';
}

function prov_closeModal() { document.getElementById('prov-successModal').style.display = 'none'; }

function prov_copyVal(btn, val) {
    navigator.clipboard.writeText(val).then(() => {
        btn.classList.add('copied'); btn.innerHTML = '<i class="fas fa-check mr-1"></i>Copied';
        setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = '<i class="fas fa-copy mr-1"></i>Copy'; }, 2000);
    });
}

function prov_showError(msg) {
    document.getElementById('prov-studentsTableBody').innerHTML = `<tr><td colspan="6"><div style="text-align:center;padding:4rem 2rem;">
        <i class="fas fa-exclamation-triangle" style="color:#fbbf24;font-size:1.75rem;margin-bottom:0.75rem;display:block;"></i>
        <p style="color:#64748b;font-size:0.875rem;font-weight:600;">${msg}</p>
        <button onclick="prov_loadAll()" style="margin-top:1rem;padding:0.5rem 1.25rem;font-size:0.75rem;font-weight:700;color:#1d4ed8;border:1.5px solid #bfdbfe;border-radius:0.5rem;background:white;cursor:pointer;">Retry</button>
    </div></td></tr>`;
}
function prov_setBadge(count) {
    const badge = document.getElementById('provisionTabBadge');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-flex' : 'none';
}

// ── Type switcher: Student ↔ Staff ──────────────────────────────────────────
let prov_currentType = 'student'; // 'student' | 'staff'

function prov_switchType(type) {
    prov_currentType = type;

    // Update pill button styles
    const stuBtn  = document.getElementById('prov-type-student');
    const stfBtn  = document.getElementById('prov-type-staff');
    const active  = 'padding:0.35rem 1rem;border-radius:9999px;font-size:0.72rem;font-weight:800;font-family:inherit;cursor:pointer;border:1.5px solid #1535a0;background:linear-gradient(135deg,#0d2470,#1535a0);color:white;transition:all 0.18s;display:flex;align-items:center;gap:0.4rem;';
    const inactive = 'padding:0.35rem 1rem;border-radius:9999px;font-size:0.72rem;font-weight:800;font-family:inherit;cursor:pointer;border:1.5px solid #c7d2fe;background:#f0f4ff;color:#3b4eac;transition:all 0.18s;display:flex;align-items:center;gap:0.4rem;';

    if (type === 'student') {
        stuBtn.style.cssText = active;
        stfBtn.style.cssText = inactive;
        // Show student tables, hide staff tables
        document.getElementById('prov-pendingTableBody').closest('.overflow-x-auto').style.display = '';
        document.getElementById('prov-studentsTableBody').closest('.overflow-x-auto').style.display = '';
        document.getElementById('prov-staffPendingWrap').style.display   = 'none';
        document.getElementById('prov-staffAccountsWrap').style.display  = 'none';
        // Update headings
        document.getElementById('prov-createAccountsTitle').textContent    = 'Approved Students — Awaiting Account Creation';
        document.getElementById('prov-createAccountsSubtitle').innerHTML   = 'Only students with status = <span class="text-green-600 font-bold">approved</span> and no existing account are listed below.';
        // Search placeholder
        const si = document.getElementById('prov-searchInput');
        if (si) si.placeholder = 'Search name or program...';
        const sp = document.getElementById('prov-searchPending');
        if (sp) sp.placeholder = 'Search name or program...';
    } else {
        stuBtn.style.cssText = inactive;
        stfBtn.style.cssText = active;
        // Show staff tables, hide student tables
        document.getElementById('prov-pendingTableBody').closest('.overflow-x-auto').style.display   = 'none';
        document.getElementById('prov-studentsTableBody').closest('.overflow-x-auto').style.display  = 'none';
        document.getElementById('prov-staffPendingWrap').style.display   = '';
        document.getElementById('prov-staffAccountsWrap').style.display  = '';
        // Update headings
        document.getElementById('prov-createAccountsTitle').textContent   = 'Approved Staff — Awaiting Account Creation';
        document.getElementById('prov-createAccountsSubtitle').innerHTML  = 'Only staff with status = <span class="text-green-600 font-bold">approved</span> and no existing account are listed below.';
        // Search placeholder
        const si = document.getElementById('prov-searchInput');
        if (si) si.placeholder = 'Search name or department...';
        const sp = document.getElementById('prov-searchPending');
        if (sp) sp.placeholder = 'Search name or role...';
    }

    prov_loadAll();
}

// ── Staff data stores ───────────────────────────────────────────────────────
let prov_allStaff     = [];
let prov_pendingStaff = [];

// ── Override prov_loadAll to handle both types ──────────────────────────────
const _prov_loadAll_orig = prov_loadAll;
prov_loadAll = async function () {
    if (prov_currentType === 'staff') {
        await Promise.all([prov_loadStaffPending(), prov_loadStaffApproved()]);
    } else {
        await Promise.all([prov_loadPending(), prov_loadApproved()]);
    }
};

// ── Staff: Pending Approval ─────────────────────────────────────────────────
async function prov_loadStaffPending() {
    const tbody = document.getElementById('prov-staffPendingTableBody');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-16"><i class="fas fa-spinner fa-spin text-amber-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>`;
    try {
        const res  = await fetch(`${PROV_API}/get_pending_staff_registrations.php`);
        const data = await res.json();
        if (data.success) {
            prov_pendingStaff = data.staff || [];
            document.getElementById('prov-statPendingApproval').textContent = prov_pendingStaff.length;
            document.getElementById('prov-pendingBadge').textContent         = prov_pendingStaff.length;
            prov_setBadge(prov_pendingStaff.length);
            prov_renderStaffPendingTable(prov_pendingStaff);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-red-400 font-semibold text-sm">${data.message}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-red-400 font-semibold text-sm">Connection Error: ${err.message}</td></tr>`;
    }
}

function prov_renderStaffPendingTable(staff) {
    const tbody = document.getElementById('prov-staffPendingTableBody');
    if (!staff.length) {
        tbody.innerHTML = `<tr><td colspan="6"><div style="text-align:center;padding:3rem 2rem;">
            <i class="fas fa-inbox" style="color:#fde68a;font-size:1.75rem;margin-bottom:0.75rem;display:block;"></i>
            <p style="font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:900;color:#0d2470;text-transform:uppercase;">No Pending Staff Registrations</p>
            <p style="color:#94a3b8;font-size:0.8rem;margin-top:0.25rem;">All staff registrations have been reviewed.</p>
        </div></td></tr>`;
        return;
    }
    tbody.innerHTML = staff.map(s => {
        const fullName = `${s.first_name}${s.middle_name ? ' ' + s.middle_name.charAt(0) + '.' : ''} ${s.last_name}`;
        const regDate  = s.registered_at ? new Date(s.registered_at).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : 'N/A';
        const initials = s.first_name.charAt(0) + s.last_name.charAt(0);
        const roleColors = { faculty:'#92400e', admin:'#581c87', registrar:'#1e3a8a', cashier:'#881337', librarian:'#064e3b' };
        const roleColor = roleColors[s.role] || '#334155';
        return `
        <tr class="prov-tbl-row" id="prov-sprow-${s.staff_id}">
            <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#d97706,#f59e0b);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-size:12px;font-weight:900;color:white;">${initials}</span>
                    </div>
                    <div>
                        <p style="font-weight:700;font-size:0.875rem;color:#334155;">${fullName}</p>
                        <p style="font-size:10px;color:#94a3b8;font-weight:600;">${s.mobile_number || '—'}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-4"><span style="font-size:0.72rem;font-weight:800;color:${roleColor};background:${roleColor}18;border:1px solid ${roleColor}30;padding:2px 10px;border-radius:9999px;text-transform:capitalize;">${s.role || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:600;color:#475569;">${s.department || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;color:#64748b;">${s.personal_email || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;color:#94a3b8;font-weight:600;">${regDate}</span></td>
            <td class="px-4 py-4 text-center">
                <button class="prov-btn-approve" id="prov-sabtn-${s.staff_id}" onclick="prov_approveStaff('${s.staff_id}')">
                    <i class="fas fa-check mr-1"></i>Approve
                </button>
                <button class="prov-btn-reject" id="prov-srbtn-${s.staff_id}" onclick="prov_rejectStaff('${s.staff_id}', '${fullName.replace(/'/g,"\\'")}')">
                    <i class="fas fa-times mr-1"></i>Reject
                </button>
            </td>
        </tr>`;
    }).join('');
}

async function prov_approveStaff(staffId) {
    const abtn = document.getElementById(`prov-sabtn-${staffId}`);
    const rbtn = document.getElementById(`prov-srbtn-${staffId}`);
    abtn.disabled = rbtn.disabled = true;
    abtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Approving...';
    try {
        const res    = await fetch(`${PROV_API}/approve_staff_registration.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ staff_id: staffId, action: 'approve' }) });
        const result = await res.json();
        if (result.success) {
            const row = document.getElementById(`prov-sprow-${staffId}`);
            row.style.background = '#f0fdf4'; row.style.opacity = '0.5';
            setTimeout(() => row.remove(), 600);
            prov_pendingStaff = prov_pendingStaff.filter(s => s.staff_id != staffId);
            document.getElementById('prov-statPendingApproval').textContent = prov_pendingStaff.length;
            document.getElementById('prov-pendingBadge').textContent         = prov_pendingStaff.length;
            prov_setBadge(prov_pendingStaff.length);
            showToast('✓ Staff approved! Now visible in Create Accounts tab.');
            await prov_loadStaffApproved();
            prov_switchTab('provision');
        } else {
            alert('Failed: ' + result.message);
            abtn.disabled = rbtn.disabled = false;
            abtn.innerHTML = '<i class="fas fa-check mr-1"></i>Approve';
        }
    } catch (err) {
        alert('Connection Error: ' + err.message);
        abtn.disabled = rbtn.disabled = false;
        abtn.innerHTML = '<i class="fas fa-check mr-1"></i>Approve';
    }
}

async function prov_rejectStaff(staffId, fullName) {
    if (!confirm(`Reject registration for ${fullName}?\n\nThis will mark their application as rejected.`)) return;
    const abtn = document.getElementById(`prov-sabtn-${staffId}`);
    const rbtn = document.getElementById(`prov-srbtn-${staffId}`);
    abtn.disabled = rbtn.disabled = true;
    rbtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Rejecting...';
    try {
        const res    = await fetch(`${PROV_API}/approve_staff_registration.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ staff_id: staffId, action: 'reject' }) });
        const result = await res.json();
        if (result.success) {
            const row = document.getElementById(`prov-sprow-${staffId}`);
            row.style.background = '#fee2e2'; row.style.opacity = '0.5';
            setTimeout(() => row.remove(), 600);
            prov_pendingStaff = prov_pendingStaff.filter(s => s.staff_id != staffId);
            document.getElementById('prov-statPendingApproval').textContent = prov_pendingStaff.length;
            document.getElementById('prov-pendingBadge').textContent         = prov_pendingStaff.length;
            prov_setBadge(prov_pendingStaff.length);
            showToast('✗ Staff registration rejected.', 'warn');
        } else {
            alert('Failed: ' + result.message);
            abtn.disabled = rbtn.disabled = false;
            rbtn.innerHTML = '<i class="fas fa-times mr-1"></i>Reject';
        }
    } catch (err) {
        alert('Connection Error: ' + err.message);
        abtn.disabled = rbtn.disabled = false;
        rbtn.innerHTML = '<i class="fas fa-times mr-1"></i>Reject';
    }
}

// ── Staff: Create Accounts ──────────────────────────────────────────────────
async function prov_loadStaffApproved() {
    const tbody = document.getElementById('prov-staffAccountsTableBody');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-16"><i class="fas fa-spinner fa-spin text-blue-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>`;
    try {
        const res  = await fetch(`${PROV_API}/check_staff_registration_status.php`);
        const data = await res.json();
        if (data.success) {
            prov_allStaff = data.staff || [];
            prov_updateStats(data.stats || {});
            prov_renderStaffTable(prov_allStaff);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-red-400 font-semibold text-sm">${data.message || 'Failed to load staff.'}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-red-400 font-semibold text-sm">Connection Error: ${err.message}</td></tr>`;
    }
}

function prov_renderStaffTable(staff) {
    const tbody = document.getElementById('prov-staffAccountsTableBody');
    if (!staff.length) {
        tbody.innerHTML = `<tr><td colspan="6"><div style="text-align:center;padding:4rem 2rem;">
            <div style="width:56px;height:56px;background:#eef2ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i class="fas fa-check-circle" style="color:#60a5fa;font-size:1.5rem;"></i>
            </div>
            <p style="font-family:'Barlow Condensed',sans-serif;font-size:1.2rem;font-weight:900;color:#0d2470;text-transform:uppercase;">All Caught Up!</p>
            <p style="color:#94a3b8;font-size:0.875rem;margin-top:0.25rem;">No approved staff are waiting for account creation.</p>
        </div></td></tr>`;
        return;
    }
    const roleColors = { faculty:'#92400e', admin:'#581c87', registrar:'#1e3a8a', cashier:'#881337', librarian:'#064e3b' };
    tbody.innerHTML = staff.map(s => {
        const fullName = `${s.first_name}${s.middle_name ? ' ' + s.middle_name.charAt(0) + '.' : ''} ${s.last_name}`;
        const regDate  = s.registered_at ? new Date(s.registered_at).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : 'N/A';
        const initials = s.first_name.charAt(0) + s.last_name.charAt(0);
        const roleColor = roleColors[s.role] || '#334155';
        return `
        <tr class="prov-tbl-row" id="prov-srow-${s.staff_id}">
            <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0d2470,#1535a0);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-size:12px;font-weight:900;color:white;">${initials}</span>
                    </div>
                    <div>
                        <p style="font-weight:700;font-size:0.875rem;color:#334155;">${fullName}</p>
                        <p style="font-size:10px;color:#94a3b8;font-weight:600;">${s.mobile_number || '—'}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-4"><span style="font-size:0.72rem;font-weight:800;color:${roleColor};background:${roleColor}18;border:1px solid ${roleColor}30;padding:2px 10px;border-radius:9999px;text-transform:capitalize;">${s.role || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:600;color:#475569;">${s.department || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;color:#64748b;">${s.personal_email || '—'}</span></td>
            <td class="px-4 py-4"><span style="font-size:0.75rem;color:#94a3b8;font-weight:600;">${regDate}</span></td>
            <td class="px-4 py-4 text-center">
                <button class="prov-btn-create" id="prov-sbtn-${s.staff_id}" onclick="prov_createStaffAccount('${s.staff_id}')">
                    <i class="fas fa-key mr-1"></i> Create Account
                </button>
            </td>
        </tr>`;
    }).join('');
}

async function prov_createStaffAccount(staffId) {
    const btn  = document.getElementById(`prov-sbtn-${staffId}`);
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Creating...';
    try {
        const res    = await fetch(`${PROV_API}/provision_staff_account.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ staff_id: staffId }) });
        const result = await res.json();
        if (result.success) {
            prov_markStaffRowCreated(staffId);
            prov_allStaff = prov_allStaff.filter(s => s.staff_id != staffId);
            const sp = document.getElementById('prov-statPending');
            sp.textContent = Math.max(0, parseInt(sp.textContent || 0) - 1);
            document.getElementById('prov-approvedBadge').textContent = Math.max(0, parseInt(document.getElementById('prov-approvedBadge').textContent || 0) - 1);
            const sc = document.getElementById('prov-statCreated');
            sc.textContent = parseInt(sc.textContent || 0) + 1;
            document.getElementById('prov-createAllBtn').disabled = prov_allStaff.length === 0;
            prov_showSuccessModal(result.credentials, result.personal_email, 'staff');
        } else {
            alert('❌ Failed\n\n' + result.message);
            btn.disabled = false; btn.innerHTML = orig;
        }
    } catch (err) {
        alert('❌ Connection Error\n\n' + err.message);
        btn.disabled = false; btn.innerHTML = orig;
    }
}

function prov_markStaffRowCreated(staffId) {
    const row = document.getElementById(`prov-srow-${staffId}`);
    if (!row) return;
    const btn = document.getElementById(`prov-sbtn-${staffId}`);
    if (btn) { btn.innerHTML = '<i class="fas fa-check mr-1"></i> Created'; btn.style.background = 'linear-gradient(135deg,#15803d,#16a34a)'; btn.disabled = true; }
    setTimeout(() => { if (row) row.remove(); }, 1500);
}

function prov_filterStaffTable() {
    const q = document.getElementById('prov-searchInput').value.toLowerCase();
    prov_renderStaffTable(q ? prov_allStaff.filter(s =>
        `${s.first_name} ${s.last_name}`.toLowerCase().includes(q) ||
        (s.department || '').toLowerCase().includes(q) ||
        (s.role || '').toLowerCase().includes(q)
    ) : prov_allStaff);
}

function prov_filterStaffPending() {
    const q = document.getElementById('prov-searchPending').value.toLowerCase();
    prov_renderStaffPendingTable(q ? prov_pendingStaff.filter(s =>
        `${s.first_name} ${s.last_name}`.toLowerCase().includes(q) ||
        (s.role || '').toLowerCase().includes(q)
    ) : prov_pendingStaff);
}
// ════════════════════════════════════════════════════════════════════
// END PROVISION TAB
// ════════════════════════════════════════════════════════════════════

// ════════════════════════════════════════════════════════════════════
// USER REGISTRATION PANEL (Student + Staff roles)
// ════════════════════════════════════════════════════════════════════
const REG_API_BASE = '../modules/integration/api';
let reg_currentRole = 'student';
let reg_uploadedFiles = [];

// ─ FILE UPLOAD FUNCTIONS ─
function reg_handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    const files = e.dataTransfer.files;
    reg_handleFiles(files);
}

function reg_handleFiles(files) {
    for (let file of files) {
        reg_queueFile(file);
    }
}

// Queue file locally — actual upload happens after registration succeeds
function reg_queueFile(file) {
    const allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif'];
    const maxSize = 10 * 1024 * 1024;

    const fileExt = file.name.split('.').pop().toLowerCase();
    if (!allowedExt.includes(fileExt)) {
        alert(`❌ File type not allowed: ${file.name}`);
        return;
    }

    if (file.size > maxSize) {
        alert(`❌ File too large: ${file.name} (exceeds 10MB)`);
        return;
    }

    // Store the raw File object for later upload
    const queueId = 'q_' + Date.now() + '_' + Math.random().toString(36).slice(2, 7);
    reg_uploadedFiles.push({ queueId, file, name: file.name, status: 'pending' });

    const fileItem = reg_createFileItem(file.name, 'pending', queueId);
    document.getElementById('reg_uploadedFilesList').appendChild(fileItem);
}

// Upload all queued files after user is registered and real user_id is known
async function reg_uploadQueuedFiles(userId) {
    const results = [];
    for (const entry of reg_uploadedFiles) {
        if (entry.status !== 'pending') continue;

        const item = document.querySelector(`[data-queue-id="${entry.queueId}"]`);
        if (item) {
            item.classList.add('opacity-50');
            item.querySelector('.status-icon').innerHTML = '<i class="fas fa-spinner fa-spin text-blue-500"></i>';
            item.querySelector('.status-text').textContent = 'Uploading...';
        }

        const formData = new FormData();
        formData.append('file', entry.file);
        formData.append('user_id', userId);

        try {
            const response = await fetch(`../modules/user-creation/api/upload_file.php`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                entry.status = 'uploaded';
                entry.id = result.file_id;
                entry.file_name = result.file_name;
                if (item) {
                    item.classList.remove('opacity-50');
                    item.querySelector('.status-icon').innerHTML = '<i class="fas fa-check-circle text-green-500"></i>';
                    item.querySelector('.status-text').textContent = 'Uploaded';
                }
                results.push({ success: true, name: entry.name });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            entry.status = 'failed';
            if (item) {
                item.classList.remove('opacity-50');
                item.querySelector('.status-icon').innerHTML = '<i class="fas fa-times-circle text-red-500"></i>';
                item.querySelector('.status-text').textContent = 'Failed: ' + error.message;
            }
            results.push({ success: false, name: entry.name, error: error.message });
        }
    }
    return results;
}

function reg_createFileItem(fileName, status, queueId) {
    const div = document.createElement('div');
    div.className = 'flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200';
    if (queueId) div.setAttribute('data-queue-id', queueId);

    const statusIcon   = status === 'pending'
        ? '<i class="fas fa-clock text-amber-400"></i>'
        : '<i class="fas fa-spinner fa-spin text-blue-500"></i>';
    const statusLabel  = status === 'pending' ? 'Ready to upload' : 'Uploading...';

    div.innerHTML = `
        <div class="flex items-center gap-3 flex-grow">
            <i class="fas fa-file text-blue-500 text-lg"></i>
            <div class="flex-grow min-w-0">
                <p class="text-sm font-semibold text-slate-700 truncate">${fileName}</p>
                <p class="text-xs text-slate-500">${statusLabel}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="status-icon">${statusIcon}</span>
            <span class="status-text text-xs font-medium text-slate-600">${statusLabel}</span>
            ${status === 'pending' ? `<button onclick="reg_removeQueuedFile('${queueId}', this)" class="ml-1 text-slate-400 hover:text-red-500 transition-colors" title="Remove"><i class="fas fa-times"></i></button>` : ''}
        </div>
    `;
    return div;
}

function reg_removeQueuedFile(queueId, btn) {
    reg_uploadedFiles = reg_uploadedFiles.filter(f => f.queueId !== queueId);
    btn.closest('.flex.items-center.justify-between').remove();
}

const reg_roleMeta = {
    student:   { label: 'Student',    icon: 'fa-user-graduate',      color: '#0d2470' },
    faculty:   { label: 'Faculty',    icon: 'fa-chalkboard-teacher', color: '#92400e' },
    admin:     { label: 'Admin',      icon: 'fa-user-shield',        color: '#581c87' },
    registrar: { label: 'Registrar',  icon: 'fa-file-invoice',       color: '#1e3a8a' },
    cashier:   { label: 'Cashier',    icon: 'fa-cash-register',      color: '#881337' },
    librarian: { label: 'Librarian',  icon: 'fa-book',               color: '#064e3b' },
};

function reg_switchRole(role, btn) {
    reg_currentRole = role;

    // Update tab active state
    document.querySelectorAll('.reg-role-tab').forEach(b => b.classList.remove('reg-role-tab-active'));
    btn.classList.add('reg-role-tab-active');

    const isStudent = role === 'student';
    const meta = reg_roleMeta[role];

    // Update form title
    document.getElementById('reg_formTitle').textContent = meta.label.toUpperCase() + ' REGISTRATION';

    // Toggle sections
    document.getElementById('reg_section_academic').style.display    = isStudent ? '' : 'none';
    document.getElementById('reg_section_guardian').style.display    = isStudent ? '' : 'none';
    document.getElementById('reg_section_employment').style.display  = isStudent ? 'none' : '';

    // Admin-specific access level row
    document.getElementById('reg_accessLevelRow').style.display = role === 'admin' ? '' : 'none';

    // Update submit button label
    document.getElementById('reg_submitBtn').childNodes[0].textContent = 'Submit ' + meta.label + ' Registration';

    // Reset validation colours
    reg_resetForm();
}

async function reg_submitRegistration() {
    const btn  = document.getElementById('reg_submitBtn');
    const orig = btn.innerText;
    const role = reg_currentRole;
    const isStudent = role === 'student';

    // ── Common fields ────────────────────────────────────────────────
    const common = {
        firstName:    document.getElementById('reg_firstName').value.trim(),
        middleName:   document.getElementById('reg_middleName').value.trim(),
        lastName:     document.getElementById('reg_lastName').value.trim(),
        birthDate:    document.getElementById('reg_birthDate').value,
        gender:       document.getElementById('reg_gender').value,
        mobileNumber: document.getElementById('reg_mobileNumber').value.trim(),
        personalEmail:document.getElementById('reg_personalEmail').value.trim(),
        streetAddress:document.getElementById('reg_streetAddress').value.trim(),
        city:         document.getElementById('reg_city').value.trim(),
        province:     document.getElementById('reg_province').value.trim(),
        zipCode:      document.getElementById('reg_zipCode').value.trim(),
    };

    if (!common.firstName || !common.lastName) { alert('⚠️ First and Last Name are required.'); return; }
    if (!common.birthDate)                      { alert('⚠️ Birth Date is required.'); return; }
    if (!common.mobileNumber)                   { alert('⚠️ Mobile Number is required.'); return; }
    if (!common.personalEmail)                  { alert('⚠️ Personal Email is required.'); return; }

    let endpoint, formData;

    if (isStudent) {
        formData = {
            ...common,
            program:              document.getElementById('reg_studentProgram').value,
            yearLevel:            document.getElementById('reg_studentYearLevel').value,
            major:                document.getElementById('reg_major').value.trim(),
            enrollmentStatus:     document.getElementById('reg_enrollmentStatus').value,
            lifeStatus:           document.getElementById('reg_studentLifeStatus').value,
            guardianName:         document.getElementById('reg_guardianName').value.trim(),
            guardianRelationship: document.getElementById('reg_guardianRelationship').value,
            guardianContact:      document.getElementById('reg_guardianContact').value.trim(),
        };
        if (!formData.program)                                   { alert('⚠️ Please select a Program.'); return; }
        if (!formData.guardianName || !formData.guardianContact) { alert('⚠️ Guardian information is required.'); return; }
        endpoint = `${REG_API_BASE}/student_register.php`;
    } else {
        formData = {
            ...common,
            role,
            department:      document.getElementById('reg_department').value,
            employmentType:  document.getElementById('reg_employmentType').value,
            lifeStatus:      document.getElementById('reg_staffLifeStatus').value,
            accessLevel:     role === 'admin' ? document.getElementById('reg_accessLevel').value : 'Standard',
        };
        if (!formData.department) { alert('⚠️ Please select a Department.'); return; }
        endpoint = `${REG_API_BASE}/staff_register.php`;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>SUBMITTING...';

    try {
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        const raw = await res.text();
        let result;
        try { result = JSON.parse(raw); }
        catch { throw new Error(`Unexpected server response (${res.status}). ${raw.slice(0, 180)}`); }

        if (!res.ok)       throw new Error(result?.message || `Server returned ${res.status}`);
        if (result.success) {
            // Upload any queued files now that we have the real user_id
            const realUserId = result.userId || result.user_id || result.registrationId;
            if (reg_uploadedFiles.length > 0 && realUserId) {
                btn.innerHTML = '<i class="fas fa-upload fa-spin mr-2"></i>UPLOADING FILES...';
                await reg_uploadQueuedFiles(realUserId);
            }
            reg_showSuccessModal(formData, result.registrationId);
        } else {
            alert('Registration Failed\n\n' + result.message);
        }
    } catch (err) {
        alert('Connection Error\n\nMake sure XAMPP is running.\n\n' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerText = orig;
    }
}

function reg_showSuccessModal(data, regId) {
    const role = reg_currentRole;
    const isStudent = role === 'student';
    const meta = reg_roleMeta[role];
    const fullName = `${data.firstName}${data.middleName ? ' ' + data.middleName : ''} ${data.lastName}`;

    const items = isStudent ? [
        { icon:'fa-user',           iconColor:'#94a3b8', label:'Full Name',       value: fullName },
        { icon:'fa-id-card',        iconColor:'#22d3ee', label:'Registration ID', value: regId || 'REG-' + Date.now() },
        { icon:'fa-graduation-cap', iconColor:'#60a5fa', label:'Program',         value: data.program },
        { icon:'fa-layer-group',    iconColor:'#a78bfa', label:'Year Level',      value: data.yearLevel },
    ] : [
        { icon:'fa-user',           iconColor:'#94a3b8', label:'Full Name',       value: fullName },
        { icon:'fa-id-card',        iconColor:'#22d3ee', label:'Registration ID', value: regId || 'SREG-' + Date.now() },
        { icon:meta.icon,           iconColor:'#60a5fa', label:'Role',            value: meta.label },
        { icon:'fa-building',       iconColor:'#a78bfa', label:'Department',      value: data.department || '—' },
    ];

    document.getElementById('reg_modalInfo').innerHTML = items.map(c => `
        <div style="display:flex;align-items:center;gap:0.875rem;background:rgba(255,255,255,0.02);border:1px solid rgba(59,130,246,0.1);border-radius:0.75rem;padding:0.75rem 1rem;margin-bottom:0.5rem;">
            <div style="width:32px;height:32px;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.75rem;background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.15);">
                <i class="fas ${c.icon}" style="color:${c.iconColor}"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <span style="font-size:8.5px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#3b5a8a;display:block;margin-bottom:2px;">${c.label}</span>
                <span style="font-size:0.8rem;font-weight:700;color:#cbd5e1;">${c.value}</span>
            </div>
        </div>`).join('');

    document.getElementById('reg_modalEmail').textContent = data.personalEmail;
    document.getElementById('reg_successModal').style.display = 'flex';
}

function reg_closeModal() {
    document.getElementById('reg_successModal').style.display = 'none';
    reg_resetForm();
}

function reg_resetForm() {
    document.querySelectorAll('#panel-studentRegistration input[type="text"], #panel-studentRegistration input[type="tel"], #panel-studentRegistration input[type="email"], #panel-studentRegistration input[type="date"]').forEach(i => i.value = '');
    const prog = document.getElementById('reg_studentProgram');
    if (prog) prog.selectedIndex = 0;
    const dept = document.getElementById('reg_department');
    if (dept) dept.selectedIndex = 0;
    // Clear uploaded files
    reg_uploadedFiles = [];
    const filesList = document.getElementById('reg_uploadedFilesList');
    if (filesList) filesList.innerHTML = '';
}
// ════════════════════════════════════════════════════════════════════
// END USER REGISTRATION PANEL
// ════════════════════════════════════════════════════════════════════

// ════════════════════════════════════════════════════════════════════
// EDIT USER METADATA MODAL
// ════════════════════════════════════════════════════════════════════
let _editingUserId = null;

function openEditModal() {
    if (!currentUser) return;
    _editingUserId = currentUser.user_id;

    document.getElementById('edit_firstName').value   = currentUser.first_name   || '';
    document.getElementById('edit_lastName').value    = currentUser.last_name    || '';
    document.getElementById('edit_middleName').value  = currentUser.middle_name  || '';
    document.getElementById('edit_mobile').value      = currentUser.mobile_number|| '';
    document.getElementById('edit_personalEmail').value = currentUser.personal_email || '';
    document.getElementById('edit_gender').value      = currentUser.gender       || 'Male';
    document.getElementById('edit_birthDate').value   = currentUser.birth_date   ? currentUser.birth_date.substring(0,10) : '';
    document.getElementById('edit_street').value      = currentUser.street_address|| '';
    document.getElementById('edit_city').value        = currentUser.city         || '';
    document.getElementById('edit_province').value    = currentUser.province     || '';
    document.getElementById('edit_zip').value         = currentUser.zip_code     || '';

    // Populate life_status options based on role
    const effRole = getEffectiveRole(currentUser);
    const isStudent = effRole === 'student';
    const statusOptions = isStudent
        ? ['Active', 'Alumni', 'Dropped']
        : ['Active', 'Terminated'];
    const sel = document.getElementById('edit_lifeStatus');
    sel.innerHTML = statusOptions.map(o => `<option value="${o}">${o}</option>`).join('');
    sel.value = currentUser.life_status || 'Active';

    const msg = document.getElementById('editModalMsg');
    msg.style.display = 'none';
    document.getElementById('editUserModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editUserModal').style.display = 'none';
}

async function submitEditUser() {
    const btn = document.getElementById('editSaveBtn');
    const msg = document.getElementById('editModalMsg');
    const orig = btn.textContent;

    const payload = {
        user_id:        _editingUserId,
        first_name:     document.getElementById('edit_firstName').value.trim(),
        last_name:      document.getElementById('edit_lastName').value.trim(),
        middle_name:    document.getElementById('edit_middleName').value.trim(),
        mobile_number:  document.getElementById('edit_mobile').value.trim(),
        personal_email: document.getElementById('edit_personalEmail').value.trim(),
        gender:         document.getElementById('edit_gender').value,
        birth_date:     document.getElementById('edit_birthDate').value,
        street_address: document.getElementById('edit_street').value.trim(),
        city:           document.getElementById('edit_city').value.trim(),
        province:       document.getElementById('edit_province').value.trim(),
        zip_code:       document.getElementById('edit_zip').value.trim(),
        life_status:    document.getElementById('edit_lifeStatus').value,
    };

    if (!payload.first_name || !payload.last_name) {
        showEditMsg('First and Last Name are required.', false);
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Saving...';

    try {
        const res    = await fetch('../modules/user-creation/api/update_user.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const result = await res.json();
        if (result.success) {
            showEditMsg('User information updated successfully.', true);
            // Update currentUser cache so view modal reflects changes
            Object.assign(currentUser, payload);
            document.getElementById('modalFullName').innerText = `${payload.first_name} ${payload.last_name}`;
            setTimeout(() => { closeEditModal(); loadUsers(); }, 1200);
        } else {
            showEditMsg(result.message || 'Failed to update user.', false);
        }
    } catch (err) {
        showEditMsg('Connection error: ' + err.message, false);
    } finally {
        btn.disabled = false;
        btn.textContent = orig;
    }
}

function showEditMsg(text, success) {
    const msg = document.getElementById('editModalMsg');
    msg.textContent = text;
    msg.style.display = 'block';
    msg.style.background  = success ? '#dcfce7' : '#fef2f2';
    msg.style.color       = success ? '#166534' : '#7f1d1d';
    msg.style.border      = success ? '1px solid #bbf7d0' : '1px solid #fecaca';
}
// ════════════════════════════════════════════════════════════════════
// END EDIT USER METADATA MODAL
// ════════════════════════════════════════════════════════════════════

// ════════════════════════════════════════════════════════════════════
// RESET PASSWORD MODAL (ADMIN)
// ════════════════════════════════════════════════════════════════════
let _resetPwUserId = null;

function openResetPasswordModal() {
    if (!currentUser) return;
    _resetPwUserId = currentUser.user_id;
    document.getElementById('resetPwUserName').textContent = `${currentUser.first_name} ${currentUser.last_name}`;
    document.getElementById('resetPwInput').value   = '';
    document.getElementById('resetPwConfirm').value = '';
    document.getElementById('resetPwStrengthFill').style.width = '0%';
    document.getElementById('resetPwStrengthLabel').textContent = 'Enter a password';
    const msg = document.getElementById('resetPwMsg');
    msg.style.display = 'none';
    document.getElementById('resetPwModal').style.display = 'flex';
}

function closeResetPasswordModal() {
    document.getElementById('resetPwModal').style.display = 'none';
}

function toggleResetPwEye() {
    const inp  = document.getElementById('resetPwInput');
    const icon = document.getElementById('resetPwEyeIcon');
    inp.type   = inp.type === 'password' ? 'text' : 'password';
    icon.className = inp.type === 'text' ? 'bx bx-hide' : 'bx bx-show';
}

function resetPwStrength(val) {
    let score = 0;
    if (val.length >= 10)   score++;
    if (/[A-Z]/.test(val))  score++;
    if (/[a-z]/.test(val))  score++;
    if (/[0-9]/.test(val))  score++;
    if (/[\W_]/.test(val))  score++;
    const fill  = document.getElementById('resetPwStrengthFill');
    const label = document.getElementById('resetPwStrengthLabel');
    const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Strong'];
    const colors = ['#ef4444', '#ef4444', '#f97316', '#eab308', '#22c55e', '#22c55e'];
    fill.style.width      = (score * 20) + '%';
    fill.style.background = colors[score] || '#ef4444';
    label.textContent     = labels[score] || 'Very Weak';
    label.style.color     = colors[score] || '#ef4444';
}

async function submitResetPassword() {
    const pw      = document.getElementById('resetPwInput').value;
    const confirm = document.getElementById('resetPwConfirm').value;
    const btn     = document.getElementById('resetPwSaveBtn');
    const orig    = btn.textContent;

    if (pw !== confirm)   { showResetPwMsg('Passwords do not match.', false); return; }
    if (pw.length < 10)   { showResetPwMsg('Password must be at least 10 characters.', false); return; }
    if (!/[A-Z]/.test(pw)){ showResetPwMsg('Must include an uppercase letter.', false); return; }
    if (!/[a-z]/.test(pw)){ showResetPwMsg('Must include a lowercase letter.', false); return; }
    if (!/[0-9]/.test(pw)){ showResetPwMsg('Must include a number.', false); return; }
    if (!/[\W_]/.test(pw)){ showResetPwMsg('Must include a symbol (e.g. !@#$%).', false); return; }

    btn.disabled = true;
    btn.textContent = 'Resetting...';

    try {
        const res = await fetch('../modules/user-creation/api/admin_reset_password.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ user_id: _resetPwUserId, password: pw }) });
        const result = await res.json();
        if (result.success) {
            showResetPwMsg('Password reset successfully.', true);
            setTimeout(() => closeResetPasswordModal(), 1400);
        } else {
            showResetPwMsg(result.message || 'Failed to reset password.', false);
        }
    } catch (err) {
        showResetPwMsg('Connection error: ' + err.message, false);
    } finally {
        btn.disabled = false;
        btn.textContent = orig;
    }
}

function showResetPwMsg(text, success) {
    const msg = document.getElementById('resetPwMsg');
    msg.textContent = text;
    msg.style.display = 'block';
    msg.style.background  = success ? '#dcfce7' : '#fef2f2';
    msg.style.color       = success ? '#166534' : '#7f1d1d';
    msg.style.border      = success ? '1px solid #bbf7d0' : '1px solid #fecaca';
}
// ════════════════════════════════════════════════════════════════════
// END RESET PASSWORD MODAL
// ════════════════════════════════════════════════════════════════════
</script>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCP SIEMS | Account Provisioning</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Barlow+Condensed:wght@700;800;900&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(135deg,#e8edf5 0%,#dde4f0 100%); min-height: 100vh; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .badge { padding: 0.2rem 0.7rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; }
        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-pending  { background: #fef9c3; color: #a16207; }
        .badge-created  { background: #dbeafe; color: #1e40af; }
        .badge-rejected { background: #fee2e2; color: #dc2626; }
        .tbl-row { transition: background 0.15s; border-bottom: 1px solid #f1f5f9; }
        .tbl-row:hover { background: #f0f4ff; }
        .tbl-row:last-child { border-bottom: none; }
        .btn-create { padding: 0.45rem 1rem; background: linear-gradient(135deg,#0d2470,#1535a0); color: white; border: none; border-radius: 0.45rem; font-size: 0.7rem; font-weight: 800; font-family: inherit; cursor: pointer; transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; box-shadow: 0 4px 12px rgba(13,36,112,0.25); }
        .btn-create:hover { background: linear-gradient(135deg,#1535a0,#1d4ed8); transform: translateY(-1px); }
        .btn-create:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
        .btn-approve { padding: 0.4rem 0.9rem; background: linear-gradient(135deg,#15803d,#16a34a); color: white; border: none; border-radius: 0.45rem; font-size: 0.7rem; font-weight: 800; font-family: inherit; cursor: pointer; transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; box-shadow: 0 4px 12px rgba(21,128,61,0.25); margin-right: 0.35rem; }
        .btn-approve:hover { background: linear-gradient(135deg,#16a34a,#22c55e); transform: translateY(-1px); }
        .btn-approve:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
        .btn-reject { padding: 0.4rem 0.9rem; background: white; color: #dc2626; border: 1.5px solid #fca5a5; border-radius: 0.45rem; font-size: 0.7rem; font-weight: 800; font-family: inherit; cursor: pointer; transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; }
        .btn-reject:hover { background: #fee2e2; }
        .btn-create-all { padding: 0.75rem 1.75rem; background: linear-gradient(135deg,#0d2470,#1535a0); color: white; border: none; border-radius: 0.6rem; font-size: 0.78rem; font-weight: 900; font-family: 'Barlow Condensed', sans-serif; cursor: pointer; transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.1em; box-shadow: 0 6px 20px rgba(13,36,112,0.3); display: flex; align-items: center; gap: 0.5rem; }
        .btn-create-all:hover { background: linear-gradient(135deg,#1535a0,#1d4ed8); transform: translateY(-1px); }
        .btn-create-all:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        /* Tabs */
        .tab-btn { padding: 0.6rem 1.4rem; font-size: 0.75rem; font-weight: 800; font-family: 'Barlow Condensed', sans-serif; letter-spacing: 0.08em; text-transform: uppercase; border: none; background: transparent; cursor: pointer; color: #94a3b8; border-bottom: 3px solid transparent; transition: all 0.2s; display: flex; align-items: center; gap: 0.4rem; }
        .tab-btn.active { color: #0d2470; border-bottom-color: #1535a0; }
        .tab-btn:hover:not(.active) { color: #475569; }
        .tab-section { display: none; }
        .tab-section.active { display: block; }
        .tab-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; border-radius: 9999px; font-size: 9px; font-weight: 900; padding: 0 5px; }
        .tab-badge-yellow { background: #fef9c3; color: #a16207; }
        .tab-badge-blue   { background: #dbeafe; color: #1d4ed8; }

        /* Modal */
        #successModal { display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(4,10,28,0.88); backdrop-filter: blur(12px); align-items: center; justify-content: center; padding: 1rem; }
        #successModal.show { display: flex; }
        .modal-card { background: linear-gradient(160deg,#0a1628 0%,#0d1f3c 50%,#0a1628 100%); border: 1px solid rgba(59,130,246,0.2); border-radius: 1.5rem; width: 100%; max-width: 440px; box-shadow: 0 40px 80px rgba(0,0,0,0.8); overflow: hidden; animation: modalPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
        @keyframes modalPop { from { opacity:0; transform:scale(0.88) translateY(30px); } to { opacity:1; transform:scale(1) translateY(0); } }
        .modal-header { background: linear-gradient(135deg,#1a3a6e 0%,#1e3a8a 50%,#1a3a6e 100%); padding: 1.5rem 1.75rem; display:flex; align-items:center; gap:1rem; border-bottom: 1px solid rgba(59,130,246,0.15); position:relative; overflow:hidden; }
        .modal-header::before { content:''; position:absolute; top:-60px; right:-60px; width:180px; height:180px; background:rgba(59,130,246,0.08); border-radius:50%; }
        .check-ring { width:48px; height:48px; border-radius:0.9rem; background: linear-gradient(135deg,#1d4ed8,#2563eb); border: 1px solid rgba(96,165,250,0.3); box-shadow: 0 4px 16px rgba(37,99,235,0.4); display:flex; align-items:center; justify-content:center; flex-shrink:0; animation: checkPop 0.4s 0.3s cubic-bezier(0.34,1.56,0.64,1) both; }
        @keyframes checkPop { from{transform:scale(0) rotate(-20deg);opacity:0;} to{transform:scale(1) rotate(0);opacity:1;} }
        .modal-body { padding: 1.5rem 1.75rem; }
        .cred-item { display:flex; align-items:center; gap:0.875rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(59,130,246,0.1); border-radius:0.75rem; padding:0.75rem 1rem; margin-bottom:0.5rem; transition: background 0.15s; }
        .cred-item:hover { background:rgba(59,130,246,0.06); }
        .cred-icon-box { width:32px; height:32px; border-radius:0.5rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.75rem; background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.15); }
        .cred-text { flex:1; min-width:0; }
        .cred-label { font-size:8.5px; font-weight:800; letter-spacing:0.12em; text-transform:uppercase; color:#3b5a8a; display:block; margin-bottom:2px; }
        .cred-value { font-size:0.8rem; font-weight:700; color:#cbd5e1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .copy-btn { background: rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.2); border-radius:0.4rem; color:#3b82f6; padding:4px 10px; font-size:9.5px; font-weight:700; cursor:pointer; transition:all 0.15s; flex-shrink:0; font-family:inherit; }
        .copy-btn:hover { background:rgba(59,130,246,0.18); color:#60a5fa; }
        .copy-btn.copied { color:#22c55e; border-color:rgba(34,197,94,0.3); background:rgba(34,197,94,0.08); }
        .modal-section-label { font-size: 9px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #3b82f6; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
        .modal-section-label::before, .modal-section-label::after { content:''; flex:1; height:1px; background:rgba(59,130,246,0.15); }
        .email-notice { background: rgba(59,130,246,0.06); border: 1px solid rgba(59,130,246,0.15); border-radius:0.75rem; padding:0.75rem 1rem; display:flex; align-items:center; gap:0.75rem; margin:1rem 0; }
        .modal-actions { display:flex; gap:0.625rem; }
        .btn-close-modal { flex:1; padding:0.8rem; background: rgba(255,255,255,0.03); border:1px solid rgba(59,130,246,0.2); border-radius:0.75rem; color:#64748b; font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; cursor:pointer; transition:all 0.2s; font-family:inherit; }
        .btn-close-modal:hover { background:rgba(59,130,246,0.08); color:#94a3b8; }
        .btn-done { flex:1.5; padding:0.8rem; background: linear-gradient(135deg,#1d4ed8,#2563eb); border: 1px solid rgba(96,165,250,0.3); border-radius:0.75rem; color:white; font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; cursor:pointer; transition:all 0.2s; box-shadow: 0 6px 20px rgba(37,99,235,0.4); font-family:inherit; }
        .btn-done:hover { transform:translateY(-1px); }
        .search-input { background: #fff; border: 1.5px solid #e0e8f8; border-radius: 0.6rem; padding: 0.6rem 1rem 0.6rem 2.5rem; font-size: 0.82rem; font-family: inherit; transition: all 0.2s; outline: none; }
        .search-input:focus { border-color: #1535a0; box-shadow: 0 0 0 3px rgba(21,53,160,0.1); }
        #toast { position: fixed; bottom: 2rem; right: 2rem; padding: 0.75rem 1.5rem; background: #0d2470; color: white; border-radius: 0.75rem; font-size: 0.82rem; font-weight: 700; z-index: 99999; opacity: 0; transition: opacity 0.3s; pointer-events: none; box-shadow: 0 8px 24px rgba(13,36,112,0.3); }
        #toast.show { opacity: 1; }
    </style>
</head>
<body class="p-6">
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div style="background:white;border-radius:1.25rem;padding:1.5rem 2rem;border:1px solid rgba(21,53,160,0.12);box-shadow:0 8px 32px rgba(13,36,112,0.1);">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <img src="../siems.png" alt="BCP" class="h-12 w-12 object-contain">
                <div>
                    <h1 style="font-family:'Barlow Condensed',sans-serif;font-size:1.75rem;font-weight:900;color:#0d2470;letter-spacing:-0.01em;line-height:1;">ACCOUNT PROVISIONING</h1>
                    <p style="font-size:0.72rem;color:#1535a0;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;">SIEMS — Bestlink College of the Philippines</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="users_table.html" style="padding:0.7rem 1.25rem;border:1.5px solid rgba(21,53,160,0.2);color:#0d2470;border-radius:0.6rem;font-size:0.72rem;font-weight:800;font-family:'Barlow Condensed',sans-serif;letter-spacing:0.1em;text-transform:uppercase;display:flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s;background:#f5f8ff;" onmouseover="this.style.background='#eef2ff'" onmouseout="this.style.background='#f5f8ff'">
                    <i class="fas fa-users"></i><span>View Users</span>
                </a>
                <button id="createAllBtn" onclick="createAllAccounts()" class="btn-create-all" disabled>
                    <i class="fas fa-bolt"></i><span>Create All Accounts</span>
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-4 gap-4 mt-6 pt-5" style="border-top:1px solid rgba(21,53,160,0.1);">
            <div style="background:#fefce8;border-radius:0.75rem;padding:1rem 1.25rem;border:1px solid rgba(234,179,8,0.2);">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pending Approval</p>
                <p id="statPendingApproval" style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:900;color:#a16207;line-height:1;">—</p>
            </div>
            <div style="background:#f5f8ff;border-radius:0.75rem;padding:1rem 1.25rem;border:1px solid rgba(21,53,160,0.08);">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Approved — No Account</p>
                <p id="statPending" style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:900;color:#0d2470;line-height:1;">—</p>
            </div>
            <div style="background:#f0fdf4;border-radius:0.75rem;padding:1rem 1.25rem;border:1px solid rgba(21,163,74,0.15);">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Accounts Created Today</p>
                <p id="statCreated" style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:900;color:#15803d;line-height:1;">—</p>
            </div>
            <div style="background:#eff6ff;border-radius:0.75rem;padding:1rem 1.25rem;border:1px solid rgba(59,130,246,0.15);">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Registered</p>
                <p id="statTotal" style="font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:900;color:#1d4ed8;line-height:1;">—</p>
            </div>
        </div>
    </div>

    <!-- Tab Card -->
    <div style="background:white;border-radius:1.25rem;border:1px solid rgba(21,53,160,0.12);box-shadow:0 8px 32px rgba(13,36,112,0.1);overflow:hidden;">

        <!-- Tab Bar -->
        <div class="flex items-center gap-1 px-5 pt-3" style="border-bottom:2px solid #e8edf5;">
            <button class="tab-btn active" id="tab-approve" onclick="switchTab('approve')">
                <i class="fas fa-clock"></i> Pending Approval
                <span class="tab-badge tab-badge-yellow" id="pendingBadge">0</span>
            </button>
            <button class="tab-btn" id="tab-provision" onclick="switchTab('provision')">
                <i class="fas fa-key"></i> Create Accounts
                <span class="tab-badge tab-badge-blue" id="approvedBadge">0</span>
            </button>
        </div>

        <!-- ── TAB 1: PENDING APPROVAL ── -->
        <div id="section-approve" class="tab-section active">
            <div class="flex items-center justify-between gap-4 p-5 flex-wrap">
                <div>
                    <h2 style="font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.05em;">Pending Registration Review</h2>
                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5">Review new student registrations. <span class="text-green-600 font-bold">Approve</span> to move them to account creation, or <span class="text-red-500 font-bold">Reject</span> to decline.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                        <input type="text" id="searchPending" class="search-input" placeholder="Search name, LRN, program..." style="width:240px;" oninput="filterPending()">
                    </div>
                    <button onclick="loadAll()" style="padding:0.6rem 1rem;border:1.5px solid rgba(21,53,160,0.2);color:#0d2470;border-radius:0.5rem;font-size:0.72rem;font-weight:800;font-family:'Barlow Condensed',sans-serif;letter-spacing:0.08em;text-transform:uppercase;background:#f5f8ff;cursor:pointer;display:flex;align-items:center;gap:0.4rem;" onmouseover="this.style.background='#eef2ff'" onmouseout="this.style.background='#f5f8ff'">
                        <i class="fas fa-sync-alt text-xs"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background:#fefce8;border-bottom:1px solid #fde68a;">
                            <th class="text-left px-5 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Student</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">LRN</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Program</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Year</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Personal Email</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest">Registered</th>
                            <th class="px-4 py-3 text-[10px] font-black text-amber-700 uppercase tracking-widest text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pendingTableBody">
                        <tr><td colspan="7" class="text-center py-16"><i class="fas fa-spinner fa-spin text-amber-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── TAB 2: CREATE ACCOUNTS ── -->
        <div id="section-provision" class="tab-section">
            <div class="flex items-center justify-between gap-4 p-5 flex-wrap">
                <div>
                    <h2 style="font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:900;color:#0d2470;text-transform:uppercase;letter-spacing:0.05em;">Approved Students — Awaiting Account Creation</h2>
                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5">Only students with status = <span class="text-green-600 font-bold">approved</span> and no existing system account are listed below.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search name, LRN, program..." style="width:240px;" oninput="filterTable()">
                    </div>
                    <button onclick="loadAll()" style="padding:0.6rem 1rem;border:1.5px solid rgba(21,53,160,0.2);color:#0d2470;border-radius:0.5rem;font-size:0.72rem;font-weight:800;font-family:'Barlow Condensed',sans-serif;letter-spacing:0.08em;text-transform:uppercase;background:#f5f8ff;cursor:pointer;display:flex;align-items:center;gap:0.4rem;" onmouseover="this.style.background='#eef2ff'" onmouseout="this.style.background='#f5f8ff'">
                        <i class="fas fa-sync-alt text-xs"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8faff;border-bottom:1px solid #e0e8f8;">
                            <th class="text-left px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Student</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">LRN</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Program</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Year</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Personal Email</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Registered</th>
                            <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <tr><td colspan="7" class="text-center py-16"><i class="fas fa-spinner fa-spin text-blue-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /tab card -->
</div>

<!-- SUCCESS MODAL -->
<div id="successModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="check-ring"><i class="fas fa-check text-white text-lg"></i></div>
            <div>
                <p class="text-white text-xl font-black tracking-tight leading-none mb-0.5">Account Provisioned!</p>
                <p class="text-blue-200 text-[10px] font-semibold uppercase tracking-widest">Student account created successfully</p>
            </div>
            <div class="ml-auto">
                <span class="bg-white/10 text-white text-[9px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full border border-white/20">Student</span>
            </div>
        </div>
        <div class="modal-body">
            <div class="modal-section-label">Account Credentials</div>
            <div id="modalCreds"></div>
            <div class="email-notice">
                <i class="fas fa-paper-plane text-blue-400 text-sm flex-shrink-0"></i>
                <p class="text-blue-300 text-[10px] font-semibold leading-relaxed">
                    Credentials for <span id="modalSentEmail" class="text-blue-200 font-black"></span>
                </p>
            </div>
            <div class="modal-actions">
                <button class="btn-close-modal" onclick="closeModal()"><i class="fas fa-times mr-1.5"></i>Close</button>
                <button class="btn-done" onclick="closeModal()"><i class="fas fa-check mr-1.5"></i>Done</button>
            </div>
        </div>
    </div>
</div>

<div id="toast"></div>

<script>
    const API_BASE_URL = '../modules/integration/api';
    let allStudents = [];   // approved, no account
    let pendingStudents = []; // pending approval

    // ── TAB SWITCHING ──────────────────────────────────────────────────
    function switchTab(name) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
        document.getElementById('tab-' + name).classList.add('active');
        document.getElementById('section-' + name).classList.add('active');
        // Only show Create All on provision tab
        document.getElementById('createAllBtn').style.display = name === 'provision' ? 'flex' : 'none';
    }

    // ── LOAD ALL DATA ──────────────────────────────────────────────────
    async function loadAll() {
        await Promise.all([loadPending(), loadApproved()]);
    }

    // ── PENDING APPROVAL TAB ───────────────────────────────────────────
    async function loadPending() {
        const tbody = document.getElementById('pendingTableBody');
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-16"><i class="fas fa-spinner fa-spin text-amber-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>`;
        try {
            const res  = await fetch(`${API_BASE_URL}/get_pending_students.php`);
            const data = await res.json();
            if (data.success) {
                pendingStudents = data.students || [];
                document.getElementById('statPendingApproval').textContent = pendingStudents.length;
                document.getElementById('pendingBadge').textContent = pendingStudents.length;
                renderPendingTable(pendingStudents);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-10 text-red-400 font-semibold text-sm">${data.message}</td></tr>`;
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-10 text-red-400 font-semibold text-sm">Connection Error: ${err.message}</td></tr>`;
        }
    }

    function renderPendingTable(students) {
        const tbody = document.getElementById('pendingTableBody');
        if (!students.length) {
            tbody.innerHTML = `<tr><td colspan="7"><div style="text-align:center;padding:3rem 2rem;">
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
            <tr class="tbl-row" id="prow-${s.student_id}">
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
                <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:700;color:#475569;font-family:monospace;">${s.lrn || '—'}</span></td>
                <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:600;color:#475569;">${s.program || '—'}</span></td>
                <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:700;color:#64748b;">${s.year_level || '—'}</span></td>
                <td class="px-4 py-4"><span style="font-size:0.75rem;color:#64748b;">${s.personal_email || '—'}</span></td>
                <td class="px-4 py-4"><span style="font-size:0.75rem;color:#94a3b8;font-weight:600;">${regDate}</span></td>
                <td class="px-4 py-4 text-center">
                    <button class="btn-approve" id="abtn-${s.student_id}" onclick="approveStudent('${s.student_id}')">
                        <i class="fas fa-check mr-1"></i>Approve
                    </button>
                    <button class="btn-reject" id="rbtn-${s.student_id}" onclick="rejectStudent('${s.student_id}', '${fullName.replace(/'/g,"\\'")}')">
                        <i class="fas fa-times mr-1"></i>Reject
                    </button>
                </td>
            </tr>`;
        }).join('');
    }

    function filterPending() {
        const q = document.getElementById('searchPending').value.toLowerCase();
        renderPendingTable(q ? pendingStudents.filter(s =>
            `${s.first_name} ${s.last_name}`.toLowerCase().includes(q) ||
            (s.lrn || '').includes(q) ||
            (s.program || '').toLowerCase().includes(q)
        ) : pendingStudents);
    }

    async function approveStudent(studentId) {
        const abtn = document.getElementById(`abtn-${studentId}`);
        const rbtn = document.getElementById(`rbtn-${studentId}`);
        abtn.disabled = rbtn.disabled = true;
        abtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Approving...';
        try {
            const res    = await fetch(`${API_BASE_URL}/approve_students.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ student_id: studentId, action: 'approve' }) });
            const result = await res.json();
            if (result.success) {
                // Remove from pending list
                const row = document.getElementById(`prow-${studentId}`);
                row.style.background = '#f0fdf4';
                row.style.opacity = '0.5';
                setTimeout(() => row.remove(), 600);
                pendingStudents = pendingStudents.filter(s => s.student_id != studentId);
                document.getElementById('statPendingApproval').textContent = pendingStudents.length;
                document.getElementById('pendingBadge').textContent = pendingStudents.length;
                showToast('✓ Student approved! Now visible in Create Accounts tab.');
                // Refresh approved list in background
                loadApproved();
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

    async function rejectStudent(studentId, fullName) {
        if (!confirm(`Reject registration for ${fullName}?\n\nThis will mark their application as rejected.`)) return;
        const abtn = document.getElementById(`abtn-${studentId}`);
        const rbtn = document.getElementById(`rbtn-${studentId}`);
        abtn.disabled = rbtn.disabled = true;
        rbtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Rejecting...';
        try {
            const res    = await fetch(`${API_BASE_URL}/approve_students.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ student_id: studentId, action: 'reject' }) });
            const result = await res.json();
            if (result.success) {
                const row = document.getElementById(`prow-${studentId}`);
                row.style.background = '#fee2e2';
                row.style.opacity = '0.5';
                setTimeout(() => row.remove(), 600);
                pendingStudents = pendingStudents.filter(s => s.student_id != studentId);
                document.getElementById('statPendingApproval').textContent = pendingStudents.length;
                document.getElementById('pendingBadge').textContent = pendingStudents.length;
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

    // ── CREATE ACCOUNTS TAB ────────────────────────────────────────────
    async function loadApproved() {
        const tbody = document.getElementById('studentsTableBody');
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-16"><i class="fas fa-spinner fa-spin text-blue-400 text-2xl"></i><p class="text-slate-400 text-sm mt-3 font-semibold">Loading...</p></td></tr>`;
        try {
            const res  = await fetch(`${API_BASE_URL}/get_registered_students.php`);
            const data = await res.json();
            if (data.success) {
                allStudents = data.students || [];
                updateStats(data.stats || {});
                renderTable(allStudents);
            } else {
                showError(data.message || 'Failed to load students.');
            }
        } catch (err) {
            showError('Connection Error — Make sure XAMPP is running.\n' + err.message);
        }
    }

    function updateStats(s) {
        document.getElementById('statPending').textContent  = s.pending ?? '0';
        document.getElementById('statCreated').textContent  = s.today   ?? '0';
        document.getElementById('statTotal').textContent    = s.total   ?? '0';
        document.getElementById('approvedBadge').textContent = s.pending ?? '0';
        document.getElementById('createAllBtn').disabled    = (s.pending ?? 0) === 0;
    }

    function renderTable(students) {
        const tbody = document.getElementById('studentsTableBody');
        if (!students.length) {
            tbody.innerHTML = `<tr><td colspan="7"><div style="text-align:center;padding:4rem 2rem;">
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
            <tr class="tbl-row" id="row-${s.student_id}">
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
                <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:700;color:#475569;font-family:monospace;">${s.lrn || '—'}</span></td>
                <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:600;color:#475569;">${s.program || '—'}</span></td>
                <td class="px-4 py-4"><span style="font-size:0.75rem;font-weight:700;color:#64748b;">${s.year_level || '—'}</span></td>
                <td class="px-4 py-4"><span style="font-size:0.75rem;color:#64748b;">${s.personal_email || '—'}</span></td>
                <td class="px-4 py-4"><span style="font-size:0.75rem;color:#94a3b8;font-weight:600;">${regDate}</span></td>
                <td class="px-4 py-4 text-center">
                    <button class="btn-create" id="btn-${s.student_id}" onclick="createAccount('${s.student_id}')">
                        <i class="fas fa-key mr-1"></i> Create Account
                    </button>
                </td>
            </tr>`;
        }).join('');
    }

    function filterTable() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        renderTable(q ? allStudents.filter(s =>
            `${s.first_name} ${s.last_name}`.toLowerCase().includes(q) ||
            (s.lrn || '').includes(q) ||
            (s.program || '').toLowerCase().includes(q)
        ) : allStudents);
    }

    async function createAccount(studentId) {
        const btn  = document.getElementById(`btn-${studentId}`);
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Creating...';
        try {
            const res    = await fetch(`${API_BASE_URL}/provision_account.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ student_id: studentId }) });
            const result = await res.json();
            if (result.success) {
                markRowCreated(studentId);
                allStudents = allStudents.filter(s => s.student_id != studentId);
                const sp = document.getElementById('statPending');
                sp.textContent = Math.max(0, parseInt(sp.textContent || 0) - 1);
                document.getElementById('approvedBadge').textContent = Math.max(0, parseInt(document.getElementById('approvedBadge').textContent || 0) - 1);
                const sc = document.getElementById('statCreated');
                sc.textContent = parseInt(sc.textContent || 0) + 1;
                document.getElementById('createAllBtn').disabled = allStudents.length === 0;
                showSuccessModal(result.credentials, result.personal_email);
            } else {
                alert('❌ Failed\n\n' + result.message);
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        } catch (err) {
            alert('❌ Connection Error\n\n' + err.message);
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    }

    function markRowCreated(studentId) {
        const row = document.getElementById(`row-${studentId}`);
        if (!row) return;
        const btn = document.getElementById(`btn-${studentId}`);
        if (btn) { btn.innerHTML = '<i class="fas fa-check mr-1"></i> Created'; btn.style.background = 'linear-gradient(135deg,#15803d,#16a34a)'; btn.disabled = true; }
        setTimeout(() => { if (row) row.remove(); }, 1500);
    }

    async function createAllAccounts() {
        if (!allStudents.length) { showToast('No approved students to provision.'); return; }
        if (!confirm(`Create accounts for all ${allStudents.length} approved student(s)?`)) return;
        const btn = document.getElementById('createAllBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Creating All...';
        let ok = 0, fail = 0;
        for (const s of [...allStudents]) {
            try {
                const res    = await fetch(`${API_BASE_URL}/provision_account.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ student_id: s.student_id }) });
                const result = await res.json();
                if (result.success) { markRowCreated(s.student_id); allStudents = allStudents.filter(x => x.student_id != s.student_id); ok++; }
                else fail++;
            } catch { fail++; }
        }
        document.getElementById('statPending').textContent  = allStudents.length;
        document.getElementById('approvedBadge').textContent = allStudents.length;
        document.getElementById('statCreated').textContent  = parseInt(document.getElementById('statCreated').textContent || 0) + ok;
        btn.innerHTML = '<i class="fas fa-bolt"></i><span>Create All Accounts</span>';
        btn.disabled  = allStudents.length === 0;
        showToast(`✓ ${ok} account(s) created${fail ? ` · ${fail} failed` : ''}.`);
    }

    // ── MODALS & HELPERS ───────────────────────────────────────────────
    function showSuccessModal(data, personalEmail) {
        document.getElementById('modalSentEmail').textContent = personalEmail || '—';
        const creds = [
            { icon:'fa-user',     iconColor:'#94a3b8', label:'Full Name',          value: data.fullName },
            { icon:'fa-id-badge', iconColor:'#22d3ee', label:'Student ID',          value: data.userId },
            { icon:'fa-envelope', iconColor:'#60a5fa', label:'Institutional Email', value: data.institutionalEmail },
            { icon:'fa-key',      iconColor:'#fbbf24', label:'Temporary Password',  value: data.temporaryPassword },
        ];
        document.getElementById('modalCreds').innerHTML = creds.map(c => `
            <div class="cred-item">
                <div class="cred-icon-box"><i class="fas ${c.icon}" style="color:${c.iconColor}"></i></div>
                <div class="cred-text">
                    <span class="cred-label">${c.label}</span>
                    <span class="cred-value" title="${c.value}">${c.value}</span>
                </div>
                <button class="copy-btn" onclick="copyVal(this,'${c.value.replace(/'/g,"\\'")}')">
                    <i class="fas fa-copy mr-1"></i>Copy
                </button>
            </div>`).join('');
        document.getElementById('successModal').classList.add('show');
    }

    function closeModal() { document.getElementById('successModal').classList.remove('show'); }

    function copyVal(btn, val) {
        navigator.clipboard.writeText(val).then(() => {
            btn.classList.add('copied'); btn.innerHTML = '<i class="fas fa-check mr-1"></i>Copied';
            setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = '<i class="fas fa-copy mr-1"></i>Copy'; }, 2000);
        });
    }

    function showError(msg) {
        document.getElementById('studentsTableBody').innerHTML = `<tr><td colspan="7"><div style="text-align:center;padding:4rem 2rem;">
            <i class="fas fa-exclamation-triangle" style="color:#fbbf24;font-size:1.75rem;margin-bottom:0.75rem;display:block;"></i>
            <p style="color:#64748b;font-size:0.875rem;font-weight:600;">${msg}</p>
            <button onclick="loadApproved()" style="margin-top:1rem;padding:0.5rem 1.25rem;font-size:0.75rem;font-weight:700;color:#1d4ed8;border:1.5px solid #bfdbfe;border-radius:0.5rem;background:white;cursor:pointer;">Retry</button>
        </div></td></tr>`;
    }

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.style.background = type === 'warn' ? '#b45309' : '#0d2470';
        t.textContent = msg; t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    window.onload = loadAll;
</script>
</body>
</html>
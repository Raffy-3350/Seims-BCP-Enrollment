<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCP SIEMS | Student Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Barlow+Condensed:wght@700;800;900&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(135deg,#e8edf5 0%,#dde4f0 100%); }
        .glass-input { background: #ffffff; border: 1.5px solid #dce4f0; transition: all 0.2s; border-radius: 0.6rem; }
        .glass-input:focus { border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29,78,216,0.12); outline: none; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .section-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; border-left: 4px solid #1535a0; padding-left: 1rem; }
        .info-card { background: linear-gradient(150deg, #0d2470 0%, #1535a0 40%, #1a3fb5 70%, #0d2470 100%); position: relative; overflow: hidden; }
        #successModal { display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(4,10,28,0.88); backdrop-filter: blur(12px); align-items: center; justify-content: center; padding: 1rem; }
        #successModal.show { display: flex; }
        .modal-card { background: linear-gradient(160deg,#0a1628 0%,#0d1f3c 50%,#0a1628 100%); border: 1px solid rgba(59,130,246,0.2); border-radius: 1.5rem; width: 100%; max-width: 420px; box-shadow: 0 40px 80px rgba(0,0,0,0.8); overflow: hidden; animation: modalPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
        @keyframes modalPop { from { opacity:0; transform:scale(0.88) translateY(30px); } to { opacity:1; transform:scale(1) translateY(0); } }
        .modal-header { background: linear-gradient(135deg,#14532d 0%,#166534 50%,#14532d 100%); padding: 1.5rem 1.75rem; display:flex; align-items:center; gap:1rem; border-bottom: 1px solid rgba(34,197,94,0.2); }
        .check-ring { width:48px; height:48px; border-radius:0.9rem; background: linear-gradient(135deg,#16a34a,#22c55e); border: 1px solid rgba(134,239,172,0.3); box-shadow: 0 4px 16px rgba(22,163,74,0.4); display:flex; align-items:center; justify-content:center; flex-shrink:0; animation: checkPop 0.4s 0.3s cubic-bezier(0.34,1.56,0.64,1) both; }
        @keyframes checkPop { from{transform:scale(0) rotate(-20deg);opacity:0;} to{transform:scale(1) rotate(0);opacity:1;} }
        .modal-body { padding: 1.5rem 1.75rem; }
        .info-item { display:flex; align-items:center; gap:0.875rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(59,130,246,0.1); border-radius:0.75rem; padding:0.75rem 1rem; margin-bottom:0.5rem; }
        .info-icon-box { width:32px; height:32px; border-radius:0.5rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.75rem; background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.15); }
        .info-text { flex:1; min-width:0; }
        .info-label { font-size:8.5px; font-weight:800; letter-spacing:0.12em; text-transform:uppercase; color:#3b5a8a; display:block; margin-bottom:2px; }
        .info-value { font-size:0.8rem; font-weight:700; color:#cbd5e1; }
        .notice-box { background: rgba(34,197,94,0.06); border: 1px solid rgba(34,197,94,0.2); border-radius:0.75rem; padding:0.875rem 1rem; display:flex; align-items:flex-start; gap:0.75rem; margin: 1rem 0; }
        .modal-actions { display:flex; gap:0.625rem; }
        .btn-new { flex:1; padding:0.8rem; background: rgba(255,255,255,0.03); border:1px solid rgba(59,130,246,0.2); border-radius:0.75rem; color:#64748b; font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; cursor:pointer; transition:all 0.2s; font-family:inherit; }
        .btn-new:hover { background:rgba(59,130,246,0.08); color:#94a3b8; }
        .btn-done { flex:1.5; padding:0.8rem; background: linear-gradient(135deg,#16a34a,#22c55e); border: 1px solid rgba(134,239,172,0.3); border-radius:0.75rem; color:white; font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; cursor:pointer; transition:all 0.2s; box-shadow: 0 6px 20px rgba(22,163,74,0.4); font-family:inherit; }
        .btn-done:hover { transform:translateY(-1px); }
        .modal-section-label { font-size: 9px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #3b82f6; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
        .modal-section-label::before, .modal-section-label::after { content:''; flex:1; height:1px; background:rgba(59,130,246,0.15); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

<div class="max-w-7xl w-full bg-white overflow-hidden flex flex-col lg:flex-row" style="height:92vh;border-radius:1.5rem;box-shadow:0 32px 80px rgba(13,36,112,0.18),0 2px 8px rgba(13,36,112,0.08);border:1px solid rgba(29,78,216,0.1);">

    <!-- LEFT: FORM -->
    <div class="lg:w-2/3 flex flex-col bg-white">

        <!-- Header -->
        <div class="p-8 border-b sticky top-0 z-20 flex justify-between items-center" style="background:#ffffff;border-color:#e0e8f8;border-bottom:2px solid #1535a0;">
            <div class="flex items-center gap-4">
                <img src="../siems.png" alt="BCP" class="h-12 w-12 object-contain">
                <div>
                    <h2 style="font-family:'Barlow Condensed',sans-serif;font-size:1.75rem;font-weight:900;color:#0d2470;letter-spacing:-0.01em;line-height:1;margin-bottom:4px;">STUDENT REGISTRATION</h2>
                    <p style="color:#1535a0;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:0.2em;">SIEMS — Bestlink College of the Philippines</p>
                </div>
            </div>
            <div class="text-right">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Enrollment Portal</span>
                <span style="font-size:0.7rem;font-weight:800;color:#0d2470;background:#eef2ff;border:1px solid rgba(21,53,160,0.2);padding:4px 14px;border-radius:999px;">AY 2026-2027</span>
            </div>
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
                        <input type="text" id="firstName" placeholder="Juan" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Middle Name</label>
                        <input type="text" id="middleName" placeholder="Protacio" class="w-full glass-input px-4 py-3 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Last Name <span class="text-red-400">*</span></label>
                        <input type="text" id="lastName" placeholder="Del Mundo" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-semibold">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Birth Date <span class="text-red-400">*</span></label>
                        <input type="date" id="birthDate" class="w-full glass-input px-4 py-3 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Gender <span class="text-red-400">*</span></label>
                        <select id="gender" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-semibold bg-white">
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">LRN (12-Digit) <span class="text-red-400">*</span></label>
                        <input type="text" id="lrnInput" placeholder="000000000000" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-mono" maxlength="12">
                    </div>
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
                        <input type="tel" id="mobileNumber" placeholder="09XX-XXX-XXXX" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Personal Email <span class="text-red-400">*</span></label>
                        <input type="email" id="personalEmail" placeholder="juan@gmail.com" class="w-full glass-input px-4 py-3 rounded-xl text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Street Address</label>
                        <input type="text" id="streetAddress" placeholder="123 Rizal Street" class="w-full glass-input px-4 py-3 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">City / Municipality</label>
                        <input type="text" id="city" placeholder="Caloocan City" class="w-full glass-input px-4 py-3 rounded-xl text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Province</label>
                        <input type="text" id="province" placeholder="Metro Manila" class="w-full glass-input px-4 py-3 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">ZIP Code</label>
                        <input type="text" id="zipCode" placeholder="1400" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-mono">
                    </div>
                </div>
            </section>

            <!-- 03: ACADEMIC PLACEMENT -->
            <section class="space-y-4">
                <div class="section-header">
                    <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:0.85rem;font-weight:800;color:#0d2470;text-transform:uppercase;letter-spacing:0.12em;">03. Academic Placement</h3>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Program / Course <span class="text-red-400">*</span></label>
                        <select id="studentProgram" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
                            <option value="" disabled selected>Select Program</option>
                            <optgroup label="College of Computer Studies">
                                <option>BS Information Technology</option>
                                <option>BS Computer Science</option>
                            </optgroup>
                            <optgroup label="College of Business & Accountancy">
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
                        <select id="studentYearLevel" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
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
                        <input type="text" id="major" placeholder="e.g., Network Technology" class="w-full glass-input px-4 py-3 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Enrollment Status</label>
                        <select id="enrollmentStatus" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
                            <option>Regular</option>
                            <option>Irregular</option>
                            <option>Transferee</option>
                            <option>Returnee</option>
                            <option>Freshmen</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- 04: GUARDIAN -->
            <section class="space-y-4">
                <div class="section-header">
                    <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:0.85rem;font-weight:800;color:#0d2470;text-transform:uppercase;letter-spacing:0.12em;">04. Guardian / Emergency Contact</h3>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Guardian Full Name <span class="text-red-400">*</span></label>
                        <input type="text" id="guardianName" placeholder="Maria Del Mundo" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Relationship <span class="text-red-400">*</span></label>
                        <select id="guardianRelationship" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-bold bg-white outline-none">
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
                        <input type="tel" id="guardianContact" placeholder="09XX-XXX-XXXX" class="w-full glass-input px-4 py-3 rounded-xl text-sm font-semibold">
                    </div>
                </div>
            </section>

            <!-- 05: FILE UPLOADS -->
            <section class="space-y-4">
                <div class="section-header">
                    <h3 style="font-family:'Barlow Condensed',sans-serif;font-size:0.85rem;font-weight:800;color:#0d2470;text-transform:uppercase;letter-spacing:0.12em;">05. Supporting Documents</h3>
                </div>
                <div id="dropzone" class="w-full border-2 border-dashed border-blue-300 rounded-xl p-8 text-center cursor-pointer transition bg-blue-50 hover:bg-blue-100"
                     ondrop="handleDrop(event)" ondragover="event.preventDefault(); event.target.style.backgroundColor='#dbeafe';" ondragleave="event.target.style.backgroundColor='#eff6ff';">
                    <i class="fas fa-cloud-upload-alt text-4xl text-blue-400 mb-3 block"></i>
                    <h4 class="text-base font-bold text-blue-600 mb-1">Drag & Drop Files Here</h4>
                    <p class="text-xs text-blue-500 mb-4">Or click to browse (PDF, DOC, DOCX, XLS, XLSX, PNG, JPG, GIF - max 10MB)</p>
                    <input type="file" id="fileInput" multiple style="display:none;" onchange="handleFiles(event.target.files);">
                    <button type="button" onclick="document.getElementById('fileInput').click();" 
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg text-sm font-semibold hover:bg-blue-600 transition">
                        Select Files
                    </button>
                </div>
                <div id="uploadedFilesList" class="space-y-2"></div>
            </section>

        </div>

        <!-- Footer -->
        <div class="p-8 flex justify-between items-center" style="border-top:2px solid #e0e8f8;background:#f5f8ff;">
            <div class="flex items-center gap-2 text-slate-400 text-xs">
                <i class="fas fa-info-circle text-blue-400"></i>
                <span>Fields marked <span class="text-red-400 font-bold">*</span> are required. No account is created at this step.</span>
            </div>
            <div class="flex gap-4">
                <button onclick="resetForm()" class="px-8 py-3 text-sm font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition">Reset Form</button>
                <button id="submitBtn" onclick="submitRegistration()" style="padding:0.875rem 3rem;background:linear-gradient(135deg,#0d2470,#1535a0);color:white;border-radius:0.6rem;font-size:0.8rem;font-weight:900;font-family:'Barlow Condensed',sans-serif;letter-spacing:0.12em;text-transform:uppercase;box-shadow:0 8px 24px rgba(13,36,112,0.35);border:none;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='linear-gradient(135deg,#1535a0,#1d4ed8)'" onmouseout="this.style.background='linear-gradient(135deg,#0d2470,#1535a0)'">
                    Submit Registration
                </button>
            </div>
        </div>
    </div>

    <!-- RIGHT: INFO PANEL -->
    <div class="lg:w-1/3 info-card p-8 text-white flex flex-col justify-between relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full pointer-events-none overflow-hidden">
            <div style="position:absolute;top:-80px;right:-80px;width:300px;height:300px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>
            <div style="position:absolute;bottom:-60px;left:-40px;width:220px;height:220px;background:rgba(255,255,255,0.04);border-radius:50%;"></div>
            <div style="position:absolute;top:40%;left:50%;width:180px;height:180px;background:rgba(96,165,250,0.06);border-radius:50%;transform:translate(-50%,-50%);"></div>
        </div>

        <div class="relative z-10 flex flex-col gap-6 flex-grow justify-center">
            <h2 style="font-family:'Barlow Condensed',sans-serif;font-size:2.5rem;font-weight:900;letter-spacing:-0.01em;line-height:1;color:white;">REGISTRATION<br>FLOW</h2>

            <!-- Steps -->
            <div class="space-y-3">
                <!-- Step 1 — active -->
                <div style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.22);border-radius:0.875rem;padding:1rem 1.25rem;display:flex;align-items:flex-start;gap:0.875rem;">
                    <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(34,197,94,0.4);">
                        <span style="font-size:11px;font-weight:900;color:white;">1</span>
                    </div>
                    <div>
                        <p style="font-size:10px;font-weight:900;color:#22c55e;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:2px;">You Are Here</p>
                        <p style="font-size:0.85rem;font-weight:700;color:white;line-height:1.3;">Student Registration</p>
                        <p style="font-size:10px;color:rgba(255,255,255,0.65);margin-top:3px;">Submit personal &amp; academic info. Status: <span style="color:#fbbf24;font-weight:700;">pending</span>. No credentials yet.</p>
                    </div>
                </div>
                <!-- Step 2 -->
                <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:0.875rem;padding:1rem 1.25rem;display:flex;align-items:flex-start;gap:0.875rem;opacity:0.65;">
                    <div style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-size:11px;font-weight:900;color:rgba(255,255,255,0.6);">2</span>
                    </div>
                    <div>
                        <p style="font-size:10px;font-weight:900;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:2px;">Admin Dashboard</p>
                        <p style="font-size:0.85rem;font-weight:700;color:rgba(255,255,255,0.75);line-height:1.3;">Account Provisioning</p>
                        <p style="font-size:10px;color:rgba(255,255,255,0.45);margin-top:3px;">Admin reviews approved students &amp; generates institutional email + password.</p>
                    </div>
                </div>
                <!-- Step 3 -->
                <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:0.875rem;padding:1rem 1.25rem;display:flex;align-items:flex-start;gap:0.875rem;opacity:0.4;">
                    <div style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-size:11px;font-weight:900;color:rgba(255,255,255,0.5);">3</span>
                    </div>
                    <div>
                        <p style="font-size:10px;font-weight:900;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:2px;">Student</p>
                        <p style="font-size:0.85rem;font-weight:700;color:rgba(255,255,255,0.5);line-height:1.3;">First Login &amp; Password Change</p>
                        <p style="font-size:10px;color:rgba(255,255,255,0.3);margin-top:3px;">Student logs in with generated credentials and sets a permanent password.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notices -->
        <div class="relative z-10 mt-6 space-y-2.5">
            <div style="padding:0.875rem;background:rgba(255,255,255,0.06);border-radius:0.75rem;border:1px solid rgba(255,255,255,0.1);font-size:10px;color:rgba(255,255,255,0.7);line-height:1.6;display:flex;align-items:flex-start;gap:0.5rem;">
                <i class="fas fa-clock text-yellow-400 mt-0.5 text-xs flex-shrink-0"></i>
                <span>After submission, await admin approval. You will receive login credentials via personal email once your account is provisioned.</span>
            </div>
            <div style="padding:0.875rem;background:rgba(255,255,255,0.04);border-radius:0.75rem;border:1px solid rgba(255,255,255,0.08);font-size:10px;color:rgba(255,255,255,0.6);line-height:1.6;display:flex;align-items:flex-start;gap:0.5rem;">
                <i class="fas fa-shield-alt text-emerald-400 mt-0.5 text-xs flex-shrink-0"></i>
                <span>No login credentials are issued at this step. Data is securely stored pending admin review.</span>
            </div>
        </div>
    </div>
</div>

<!-- SUCCESS MODAL -->
<div id="successModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="check-ring"><i class="fas fa-check text-white text-lg"></i></div>
            <div>
                <p class="text-white text-xl font-black tracking-tight leading-none mb-0.5">Registration Submitted!</p>
                <p class="text-green-200 text-[10px] font-semibold uppercase tracking-widest">Awaiting admin approval</p>
            </div>
        </div>
        <div class="modal-body">
            <div class="modal-section-label">Registration Summary</div>
            <div id="modalInfo"></div>
            <div class="notice-box">
                <i class="fas fa-info-circle text-green-400 text-sm flex-shrink-0 mt-0.5"></i>
                <p class="text-green-300 text-[10px] font-semibold leading-relaxed">
                    Registration is <strong class="text-yellow-300">pending</strong> admin approval. Login credentials will be sent to <span id="modalEmail" class="text-green-200 font-black"></span> once your account is created.
                </p>
            </div>
            <div class="modal-actions">
                <button class="btn-new" onclick="closeModal()"><i class="fas fa-plus mr-1.5"></i>New Registration</button>
                <button class="btn-done" onclick="closeModal()"><i class="fas fa-check mr-1.5"></i>Done</button>
            </div>
        </div>
    </div>
</div>

<script>
    const API_BASE_URL = '../api';
    let uploadedFiles = [];

    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        const files = e.dataTransfer.files;
        handleFiles(files);
    }

    function handleFiles(files) {
        for (let file of files) {
            uploadFile(file);
        }
    }

    async function uploadFile(file) {
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

        const fileItem = createFileItem(file.name, 'uploading');
        const filesList = document.getElementById('uploadedFilesList');
        filesList.appendChild(fileItem);

        const formData = new FormData();
        formData.append('file', file);
        formData.append('user_id', 'TEMP_' + Date.now());

        try {
            const response = await fetch(`../../user-creation/api/upload_file.php`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                uploadedFiles.push({
                    id: result.file_id,
                    name: result.original_name,
                    file_name: result.file_name
                });
                fileItem.classList.remove('opacity-50');
                fileItem.querySelector('.status-icon').innerHTML = '<i class="fas fa-check-circle text-green-500"></i>';
                fileItem.querySelector('.status-text').textContent = 'Uploaded';
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            fileItem.querySelector('.status-icon').innerHTML = '<i class="fas fa-times-circle text-red-500"></i>';
            fileItem.querySelector('.status-text').textContent = 'Failed: ' + error.message;
        }
    }

    function createFileItem(fileName, status) {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200 ' + (status === 'uploading' ? 'opacity-50' : '');
        div.innerHTML = `
            <div class="flex items-center gap-3 flex-grow">
                <i class="fas fa-file text-blue-500 text-lg"></i>
                <div class="flex-grow min-w-0">
                    <p class="text-sm font-semibold text-slate-700 truncate">${fileName}</p>
                    <p class="text-xs text-slate-500">Uploading...</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="status-icon"><i class="fas fa-spinner fa-spin text-blue-500"></i></span>
                <span class="status-text text-xs font-medium text-slate-600">Uploading...</span>
            </div>
        `;
        return div;
    }

    async function submitRegistration() {
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerText;

        const formData = {
            firstName:            document.getElementById('firstName').value.trim(),
            middleName:           document.getElementById('middleName').value.trim(),
            lastName:             document.getElementById('lastName').value.trim(),
            birthDate:            document.getElementById('birthDate').value,
            gender:               document.getElementById('gender').value,
            lrn:                  document.getElementById('lrnInput').value.trim(),
            mobileNumber:         document.getElementById('mobileNumber').value.trim(),
            personalEmail:        document.getElementById('personalEmail').value.trim(),
            streetAddress:        document.getElementById('streetAddress').value.trim(),
            city:                 document.getElementById('city').value.trim(),
            province:             document.getElementById('province').value.trim(),
            zipCode:              document.getElementById('zipCode').value.trim(),
            program:              document.getElementById('studentProgram').value,
            yearLevel:            document.getElementById('studentYearLevel').value,
            major:                document.getElementById('major').value.trim(),
            enrollmentStatus:     document.getElementById('enrollmentStatus').value,
            guardianName:         document.getElementById('guardianName').value.trim(),
            guardianRelationship: document.getElementById('guardianRelationship').value,
            guardianContact:      document.getElementById('guardianContact').value.trim(),
        };

        // Validation
        if (!formData.firstName || !formData.lastName) { alert('⚠️ First and Last Name are required.'); return; }
        if (!formData.birthDate)                        { alert('⚠️ Birth Date is required.'); return; }
        if (!formData.lrn || formData.lrn.length !== 12){ alert('⚠️ LRN must be exactly 12 digits.'); return; }
        if (!formData.mobileNumber)                     { alert('⚠️ Mobile Number is required.'); return; }
        if (!formData.personalEmail)                    { alert('⚠️ Personal Email is required.'); return; }
        if (!formData.program)                          { alert('⚠️ Please select a Program.'); return; }
        if (!formData.guardianName || !formData.guardianContact) { alert('⚠️ Guardian information is required.'); return; }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>SUBMITTING...';

        try {
            const response = await fetch(`${API_BASE_URL}/student_register.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();
            if (result.success) {
                showSuccessModal(formData, result.registrationId);
            } else {
                alert('❌ Registration Failed\n\n' + result.message);
            }
        } catch (error) {
            alert('❌ Connection Error\n\nMake sure XAMPP is running.\n\n' + error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    }
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerText;

        const formData = {
            firstName:            document.getElementById('firstName').value.trim(),
            middleName:           document.getElementById('middleName').value.trim(),
            lastName:             document.getElementById('lastName').value.trim(),
            birthDate:            document.getElementById('birthDate').value,
            gender:               document.getElementById('gender').value,
            lrn:                  document.getElementById('lrnInput').value.trim(),
            mobileNumber:         document.getElementById('mobileNumber').value.trim(),
            personalEmail:        document.getElementById('personalEmail').value.trim(),
            streetAddress:        document.getElementById('streetAddress').value.trim(),
            city:                 document.getElementById('city').value.trim(),
            province:             document.getElementById('province').value.trim(),
            zipCode:              document.getElementById('zipCode').value.trim(),
            program:              document.getElementById('studentProgram').value,
            yearLevel:            document.getElementById('studentYearLevel').value,
            major:                document.getElementById('major').value.trim(),
            enrollmentStatus:     document.getElementById('enrollmentStatus').value,
            guardianName:         document.getElementById('guardianName').value.trim(),
            guardianRelationship: document.getElementById('guardianRelationship').value,
            guardianContact:      document.getElementById('guardianContact').value.trim(),
        };

        // Validation
        if (!formData.firstName || !formData.lastName) { alert('⚠️ First and Last Name are required.'); return; }
        if (!formData.birthDate)                        { alert('⚠️ Birth Date is required.'); return; }
        if (!formData.lrn || formData.lrn.length !== 12){ alert('⚠️ LRN must be exactly 12 digits.'); return; }
        if (!formData.mobileNumber)                     { alert('⚠️ Mobile Number is required.'); return; }
        if (!formData.personalEmail)                    { alert('⚠️ Personal Email is required.'); return; }
        if (!formData.program)                          { alert('⚠️ Please select a Program.'); return; }
        if (!formData.guardianName || !formData.guardianContact) { alert('⚠️ Guardian information is required.'); return; }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>SUBMITTING...';

        try {
            const response = await fetch(`${API_BASE_URL}/register_student.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();
            if (result.success) {
                showSuccessModal(formData, result.registrationId);
            } else {
                alert('❌ Registration Failed\n\n' + result.message);
            }
        } catch (error) {
            alert('❌ Connection Error\n\nMake sure XAMPP is running.\n\n' + error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }


    function showSuccessModal(data, regId) {
        const fullName = `${data.firstName}${data.middleName ? ' ' + data.middleName : ''} ${data.lastName}`;
        const items = [
            { icon: 'fa-user',           iconColor: '#94a3b8', label: 'Full Name',        value: fullName },
            { icon: 'fa-id-card',        iconColor: '#22d3ee', label: 'Registration ID',  value: regId || 'REG-' + Date.now() },
            { icon: 'fa-graduation-cap', iconColor: '#60a5fa', label: 'Program',           value: data.program },
            { icon: 'fa-layer-group',    iconColor: '#a78bfa', label: 'Year Level',        value: data.yearLevel },
            { icon: 'fa-hashtag',        iconColor: '#fbbf24', label: 'LRN',               value: data.lrn },
        ];
        document.getElementById('modalInfo').innerHTML = items.map(c => `
            <div class="info-item">
                <div class="info-icon-box"><i class="fas ${c.icon}" style="color:${c.iconColor}"></i></div>
                <div class="info-text">
                    <span class="info-label">${c.label}</span>
                    <span class="info-value">${c.value}</span>
                </div>
            </div>
        `).join('');
        document.getElementById('modalEmail').textContent = data.personalEmail;
        document.getElementById('successModal').classList.add('show');
    }

    function closeModal() {
        document.getElementById('successModal').classList.remove('show');
        resetForm();
    }

    function resetForm() {
        document.querySelectorAll('input[type="text"],input[type="tel"],input[type="email"],input[type="date"]').forEach(i => i.value = '');
        document.getElementById('studentProgram').selectedIndex = 0;
    }
</script>
</body>
</html>
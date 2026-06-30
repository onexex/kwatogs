@extends('layout.app', ['title' => 'Documentation'])
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8;
        --bg:#f1f5f9; --surface:#ffffff; --border:#e2e8f0;
        --radius-card:14px; --radius-input:8px;
        --shadow-card:0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }
    .doc-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }

    .doc-topbar {
        background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:18px 22px; margin-bottom:20px;
        display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;
    }
    .doc-topbar .page-title { font-size:1.15rem; font-weight:800; color:var(--slate); margin:0; letter-spacing:-.2px; }
    .doc-topbar .page-sub { font-size:.8rem; color:var(--muted); margin:3px 0 0; }

    /* layout: sticky TOC + content */
    .doc-grid { display:grid; grid-template-columns:230px 1fr; gap:20px; align-items:start; }
    @media (max-width:900px){ .doc-grid { grid-template-columns:1fr; } .doc-toc { position:static !important; } }

    .doc-toc {
        background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:14px; position:sticky; top:16px;
    }
    .doc-toc h6 { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--slate-light); margin:0 0 8px; }
    .doc-toc a {
        display:block; padding:7px 10px; border-radius:8px; font-size:.82rem; font-weight:600;
        color:var(--slate); text-decoration:none; transition:all .15s;
    }
    .doc-toc a:hover { background:var(--teal-light); color:var(--teal-dark); }
    .doc-toc a .chip { font-size:.62rem; color:var(--muted); font-weight:700; float:right; }

    .sc {
        background:var(--surface); border-radius:var(--radius-card); border:1px solid var(--border);
        box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; scroll-margin-top:16px;
    }
    .sc-head {
        display:flex; align-items:center; gap:10px; padding:14px 22px;
        border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa);
    }
    .sc-icon {
        width:32px; height:32px; border-radius:8px; background:var(--teal-light); color:var(--teal);
        display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0;
    }
    .sc-title { font-size:.82rem; font-weight:800; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .sc-sub { font-size:.72rem; color:var(--muted); margin:1px 0 0; }
    .sc-body { padding:0; }
    .sc-body.prose { padding:18px 22px; }
    .sc-body.prose p { font-size:.86rem; color:var(--slate); line-height:1.6; margin:0 0 10px; }
    .sc-body.prose p:last-child { margin-bottom:0; }

    .doc-table { width:100%; margin:0; border-collapse:collapse; }
    .doc-table thead th {
        font-size:.66rem; font-weight:800; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px;
        border-bottom:2px solid var(--border); background:#f8fafc; padding:11px 16px; white-space:nowrap; text-align:left;
    }
    .doc-table tbody td { font-size:.82rem; color:var(--slate); vertical-align:top; padding:11px 16px; border-bottom:1px solid var(--border); }
    .doc-table tbody tr:last-child td { border-bottom:none; }
    .doc-table tbody tr:hover { background:var(--teal-light); }
    .doc-table .item-name { font-weight:700; color:var(--slate); white-space:nowrap; }
    .doc-table .item-name i { color:var(--teal); width:18px; text-align:center; margin-right:6px; }

    code.kk {
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.74rem;
        background:#f1f5f9; border:1px solid var(--border); border-radius:5px; padding:1px 6px; color:var(--teal-dark); white-space:nowrap;
    }
    .url { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.74rem; color:var(--slate-light); white-space:nowrap; }

    .badge-soft {
        display:inline-block; font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.4px;
        padding:3px 9px; border-radius:20px; background:var(--teal-light); color:var(--teal-dark); border:1px solid var(--teal-mid);
    }
    .note {
        background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:12px 14px;
        font-size:.8rem; color:#92400e; line-height:1.55; margin:0 22px 18px;
    }
    .note b { color:#78350f; }
</style>

<div class="doc-shell">

    <div class="doc-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-book me-2" style="color:var(--teal)"></i>System Documentation — Menu &amp; Functions</p>
            <p class="page-sub">A reference guide to the sidebar navigation, what each screen does, and the permission that controls it.</p>
        </div>
        <span class="badge-soft">Updated {{ now()->format('M d, Y') }}</span>
    </div>

    <div class="doc-grid">

        {{-- ── Table of contents ── --}}
        <nav class="doc-toc">
            <h6>On this page</h6>
            <a href="#overview">Overview</a>
            <a href="#homepage">Home Page Guide</a>
            <a href="#pinned">Top Menu <span class="chip">2</span></a>
            <a href="#workforce">Workforce <span class="chip">20</span></a>
            <a href="#settings">Settings <span class="chip">28</span></a>
            <a href="#reports">Reports <span class="chip">5</span></a>
            <a href="#access">How Access Works</a>
        </nav>

        <div>
            {{-- ── Overview ── --}}
            <section class="sc" id="overview">
                <div class="sc-head">
                    <div class="sc-icon"><i class="fa-solid fa-circle-info"></i></div>
                    <div>
                        <h5 class="sc-title">Overview</h5>
                        <p class="sc-sub">How the sidebar is organized</p>
                    </div>
                </div>
                <div class="sc-body prose">
                    <p>The left sidebar is the main way to move around the system. It has two pinned shortcuts at the top (Home and Registration) followed by three collapsible groups: <b>Workforce</b> (day-to-day operations), <b>Settings</b> (master data and system management), and <b>Reports</b> (analysis and exports).</p>
                    <p>Each menu item is tied to a permission. You only see an item if your assigned role has the matching permission, so two users can see very different menus. A group heading (Workforce, Settings, Reports) only appears if you can access at least one item inside it. Roles and permissions are managed from <b>Settings → Employee Role</b> and <b>Settings → User Roles</b>.</p>
                    <p>The tables below list every menu item, the page it opens, the permission key behind it, and a short description of what it does.</p>
                </div>
            </section>

            {{-- ── Home Page Guide ── --}}
            <section class="sc" id="homepage">
                <div class="sc-head">
                    <div class="sc-icon"><i class="fa-solid fa-house"></i></div>
                    <div>
                        <h5 class="sc-title">Home Page Guide</h5>
                        <p class="sc-sub">Attendance dashboard, Time In/Out steps, and button reference</p>
                    </div>
                </div>

                {{-- Time In / Time Out Steps --}}
                <div class="sc-body prose" style="border-bottom:1px solid var(--border);">
                    <p style="font-weight:800;color:var(--slate);font-size:.9rem;margin-bottom:10px;">
                        <i class="fa-solid fa-list-ol me-2" style="color:var(--teal)"></i>How to Time In and Time Out
                    </p>

                    <p style="font-weight:700;color:var(--slate);margin:0 0 6px;">Logging your Time In</p>
                    <ol style="font-size:.86rem;color:var(--slate);line-height:1.8;padding-left:1.4rem;margin-bottom:14px;">
                        <li>From the sidebar, click <b>Home</b> (or navigate to <code class="kk">/</code>).</li>
                        <li>At the bottom-right of the page, locate the <b style="color:var(--teal)">Time In</b> button (teal/green, pill-shaped).</li>
                        <li>Click <b>Time In</b>. A confirmation dialog will appear asking <em>"Ready to log your attendance?"</em></li>
                        <li>Click <b>Yes, Time In</b> to confirm. The system records the current timestamp as your time-in entry.</li>
                        <li>A success notification appears briefly. The <b>Attendance Log</b> table refreshes automatically showing your new entry.</li>
                    </ol>

                    <p style="font-weight:700;color:var(--slate);margin:0 0 6px;">Logging your Time Out</p>
                    <ol style="font-size:.86rem;color:var(--slate);line-height:1.8;padding-left:1.4rem;margin-bottom:14px;">
                        <li>At the end of your shift, return to the <b>Home</b> page.</li>
                        <li>At the bottom-right, locate the <b style="color:#ef4444">Time Out</b> button (red, pill-shaped).</li>
                        <li>Click <b>Time Out</b>. A confirmation dialog appears: <em>"End your shift for the day?"</em></li>
                        <li>Click <b>Yes, Time Out</b> to confirm. The system stamps the current time as your time-out.</li>
                        <li>The table refreshes. The <b>Daily Summary</b> row for today will now show your total hours worked, any late deductions, and your attendance status.</li>
                    </ol>

                    <div class="note" style="margin:0 0 6px;">
                        <b>Important:</b> Always Time In at the start of your shift and Time Out at the end. Missed punches result in an <em>Incomplete</em> status on the daily log. If you forget, ask HR to correct the entry via <b>Workforce → Adjustment Time</b>.
                    </div>
                </div>

                {{-- Home Page Buttons --}}
                <div class="sc-body" style="padding:18px 22px 4px;">
                    <p style="font-weight:800;color:var(--slate);font-size:.9rem;margin-bottom:10px;">
                        <i class="fa-solid fa-computer-mouse me-2" style="color:var(--teal)"></i>All Buttons &amp; Controls on the Home Page
                    </p>
                </div>
                <div class="sc-body">
                    <table class="doc-table">
                        <thead><tr><th>Button / Control</th><th>Location</th><th>What it does</th></tr></thead>
                        <tbody>
                            <tr>
                                <td class="item-name"><i class="fa-solid fa-clock"></i>Time In</td>
                                <td>Bottom-right corner (teal pill button)</td>
                                <td>Opens a confirmation dialog and records your time-in for the current moment. The attendance table auto-refreshes after a successful punch.</td>
                            </tr>
                            <tr>
                                <td class="item-name"><i class="fa-solid fa-right-from-bracket"></i>Time Out</td>
                                <td>Bottom-right corner (red pill button)</td>
                                <td>Opens a confirmation dialog and records your time-out for the current moment. The daily summary row updates with hours worked, late minutes, and status.</td>
                            </tr>
                            <tr>
                                <td class="item-name"><i class="fa-solid fa-rotate"></i>Refresh (↺)</td>
                                <td>Top-right date range bar (teal icon button)</td>
                                <td>Reloads the attendance log table for the selected date range without reloading the full page. Use this after an HR adjustment to see updated data.</td>
                            </tr>
                            <tr>
                                <td class="item-name"><i class="fa-solid fa-calendar"></i>Date From / Date To</td>
                                <td>Top-right date range bar</td>
                                <td>Set the start and end dates of the attendance period you want to view. Defaults to the last 10 days through today. Change the dates then click <b>Refresh</b> to reload.</td>
                            </tr>
                            <tr>
                                <td class="item-name"><i class="fa-solid fa-wand-magic-sparkles"></i>Kuya Kwatogs (floating button)</td>
                                <td>Fixed bottom-right corner (teal pill, above Time In/Out)</td>
                                <td>Opens the <b>Schedule Change Assistant</b> — a guided chatbot-style form to file a schedule adjustment request (change your shift time for a specific day). Only visible if your role has the <code class="kk">createschedulechange</code> permission.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Summary Cards --}}
                <div class="sc-body" style="padding:18px 22px 4px;">
                    <p style="font-weight:800;color:var(--slate);font-size:.9rem;margin-bottom:10px;">
                        <i class="fa-solid fa-chart-simple me-2" style="color:var(--teal)"></i>Summary Cards (top of Home page)
                    </p>
                </div>
                <div class="sc-body">
                    <table class="doc-table">
                        <thead><tr><th>Card</th><th>Shows</th><th>Notes</th></tr></thead>
                        <tbody>
                            <tr>
                                <td class="item-name"><i class="fa-solid fa-clock"></i>Total Hours</td>
                                <td>Sum of all work hours logged in the selected date range.</td>
                                <td>Updates each time the table reloads.</td>
                            </tr>
                            <tr>
                                <td class="item-name"><i class="fa-solid fa-hourglass-half"></i>Late Deductions</td>
                                <td>Total accumulated late minutes across all days in the selected range.</td>
                                <td>Displayed in minutes. Computed from each day's daily summary row.</td>
                            </tr>
                            <tr>
                                <td class="item-name"><i class="fa-solid fa-circle-check"></i>Period Status</td>
                                <td>An overall status label for the selected period.</td>
                                <td><b>Perfect Attendance</b> — no lates and no incomplete logs. <b>Active (With Lates)</b> — all punches complete but some lates exist. <b>Needs Action</b> — at least one day has an Incomplete or Missing log; contact HR.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Kuya Kwatogs assistant --}}
                <div class="sc-body prose" style="border-top:1px solid var(--border);">
                    <p style="font-weight:800;color:var(--slate);font-size:.9rem;margin-bottom:6px;">
                        <i class="fa-solid fa-robot me-2" style="color:var(--teal)"></i>Kuya Kwatogs — Schedule Change Assistant
                    </p>
                    <p>Click the floating <b>Kuya Kwatogs</b> button to open the assistant panel. You can use the <b>Guided</b> mode (step-by-step questions) or switch to <b>Quick Form</b> mode using the toggle in the panel header.</p>
                    <ol style="font-size:.86rem;color:var(--slate);line-height:1.8;padding-left:1.4rem;margin-bottom:0;">
                        <li>Choose the <b>date</b> you need to adjust (today or a future date).</li>
                        <li>Enter the <b>new time in</b> and <b>new time out</b> for that day.</li>
                        <li>Type a short <b>reason</b> — your approver will see this.</li>
                        <li>Review the summary and click <b>Submit</b>.</li>
                        <li>Your request appears in the <em>My recent requests</em> list at the bottom of the panel with a status of <b>For Approval</b>. An authorized approver (Settings → Pending Schedule Requests) will act on it.</li>
                    </ol>
                </div>
            </section>

            {{-- ── Pinned ── --}}
            <section class="sc" id="pinned">
                <div class="sc-head">
                    <div class="sc-icon"><i class="fa-solid fa-thumbtack"></i></div>
                    <div>
                        <h5 class="sc-title">Top Menu (Pinned)</h5>
                        <p class="sc-sub">Always near the top of the sidebar</p>
                    </div>
                </div>
                <div class="sc-body">
                    <table class="doc-table">
                        <thead><tr><th>Menu Item</th><th>Opens</th><th>Permission</th><th>What it does</th></tr></thead>
                        <tbody>
                            <tr><td class="item-name"><i class="fa-solid fa-house"></i>Home</td><td class="url">/</td><td><code class="kk">home</code></td><td>Landing dashboard and personal attendance overview after login.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-user-plus"></i>Registration</td><td class="url">/pages/modules/registration</td><td><code class="kk">registration</code></td><td>Register and enroll new employees into the system.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- ── Workforce ── --}}
            <section class="sc" id="workforce">
                <div class="sc-head">
                    <div class="sc-icon"><i class="fa-solid fa-cubes"></i></div>
                    <div>
                        <h5 class="sc-title">Workforce <span class="badge-soft ms-1">Operations</span></h5>
                        <p class="sc-sub">Everyday HR, attendance and payroll tasks</p>
                    </div>
                </div>
                <div class="sc-body">
                    <table class="doc-table">
                        <thead><tr><th>Menu Item</th><th>Opens</th><th>Permission</th><th>What it does</th></tr></thead>
                        <tbody>
                            <tr><td class="item-name"><i class="fa-solid fa-paper-plane"></i>Adjustment Time</td><td class="url">/pages/modules/adjustmentTime</td><td><code class="kk">manual_entry</code></td><td>HR manual time adjustment for missed or incorrect time entries.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-file-import"></i>Attendance Import</td><td class="url">/attendance-import</td><td><code class="kk">attendanceimport</code></td><td>Bulk-import attendance/biometric logs from a spreadsheet.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-receipt"></i>Debit Advise</td><td class="url">/pages/modules/debitAdvise</td><td><code class="kk">debitadvise</code></td><td>Prepare bank debit advice for salary disbursement.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-id-badge"></i>E-201</td><td class="url">/pages/modules/E201</td><td><code class="kk">e201</code></td><td>Employee 201 file — personal, employment and document records.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-door-open"></i>Earlyout</td><td class="url">/pages/modules/earlyout</td><td><code class="kk">earlyout</code></td><td>File and track early-out requests.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-user-gear"></i>Enroll Employee</td><td class="url">/pages/modules/registration</td><td><code class="kk">enrollemployee</code></td><td>Add a new employee record and onboarding details.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-gauge-high"></i>HR Dashboard</td><td class="url">/pages/management/hr-dashboard</td><td><code class="kk">hrdashboard</code></td><td>High-level HR metrics and summaries at a glance.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-calendar-day"></i>Leave Application</td><td class="url">/pages/modules/leaveApplication</td><td><code class="kk">leaveapplication</code></td><td>File a leave application on behalf of an employee.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-calendar-check"></i>Leave Import</td><td class="url">/leave-import</td><td><code class="kk">leaveimport</code></td><td>Bulk-import leave records from a spreadsheet.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-hand-holding-dollar"></i>Loans &amp; Charges</td><td class="url">/pages/modules/loanManagement</td><td><code class="kk">loanmanagement</code></td><td>Manage employee loans, cash advances, charges and repayment schedules.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-map-location-dot"></i>OB Tracker</td><td class="url">/pages/modules/obtTracker</td><td><code class="kk">obttracker</code></td><td>Track official business trips and out-of-office assignments.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-file-circle-exclamation"></i>Notices &amp; Memos</td><td class="url">/pages/modules/notices</td><td><code class="kk">noticemanagement</code></td><td>Issue memos and disciplinary notices to employees. 4+ active disciplinary notices auto-recommends suspension for HR review; names surface on the HR Dashboard. Employees view their own at <code class="kk">/pages/modules/mynotices</code> (no permission needed).</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-file-signature"></i>Certificate of Employment</td><td class="url">/pages/modules/coe</td><td><code class="kk">coemanagement</code></td><td>Review employee COE requests and approve them with a drawn e-signature (optionally including salary); approved certificates are downloadable as a PDF. Employees request their own at <code class="kk">/pages/modules/mycoe</code> (no permission needed) — gated on active employment, a complete profile, required fields, and no pending request. Pending counts surface on the HR Dashboard. Separated employees can't self-serve (they're blocked from login); HR issues their COE from this screen, gated on the offboarding clearance ticked in E-201 → Update Status. The e-signature comes from a configured signatory (see COE Signatories), not a drawn pad.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-signature"></i>COE Signatories</td><td class="url">/pages/management/coe-signatories</td><td><code class="kk">coemanagement</code></td><td>Maintain the authorized signatories (name + title + uploaded e-signature image) that can be stamped on a Certificate of Employment. HR picks one per COE when approving/issuing; the chosen signatory is frozen onto that certificate. Reached via "Manage Signatories" on the COE screen.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-user-clock"></i>Overtime</td><td class="url">/pages/modules/overtime</td><td><code class="kk">overtime</code></td><td>File overtime for employees.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-clock"></i>Overtime Import</td><td class="url">/overtime-import</td><td><code class="kk">overtimeimport</code></td><td>Bulk-import overtime records from a spreadsheet.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-sliders"></i>Pay Adjustments</td><td class="url">/pages/modules/payadjustments</td><td><code class="kk">payadjustments</code></td><td>Add one-off additions or deductions applied to a payroll run.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-clipboard-list"></i>Payroll Logs</td><td class="url">/payroll-logs</td><td><code class="kk">payrolllogs</code></td><td>Review the detailed computation breakdown behind each payslip.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-award"></i>Programs</td><td class="url">/pages/modules/programs</td><td><code class="kk">programs</code></td><td>Define years-of-service milestones and their benefits (e.g. 2 years &rarr; rice). Tracks who has reached each milestone, who is due, and which benefits were granted — also surfaced on the HR Dashboard.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-file-invoice-dollar"></i>Payroll System</td><td class="url">/pages/modules/payroll</td><td><code class="kk">payroll</code></td><td>Generate, review and process employee payroll for a pay period.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-paper-plane"></i>Payslip Email Sending</td><td class="url">"Email Payslips" button on Payroll System</td><td><code class="kk">payslipemail</code></td><td>Send password-protected payslip PDFs to employees' emails (manual or auto-send on approval). Requires an active integration under Mail Integration.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-calendar-day"></i>Pending Leave Requests</td><td class="url">/pages/modules/leaverequests</td><td><code class="kk">pendingleaverequests</code></td><td>Approve or reject pending leave requests (badge shows the count awaiting you).</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-calendar-day"></i>Pending Overtime Requests</td><td class="url">/pages/modules/overtimerequests</td><td><code class="kk">pendingovertimerequests</code></td><td>Approve or reject pending overtime requests.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-calendar-check"></i>Pending Schedule Requests</td><td class="url">/pages/modules/schedulerequests</td><td><code class="kk">approveschedulechange</code></td><td>Review and approve/reject employee schedule-change requests.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-calendar-plus"></i>Schedule Import</td><td class="url">/schedule-import</td><td><code class="kk">scheduleimport</code></td><td>Bulk-assign shifts from a spreadsheet — one row covers a date range (optionally filtered to weekdays) and expands to per-day schedules. Overlaps are rejected; imports can be rolled back from Import History.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-paper-plane"></i>Send to OBT</td><td class="url">/pages/modules/sendOBT</td><td><code class="kk">sendobt</code></td><td>Send approved records to the OBT system.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- ── Settings ── --}}
            <section class="sc" id="settings">
                <div class="sc-head">
                    <div class="sc-icon"><i class="fa-solid fa-gears"></i></div>
                    <div>
                        <h5 class="sc-title">Settings <span class="badge-soft ms-1">Management</span></h5>
                        <p class="sc-sub">Master data, configuration and system administration</p>
                    </div>
                </div>
                <div class="sc-body">
                    <table class="doc-table">
                        <thead><tr><th>Menu Item</th><th>Opens</th><th>Permission</th><th>What it does</th></tr></thead>
                        <tbody>
                            <tr><td class="item-name"><i class="fa-solid fa-id-card"></i>Admin E-201</td><td class="url">/pages/management/e201</td><td><code class="kk">admine201</code></td><td>Administrative management of employee 201 files.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-user-gear"></i>Manage Employee Status</td><td class="url">E-201 &rarr; Update Status</td><td><code class="kk">manageemployeestatus</code></td><td>On the E-201 viewer: separate an employee (Resigned / End of Contract) with reason, date &amp; auto-computed years rendered, and/or apply an independent Red Flag / Blacklist with reason.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-building-shield"></i>Agencies</td><td class="url">/pages/management/agencies</td><td><code class="kk">agencies</code></td><td>Maintain manpower/recruitment agency records.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-box-archive"></i>Archive</td><td class="url">/pages/management/archive</td><td><code class="kk">archive</code></td><td>Manage archived (resigned/inactive) employee records.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-clipboard-list"></i>Audit Trail</td><td class="url">/pages/management/audit-trail</td><td><code class="kk">auditlog</code></td><td>View a log of system changes and user activity.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-tags"></i>Classification</td><td class="url">/pages/management/classification</td><td><code class="kk">classification</code></td><td>Maintain employee classification categories.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-building"></i>Companies</td><td class="url">/pages/management/companies</td><td><code class="kk">companies</code></td><td>Maintain company/employer records.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-database"></i>Database Backup</td><td class="url">/pages/management/databasebackup</td><td><code class="kk">databasebackup</code></td><td>Create, download, restore and delete database backups; import an external .sql/.sql.gz dump to replace or merge into the current database.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-sitemap"></i>Departments</td><td class="url">/pages/management/departments</td><td><code class="kk">departments</code></td><td>Maintain department records and structure.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-book"></i>Documentation</td><td class="url">/pages/management/documentation</td><td><span class="url">all users</span></td><td>This guide — the menu reference and function snapshots you are reading now.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-user-tag"></i>Emp Status</td><td class="url">/pages/management/employeestatus</td><td><code class="kk">employeestatus</code></td><td>Maintain employment status types (regular, probationary, etc.).</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-users-gear"></i>Employee Role</td><td class="url">/pages/management/accessrights</td><td><code class="kk">accessrights</code></td><td>Assign roles to employees (search, bulk-assign, multi-role) and remove access.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-heart-pulse"></i>HMOs</td><td class="url">/pages/management/hmo</td><td><code class="kk">hmo</code></td><td>Maintain HMO/health-benefit providers.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-calendar"></i>Holidays</td><td class="url">/pages/management/holidaylogger</td><td><code class="kk">holidaylogger</code></td><td>Log holidays used in payroll and attendance computations.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-layer-group"></i>Job Levels</td><td class="url">/pages/management/joblevels</td><td><code class="kk">joblevels</code></td><td>Maintain job-level definitions.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-list-check"></i>Leave Credit Allocation</td><td class="url">/pages/management/leavecreditallocations</td><td><code class="kk">leavecreditallocation</code></td><td>Allocate and adjust leave credits for employees.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-list-check"></i>Leave Types</td><td class="url">/pages/management/leavetypes</td><td><code class="kk">leavetypes</code></td><td>Maintain leave types and their rules.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-calendar-check"></i>Leave Validation</td><td class="url">/pages/management/leavevalidations</td><td><code class="kk">leavevalidations</code></td><td>Configure validation rules for leave filing.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-clock-rotate-left"></i>Lilo Validation</td><td class="url">/pages/management/lilovalidations</td><td><code class="kk">lilovalidations</code></td><td>Configure log-in/log-out (LILO) validation rules.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-paper-plane"></i>Mail Integration</td><td class="url">/pages/management/mailintegration</td><td><code class="kk">mailintegration</code></td><td>Configure the email provider (SMTP/Brevo, Mailgun, SES, Postmark) used to send automated payslips and other system email. Test before activating.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-map-check"></i>OB Valid.</td><td class="url">/pages/management/obvalidations</td><td><code class="kk">obvalidations</code></td><td>Configure official-business validation rules.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-wrench"></i>OT Maintenance</td><td class="url">/pages/management/otfiling</td><td><code class="kk">otfiling</code></td><td>Configure overtime-filing rules and settings.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-piggy-bank"></i>Pagibig Contri.</td><td class="url">/pages/management/pagibigcontribution</td><td><code class="kk">pagibigcontribution</code></td><td>Maintain Pag-IBIG contribution tables.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-users-between-lines"></i>Parental Set.</td><td class="url">/pages/management/parentalsetting</td><td><code class="kk">parentalsetting</code></td><td>Configure parental-leave and related settings.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-kit-medical"></i>Philhealth</td><td class="url">/pages/management/philhealth</td><td><code class="kk">philhealth</code></td><td>Maintain PhilHealth contribution tables.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-briefcase"></i>Positions</td><td class="url">/pages/management/positions</td><td><code class="kk">positions</code></td><td>Maintain job positions/titles.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-people-arrows"></i>Relationship</td><td class="url">/pages/management/relationship</td><td><code class="kk">relationship</code></td><td>Maintain relationship types for dependents/beneficiaries.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-clock"></i>Schedule Time</td><td class="url">/pages/management/time</td><td><code class="kk">scheduletime</code></td><td>Maintain shift/time templates used by the scheduler.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-calendar-days"></i>Scheduler</td><td class="url">/employee-schedules</td><td><code class="kk">employeeschedules</code></td><td>Assign and manage employee work schedules.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-hand-holding-medical"></i>SSS Contri.</td><td class="url">/pages/management/ssscontribution</td><td><code class="kk">ssscontribution</code></td><td>Maintain SSS contribution tables.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-shield-halved"></i>User Roles</td><td class="url">/user-roles</td><td><code class="kk">userroles</code></td><td>Create roles and assign the permissions each role grants.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- ── Reports ── --}}
            <section class="sc" id="reports">
                <div class="sc-head">
                    <div class="sc-icon"><i class="fa-solid fa-file-contract"></i></div>
                    <div>
                        <h5 class="sc-title">Reports <span class="badge-soft ms-1">Analysis</span></h5>
                        <p class="sc-sub">Read-only reports and exports</p>
                    </div>
                </div>
                <div class="sc-body">
                    <table class="doc-table">
                        <thead><tr><th>Menu Item</th><th>Opens</th><th>Permission</th><th>What it does</th></tr></thead>
                        <tbody>
                            <tr><td class="item-name"><i class="fa-solid fa-gift"></i>13th Month Pay</td><td class="url">/reports/thirteenth-month</td><td><code class="kk">thirteenthmonth</code></td><td>Compute and report 13th-month pay.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-chart-column"></i>Attendance Viewer</td><td class="url">/pages/reports/attendance</td><td><code class="kk">attendance</code></td><td>View and export attendance data by employee and date range.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-chart-column"></i>Employee Information</td><td class="url">/reports/employee-information</td><td><code class="kk">employeeinformation</code></td><td>Generate employee master-data reports.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-calendar-day"></i>Leave Report</td><td class="url">/reports/leave</td><td><code class="kk">leavereport</code></td><td>Summarize leave usage and balances.</td></tr>
                            <tr><td class="item-name"><i class="fa-solid fa-user-clock"></i>Overtime Report</td><td class="url">/reports/overtime</td><td><code class="kk">overtimereport</code></td><td>Summarize overtime by employee/period.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- ── How access works ── --}}
            <section class="sc" id="access">
                <div class="sc-head">
                    <div class="sc-icon"><i class="fa-solid fa-lock"></i></div>
                    <div>
                        <h5 class="sc-title">How Access Works</h5>
                        <p class="sc-sub">Roles, permissions and visibility</p>
                    </div>
                </div>
                <div class="sc-body prose">
                    <p>The system uses role-based access control. A <b>permission</b> is the right to use one screen (the keys shown in the tables above). A <b>role</b> is a named bundle of permissions. Employees are given one or more roles, and they inherit every permission those roles contain.</p>
                    <p>To set this up: open <b>Settings → User Roles</b> to create a role and tick the permissions it should include, then open <b>Settings → Employee Role</b> to assign that role to employees. On the Employee Role page you can search the list, select several employees at once, and apply multiple roles in a single save.</p>
                    <p>A sidebar item is hidden whenever the signed-in user lacks its permission, and a whole group heading disappears if none of its items are accessible. This means the menu naturally adapts to each user's responsibilities.</p>
                </div>
                <div class="note">
                    <b>Note for administrators:</b> new permission keys are created from the permission enums by running <code class="kk">php artisan app:create-permission</code>. After running it, assign the new permission to the appropriate roles. This Documentation page is intentionally visible to everyone with access to the Settings group and does not require a separate permission.
                </div>
            </section>
        </div>
    </div>
</div>

<script>
    // Smooth-scroll the in-page TOC
    document.querySelectorAll('.doc-toc a').forEach(a => {
        a.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });
</script>

@endsection

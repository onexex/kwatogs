@extends('layout.app')

@section('content')
<style>
    /* ── Design tokens (shared with Edit Employee / Leave / Overtime / Loans) ── */
    :root {
        --teal:         #008080;
        --teal-dark:    #006666;
        --teal-mid:     #4db6ac;
        --teal-light:   #e0f2f1;
        --slate:        #334155;
        --slate-light:  #64748b;
        --muted:        #94a3b8;
        --bg:           #f1f5f9;
        --surface:      #ffffff;
        --border:       #e2e8f0;
        --danger:       #ef4444;
        --success:      #10b981;
        --warning:      #f59e0b;
        --radius-card:  14px;
        --radius-input: 8px;
        --shadow-card:  0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    /* ── Page shell ──────────────────────────────────────────── */
    .e201-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Standard top header bar (uniform with other modules) ── */
    .e201-topbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 22px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .e201-topbar .page-title { font-size: 1.1rem; font-weight: 700; color: var(--slate); margin: 0; letter-spacing: -.2px; }
    .e201-topbar .page-sub { font-size: .78rem; color: var(--muted); margin: 2px 0 0; }

    /* ── Profile hero ────────────────────────────────────────── */
    .profile-hero {
        background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
        border-radius: var(--radius-card);
        color: #fff;
        border: none;
        box-shadow: var(--shadow-card);
    }
    .profile-img-container { width: 130px; height: 130px; border: 4px solid rgba(255,255,255,.25); object-fit: cover; }

    .btn-hero {
        background: rgba(255,255,255,.15);
        border: 1px solid rgba(255,255,255,.25);
        color: #fff;
        backdrop-filter: blur(4px);
    }
    .btn-hero:hover { background: rgba(255,255,255,.25); color: #fff; }

    /* ── Tabs ─────────────────────────────────────────────────── */
    .nav-resume { border: none; gap: 8px; }
    .nav-resume .nav-link {
        border: none;
        color: var(--slate-light);
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 10px 22px;
        border-radius: 50px;
        transition: all .15s;
    }
    .nav-resume .nav-link:hover { background-color: var(--teal-light); color: var(--teal-dark); }
    .nav-resume .nav-link.active { background-color: var(--teal) !important; color: #fff !important; }

    /* ── Section card ────────────────────────────────────────── */
    .sc {
        background: var(--surface);
        border-radius: var(--radius-card);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-card);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .sc-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 22px;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(to right, #fafcff, #f8fbfa);
    }
    .sc-head-left { display: flex; align-items: center; gap: 10px; }
    .sc-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        flex-shrink: 0;
    }
    .sc-title {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin: 0;
    }
    .sc-body { padding: 22px; }

    /* ── Sub-section divider ─────────────────────────────────── */
    .sub-divider {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 18px;
    }
    .sub-divider span {
        font-size: 0.73rem;
        font-weight: 700;
        color: var(--teal);
        text-transform: uppercase;
        letter-spacing: .4px;
        white-space: nowrap;
    }
    .sub-divider::after {
        content: '';
        flex-grow: 1;
        height: 1px;
        background: var(--border);
    }

    /* ── Field helpers ───────────────────────────────────────── */
    .field-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--teal);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 4px;
        display: block;
    }
    .field-value {
        font-size: 0.95rem;
        color: var(--slate);
        font-weight: 500;
    }

    /* ── Highlight tiles (Compensation / Compliance) ─────────── */
    .tile {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: var(--radius-input);
        padding: 14px 16px;
    }
    .tile-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    /* ── Education entry ─────────────────────────────────────── */
    .educ-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    /* ── Self-service hub: at-a-glance stats ─────────────────── */
    .hub-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    .hub-stat {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 15px 18px;
        display: flex;
        align-items: center;
        gap: 13px;
    }
    .hub-stat .hs-ic {
        width: 42px; height: 42px;
        border-radius: 11px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .hub-stat .num { font-size: 1.35rem; font-weight: 800; color: var(--slate); line-height: 1; }
    .hub-stat .lbl {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .4px; color: var(--muted); margin-top: 3px;
    }

    /* ── Self-service hub: quick actions ─────────────────────── */
    .qa-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 14px;
    }
    .qa-card {
        display: flex; align-items: center; gap: 13px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        padding: 15px 17px;
        text-decoration: none;
        transition: transform .15s, box-shadow .15s, border-color .15s;
        position: relative;
    }
    .qa-card:hover {
        transform: translateY(-2px);
        border-color: var(--teal-mid);
        box-shadow: 0 6px 18px rgba(0,128,128,.12);
    }
    .qa-ic {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem; flex-shrink: 0;
    }
    .qa-tt  { font-size: .9rem;  font-weight: 700; color: var(--slate); margin: 0; line-height: 1.15; }
    .qa-sub { font-size: .72rem; color: var(--muted); margin: 2px 0 0; }
    .qa-badge { position: absolute; top: 11px; right: 13px; }

    /* ── Self-service hub: "Ask about your record" (no AI, keyword match) ── */
    .ask-input-row { display: flex; gap: 10px; }
    .ask-input {
        flex: 1;
        border: 1px solid var(--border);
        border-radius: var(--radius-input);
        padding: 11px 14px;
        font-size: .9rem;
        color: var(--slate);
    }
    .ask-input:focus { outline: none; border-color: var(--teal-mid); box-shadow: 0 0 0 3px var(--teal-light); }
    .ask-btn {
        background: var(--teal); color: #fff; border: none;
        border-radius: var(--radius-input); padding: 0 20px;
        font-weight: 700; font-size: .85rem;
    }
    .ask-btn:hover { background: var(--teal-dark); }
    .ask-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
    .ask-chip {
        background: var(--bg); border: 1px solid var(--border);
        border-radius: 50px; padding: 6px 14px;
        font-size: .75rem; color: var(--slate); cursor: pointer;
        transition: all .12s;
    }
    .ask-chip:hover { background: var(--teal-light); border-color: var(--teal-mid); color: var(--teal-dark); }
    .ask-log { margin-top: 4px; }
    .ask-q { display: flex; justify-content: flex-end; margin: 16px 0 8px; }
    .ask-q span {
        background: var(--teal); color: #fff; padding: 8px 14px;
        border-radius: 14px 14px 2px 14px; font-size: .85rem; max-width: 80%;
    }
    .ask-a { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 4px; }
    .ask-a .a-ic {
        width: 30px; height: 30px; border-radius: 8px;
        background: var(--teal-light); color: var(--teal);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: .8rem;
    }
    .ask-a .a-body {
        background: var(--bg); border: 1px solid var(--border);
        padding: 10px 14px; border-radius: 2px 14px 14px 14px;
        font-size: .88rem; color: var(--slate); max-width: 85%;
    }
    .ask-a .a-body a { color: var(--teal); font-weight: 600; }
    .ask-a .a-body b { color: var(--slate); }
</style>

<div class="e201-shell">

    {{-- Top header (uniform with other modules) --}}
    <div class="e201-topbar">
        <div>
            <p class="page-title">Employee 201 File</p>
            <p class="page-sub">Personal, education, employment and compliance records</p>
        </div>
    </div>

    @include('partials.sanitary_card_banner', ['emp' => $emp])

    {{-- Hero Section --}}
    <div class="card profile-hero mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-2 text-center mb-3 mb-lg-0">
                    @if($emp->empPicPath && file_exists(public_path('img/profile/' . $emp->empPicPath)))
                        <img src="{{ asset('img/profile/' . $emp->empPicPath) }}"
                             alt="profile" class="rounded-circle profile-img-container shadow-lg">
                    @else
                        @php $gender = $user->employeeInformation->gender ?? null; @endphp
                        <div class="rounded-circle profile-img-container shadow-lg d-flex align-items-center justify-content-center"
                             style="background:#f1f5f9;">
                            <i class="fa-solid fa-circle-user"
                               style="font-size:4rem;color:{{ $gender == 2 ? '#e91e8c' : '#1976d2' }};"></i>
                        </div>
                    @endif
                </div>
                <div class="col-lg-10 text-center text-lg-start">
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start mb-2 flex-wrap gap-2">
                        <h1 class="fw-bold mb-0 me-1 text-capitalize">
                            {{ $user->fname ?? 'Select' }} {{ $user->mname ?? '' }} {{ $user->lname ?? 'Employee' }} {{ $user->suffix ?? '' }}
                        </h1>
                        @if(isset($user->status))
                            <span class="badge {{ $user->status == 1 ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                {{ $user->status == 1 ? 'ACTIVE' : 'RESIGNED' }}
                            </span>
                        @endif
                    </div>
                    <p class="fs-5 opacity-75 mb-3">{{ $emp->position->pos_desc ?? 'Position' }} <span class="mx-2">|</span> {{ $emp->department->dep_name ?? 'Department' }}</p>
                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-2">
                        <span class="badge rounded-pill px-3 py-2" style="background:#ffffff; color: var(--teal-dark); font-weight:600;"><i class="fa-solid fa-id-card me-1"></i> {{ $emp->empID ?? '---' }}</span>
                        <span class="badge rounded-pill px-3 py-2" style="background:#ffffff; color: var(--teal-dark); font-weight:600;"><i class="fa-solid fa-envelope me-1"></i> {{ $user->email ?? '---' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Self-Service Hub ──────────────────────────────────────
         Turns this 201 file into a personal workspace: an at-a-glance
         strip plus quick actions to the things the logged-in employee
         can do. Each action is gated by the same permission as its
         sidebar item, so a link only shows when the employee can reach
         it (My Notices / My COE are permission-less, always shown). --}}
    @php
        // Own unread active notices — mirrors the layout's badge computation.
        $myUnreadNotices = 0;
        if ($user->empID) {
            $myUnreadNotices = \App\Models\Notice::where('employee_id', $user->empID)
                ->where('status', 'active')->where('is_read', false)->count();
        }
        // Continuous years of service from the hire date (0 if not set).
        $yearsOfService = $emp && $emp->empDateHired
            ? round(\Carbon\Carbon::parse($emp->empDateHired)->floatDiffInYears(now()), 1)
            : null;
        $myDocCount = ($user->employmentDocuments ?? collect())->count();
    @endphp

    <div class="hub-stats">
        <div class="hub-stat">
            <div class="hs-ic"><i class="fa-solid fa-hourglass-half"></i></div>
            <div>
                <div class="num">{{ $yearsOfService !== null ? $yearsOfService : '--' }}<span style="font-size:.8rem;font-weight:700;color:var(--muted);"> yrs</span></div>
                <div class="lbl">Years of Service</div>
            </div>
        </div>
        <div class="hub-stat">
            <div class="hs-ic"><i class="fa-solid fa-user-check"></i></div>
            <div>
                <div class="num" style="font-size:1.05rem;">{{ ($user->status ?? 0) == 1 ? 'Active' : 'Inactive' }}</div>
                <div class="lbl">Employment Status</div>
            </div>
        </div>
        <div class="hub-stat">
            <div class="hs-ic"><i class="fa-solid fa-bell"></i></div>
            <div>
                <div class="num">{{ $myUnreadNotices }}</div>
                <div class="lbl">Unread Notices</div>
            </div>
        </div>
        @can('viewe201files')
        <div class="hub-stat">
            <div class="hs-ic"><i class="fa-solid fa-folder-open"></i></div>
            <div>
                <div class="num">{{ $myDocCount }}</div>
                <div class="lbl">My Documents</div>
            </div>
        </div>
        @endcan
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-bolt"></i></div>
                <h5 class="sc-title">Quick Actions</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="qa-grid">
                {{-- Permission-less self-service (always visible) --}}
                <a class="qa-card" href="{{ route('notices.mine') }}">
                    <div class="qa-ic"><i class="fa-solid fa-bell"></i></div>
                    <div>
                        <p class="qa-tt">My Notices</p>
                        <p class="qa-sub">Memos &amp; disciplinary notices</p>
                    </div>
                    @if($myUnreadNotices > 0)
                        <span class="qa-badge badge rounded-pill bg-danger">{{ $myUnreadNotices > 99 ? '99+' : $myUnreadNotices }}</span>
                    @endif
                </a>
                <a class="qa-card" href="{{ route('coe.mine') }}">
                    <div class="qa-ic"><i class="fa-solid fa-file-signature"></i></div>
                    <div>
                        <p class="qa-tt">My COE</p>
                        <p class="qa-sub">Request a Certificate of Employment</p>
                    </div>
                </a>

                {{-- Gated by the same permission as the sidebar item --}}
                @can('leaveapplication')
                <a class="qa-card" href="/pages/modules/leaveApplication">
                    <div class="qa-ic"><i class="fa-solid fa-calendar-day"></i></div>
                    <div>
                        <p class="qa-tt">Leave Application</p>
                        <p class="qa-sub">File and track your leave</p>
                    </div>
                </a>
                @endcan
                @can('overtime')
                <a class="qa-card" href="/pages/modules/overtime">
                    <div class="qa-ic"><i class="fa-solid fa-user-clock"></i></div>
                    <div>
                        <p class="qa-tt">Overtime</p>
                        <p class="qa-sub">File an overtime request</p>
                    </div>
                </a>
                @endcan
                @can('obttracker')
                <a class="qa-card" href="/pages/modules/obtTracker">
                    <div class="qa-ic"><i class="fa-solid fa-map-location-dot"></i></div>
                    <div>
                        <p class="qa-tt">OB Tracker</p>
                        <p class="qa-sub">Log official business</p>
                    </div>
                </a>
                @endcan
                @can('earlyout')
                <a class="qa-card" href="/pages/modules/earlyout">
                    <div class="qa-ic"><i class="fa-solid fa-door-open"></i></div>
                    <div>
                        <p class="qa-tt">Early Out</p>
                        <p class="qa-sub">File an early-out request</p>
                    </div>
                </a>
                @endcan
                @can('kuboaccess')
                <a class="qa-card" href="{{ route('kubo.feed') }}">
                    <div class="qa-ic"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <p class="qa-tt">KwHub</p>
                        <p class="qa-sub">Company community feed</p>
                    </div>
                </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- ── Ask about your record (deterministic, NO AI) ──────────
         A keyword-matched Q&A over the employee's OWN 201 data. All
         facts are their own record (already shown in the tabs below),
         serialized once into JS; matching + answering happen entirely
         client-side — nothing is sent anywhere, no model is called. --}}
    @php
        $info = $user->employeeInformation ?? null;
        $addr = collect([
            $info->empAddStreet ?? null,
            isset($info->empAddBrgyDesc) ? 'Brgy. '.$info->empAddBrgyDesc : null,
            $info->empAddCityDesc ?? null,
            $info->empProvDesc ?? null,
            $info->empZipcode ?? null,
        ])->filter()->implode(', ');

        $askFacts = [
            'name'        => trim(($user->fname ?? '').' '.($user->mname ?? '').' '.($user->lname ?? '').' '.($user->suffix ?? '')),
            'empID'       => $emp->empID ?? null,
            'email'       => $user->email ?? null,
            'position'    => $emp->position->pos_desc ?? null,
            'department'  => $emp->department->dep_name ?? null,
            'company'     => $emp->company->comp_name ?? null,
            'status'      => ($user->status ?? 0) == 1 ? 'Active' : 'Inactive',
            'hired'       => ($emp && $emp->empDateHired) ? date('F d, Y', strtotime($emp->empDateHired)) : null,
            'years'       => $yearsOfService,
            'birthdate'   => (isset($info->empBdate) && $info->empBdate) ? date('F d, Y', strtotime($info->empBdate)) : null,
            'gender'      => isset($info->gender) ? ($info->gender == 1 ? 'Male' : 'Female') : null,
            'contact'     => trim(($info->empPContact ?? '').(isset($info->empHContact) ? ' / '.$info->empHContact : '')) ?: null,
            'address'     => $addr ?: null,
            'sss'         => $emp->empSSS ?? null,
            'philhealth'  => $emp->empPhilhealth ?? null,
            'pagibig'     => $emp->empPagibig ?? null,
            'tin'         => $emp->empTIN ?? null,
            'umid'        => $emp->empUMID ?? null,
            'passport'    => $emp->empPassport ?? null,
            'basic'       => isset($emp->empBasic) ? number_format($emp->empBasic, 2) : null,
            'allowance'   => isset($emp->empAllowance) ? number_format($emp->empAllowance, 2) : null,
            'hourly'      => isset($emp->empHrate) ? number_format($emp->empHrate, 2) : null,
            'payrollType' => $emp->empPayrollType ?? null,
            'unread'      => $myUnreadNotices,
        ];
        // Which self-service destinations this employee can actually reach —
        // "how do I…" answers only link where the permission allows.
        $askCan = [
            'leave'    => auth()->user()?->can('leaveapplication') ?? false,
            'overtime' => auth()->user()?->can('overtime') ?? false,
            'ob'       => auth()->user()?->can('obttracker') ?? false,
            'earlyout' => auth()->user()?->can('earlyout') ?? false,
        ];
    @endphp

    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-circle-question"></i></div>
                <h5 class="sc-title">Ask About Your Record</h5>
            </div>
            <span class="text-muted" style="font-size:.68rem;">Answers come from your own file — nothing is sent anywhere.</span>
        </div>
        <div class="sc-body">
            <form id="askForm" class="ask-input-row" autocomplete="off">
                <input type="text" id="askInput" class="ask-input" placeholder="e.g. How many years have I worked? What's my SSS number?">
                <button type="submit" class="ask-btn"><i class="fa-solid fa-paper-plane me-1"></i> Ask</button>
            </form>
            <div class="ask-chips" id="askChips">
                <span class="ask-chip">How many years have I worked?</span>
                <span class="ask-chip">What is my SSS number?</span>
                <span class="ask-chip">When was I hired?</span>
                <span class="ask-chip">What is my position?</span>
                <span class="ask-chip">How do I request leave?</span>
                <span class="ask-chip">How do I get a COE?</span>
            </div>
            <div class="ask-log" id="askLog"></div>
        </div>
    </div>

    <script>
        (function () {
            const F = @json($askFacts);
            const CAN = @json($askCan);
            const COE_URL = "{{ route('coe.mine') }}";
            const NOTICES_URL = "{{ route('notices.mine') }}";
            const NA = "That isn't on your file — please contact HR to have it updated.";

            // Each intent: keyword groups (ALL words in a group must appear) → answer.
            // First matching intent wins; order = priority.
            const has = (t, ...ws) => ws.every(w => t.includes(w));
            const val = (v, label) => v ? `Your <b>${label}</b> is <b>${v}</b>.` : NA;

            const INTENTS = [
                { m: t => has(t,'how') && (t.includes('leave')||t.includes('vacation')||t.includes('sick')) && !t.includes('credit'),
                  a: () => CAN.leave
                        ? `To file leave, open <a href="/pages/modules/leaveApplication">Leave Application</a> (also in Quick Actions above), pick the type and dates, and submit for approval.`
                        : `Leave requests are filed through your supervisor/HR. Please coordinate with them to file your leave.` },
                { m: t => has(t,'how') && (t.includes('overtime')||t.includes(' ot')),
                  a: () => CAN.overtime
                        ? `To file overtime, open <a href="/pages/modules/overtime">Overtime</a> in Quick Actions, then submit your OT request for approval.`
                        : `Overtime is filed via HR/your supervisor. Please coordinate with them.` },
                { m: t => has(t,'how') && (t.includes('coe')||has(t,'certificate','employment')),
                  a: () => `Request a Certificate of Employment from <a href="${COE_URL}">My COE</a> in Quick Actions. Once HR approves it, a Download button appears there.` },
                { m: t => has(t,'how') && t.includes('password'),
                  a: () => `Use <b>Password Settings</b> under your profile menu (top-right avatar) to change your password.` },
                { m: t => has(t,'how') && (t.includes('official business')||t.includes(' ob')),
                  a: () => CAN.ob ? `Log official business in <a href="/pages/modules/obtTracker">OB Tracker</a> (Quick Actions).` : `Official business is logged via HR. Please coordinate with them.` },
                { m: t => (t.includes('year')||t.includes('tenure')||t.includes('long')) && (t.includes('work')||t.includes('service')||t.includes('been')||t.includes('tenure')),
                  a: () => F.years != null ? `You have <b>${F.years} year(s)</b> of service${F.hired ? `, since <b>${F.hired}</b>` : ''}.` : NA },
                { m: t => t.includes('hire') || (t.includes('start') && t.includes('work')) || has(t,'when','join'),
                  a: () => val(F.hired, 'date hired') },
                { m: t => t.includes('sss'),        a: () => val(F.sss, 'SSS number') },
                { m: t => t.includes('philhealth'), a: () => val(F.philhealth, 'PhilHealth number') },
                { m: t => t.includes('pag') || t.includes('hdmf'), a: () => val(F.pagibig, 'Pag-IBIG number') },
                { m: t => t.includes('tin') || has(t,'tax','number'), a: () => val(F.tin, 'TIN') },
                { m: t => t.includes('umid'),       a: () => val(F.umid, 'UMID') },
                { m: t => t.includes('passport'),   a: () => val(F.passport, 'passport number') },
                { m: t => t.includes('salary') || t.includes('basic') || t.includes('pay') || t.includes('rate') || t.includes('allowance'),
                  a: () => {
                        const parts = [];
                        if (F.basic)     parts.push(`Basic salary: <b>₱${F.basic}</b>`);
                        if (F.allowance) parts.push(`Allowance: <b>₱${F.allowance}</b>`);
                        if (F.hourly)    parts.push(`Hourly rate: <b>₱${F.hourly}</b>`);
                        return parts.length ? parts.join('<br>') : NA;
                  } },
                { m: t => t.includes('position') || t.includes('title') || t.includes('role') || t.includes('job'),
                  a: () => val(F.position, 'position') },
                { m: t => t.includes('department') || t.includes('dept'), a: () => val(F.department, 'department') },
                { m: t => t.includes('company'),    a: () => val(F.company, 'company') },
                { m: t => t.includes('birth') || t.includes('bday') || t.includes('birthday'), a: () => val(F.birthdate, 'birth date') },
                { m: t => t.includes('address') || t.includes('live'), a: () => val(F.address, 'address') },
                { m: t => t.includes('contact') || t.includes('phone') || t.includes('mobile') || t.includes('number') && t.includes('my'),
                  a: () => val(F.contact, 'contact number') },
                { m: t => t.includes('email'),      a: () => val(F.email, 'email') },
                { m: t => (t.includes('employee') && t.includes('id')) || t.includes('emp id') || t.includes('id number'),
                  a: () => val(F.empID, 'employee ID') },
                { m: t => t.includes('status') || t.includes('active') || t.includes('regular'),
                  a: () => `Your employment status is <b>${F.status}</b>.` },
                { m: t => t.includes('notice') || t.includes('memo'),
                  a: () => `You have <b>${F.unread}</b> unread notice(s). Open <a href="${NOTICES_URL}">My Notices</a> to read them.` },
            ];

            const FALLBACK = `I can answer questions about <b>your own record</b> — years of service, date hired, SSS/PhilHealth/Pag-IBIG/TIN, position, department, salary, contact info, status — and how to request leave, overtime, or a COE. Try one of the suggestions above.`;

            const log = document.getElementById('askLog');
            function esc(s){ const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
            function answer(q) {
                const t = q.toLowerCase().trim();
                if (!t) return;
                let ansHtml = FALLBACK;
                for (const it of INTENTS) { if (it.m(t)) { ansHtml = it.a(); break; } }
                log.insertAdjacentHTML('afterbegin',
                    `<div class="ask-q"><span>${esc(q)}</span></div>` +
                    `<div class="ask-a"><div class="a-ic"><i class="fa-solid fa-circle-info"></i></div><div class="a-body">${ansHtml}</div></div>`);
            }

            document.getElementById('askForm').addEventListener('submit', function (e) {
                e.preventDefault();
                const inp = document.getElementById('askInput');
                answer(inp.value);
                inp.value = '';
            });
            document.getElementById('askChips').addEventListener('click', function (e) {
                if (e.target.classList.contains('ask-chip')) {
                    document.getElementById('askInput').value = e.target.textContent;
                    answer(e.target.textContent);
                }
            });
        })();
    </script>

    {{-- Tabs --}}
    <ul class="nav nav-resume mb-4" id="resumeTab" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#personal">Personal Info</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#education">Education</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#employment">Employment</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#compliance">Compliance</button></li>
        @can('viewe201files')
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#documents">Documents</button></li>
        @endcan
    </ul>

    <div class="tab-content">
        {{-- Personal Tab --}}
        <div class="tab-pane fade show active" id="personal" role="tabpanel">
            <div class="sc">
                <div class="sc-head">
                    <div class="sc-head-left">
                        <div class="sc-icon"><i class="fa-solid fa-user"></i></div>
                        <h5 class="sc-title">General Details</h5>
                    </div>
                </div>
                <div class="sc-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <span class="field-label">Gender / Citizenship</span>
                            <div class="field-value">
                                {{ isset($user->employeeInformation?->gender) ? ($user->employeeInformation->gender == 1 ? 'Male' : 'Female') : '---' }} / {{ $user->employeeInformation->citizenship ?? '---' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <span class="field-label">Birth Date</span>
                            <div class="field-value">{{ isset($user->employeeInformation->empBdate) ? date('M d, Y', strtotime($user->employeeInformation->empBdate)) : '---' }}</div>
                        </div>
                        <div class="col-md-4">
                            <span class="field-label">Contact Numbers</span>
                            <div class="field-value">{{ $user->employeeInformation->empPContact ?? '---' }} {{ isset($user->employeeInformation->empHContact) ? '/ '.$user->employeeInformation->empHContact : '' }}</div>
                        </div>
                        <div class="col-md-12">
                            <span class="field-label">Mailing Address</span>
                            <div class="field-value">
                                {{ $user->employeeInformation->empAddStreet ?? '---' }}, Brgy. {{ $user->employeeInformation->empAddBrgyDesc ?? '---' }}, {{ $user->employeeInformation->empAddCityDesc ?? '---' }}, {{ $user->employeeInformation->empProvDesc ?? '---' }}, {{ $user->employeeInformation->empZipcode ?? '' }}, {{ $user->employeeInformation->empCountry ?? '' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Education Tab --}}
        <div class="tab-pane fade" id="education" role="tabpanel">
            <div class="sc">
                <div class="sc-head">
                    <div class="sc-head-left">
                        <div class="sc-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                        <h5 class="sc-title">Educational Background</h5>
                    </div>
                </div>
                <div class="sc-body">
                    <div class="row g-4">
                        @foreach ($user->education as $education)
                            <div class="col-md-12 d-flex align-items-center gap-3">
                                <div class="educ-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                                <div>
                                    <span class="field-label">{{ $education->schoolLevel }} Education</span>
                                    <div class="field-value fw-bold text-capitalize">{{ $education->schoolName ?? 'Not Specified' }}</div>
                                    <div class="small text-muted text-capitalize">{{ $education->schoolYearStarted ?? '' }} - {{ $education->schoolYearEnded ?? '' }} | {{ $education->schoolAddress ?? '' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Employment Tab --}}
        <div class="tab-pane fade" id="employment" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="sc h-100">
                        <div class="sc-head">
                            <div class="sc-head-left">
                                <div class="sc-icon"><i class="fa-solid fa-coins"></i></div>
                                <h5 class="sc-title">Compensation</h5>
                            </div>
                        </div>
                        <div class="sc-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid var(--border);">
                                <span class="field-label mb-0">Basic Salary</span>
                                <span class="field-value fw-bold">₱ {{ number_format($emp->empBasic ?? 0, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid var(--border);">
                                <span class="field-label mb-0">Allowance</span>
                                <span class="field-value fw-bold">₱ {{ number_format($emp->empAllowance ?? 0, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid var(--border);">
                                <span class="field-label mb-0">Hourly Rate</span>
                                <span class="field-value fw-bold">₱ {{ number_format($emp->empHrate ?? 0, 2) }}</span>
                            </div>
                            @php $__ptype = strtoupper($emp->empPayrollType ?? 'CASH'); @endphp
                            <div class="d-flex justify-content-between align-items-center @if($__ptype === 'CARD') mb-3 pb-2 @endif" @if($__ptype === 'CARD') style="border-bottom: 1px solid var(--border);" @endif>
                                <span class="field-label mb-0">Payroll Type</span>
                                <span class="field-value fw-bold">
                                    <span class="badge {{ $__ptype === 'CARD' ? 'bg-info' : 'bg-secondary' }}">{{ ucfirst(strtolower($__ptype)) }}</span>
                                </span>
                            </div>
                            @if($__ptype === 'CARD')
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="field-label mb-0">Card / Account No.</span>
                                <span class="field-value fw-bold">{{ $emp->empCardNo ?: '---' }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="sc h-100">
                        <div class="sc-head">
                            <div class="sc-head-left">
                                <div class="sc-icon"><i class="fa-solid fa-briefcase"></i></div>
                                <h5 class="sc-title">Employment Details</h5>
                            </div>
                        </div>
                        <div class="sc-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <span class="field-label">Company / Agency</span>
                                    <div class="field-value">{{ $emp->company->comp_name ?? '---' }} / {{ $emp->agency->ag_name ?? 'Direct' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <span class="field-label">Date Hired</span>
                                    <div class="field-value fw-bold" style="color: var(--teal);">{{ isset($emp->empDateHired) ? date('M d, Y', strtotime($emp->empDateHired)) : '---' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Compliance Tab --}}
        <div class="tab-pane fade" id="compliance" role="tabpanel">
            <div class="sc">
                <div class="sc-head">
                    <div class="sc-head-left">
                        <div class="sc-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <h5 class="sc-title">Government & Compliance</h5>
                    </div>
                </div>
                <div class="sc-body">
                    <div class="row g-4">
                        @php
                            $governmentFields = [
                                ['label' => 'SSS Number', 'val' => $emp->empSSS ?? null, 'icon' => 'fa-shield-halved'],
                                ['label' => 'PhilHealth', 'val' => $emp->empPhilhealth ?? null, 'icon' => 'fa-kit-medical'],
                                ['label' => 'Pag-IBIG', 'val' => $emp->empPagibig ?? null, 'icon' => 'fa-house-chimney-user'],
                                ['label' => 'TIN', 'val' => $emp->empTIN ?? null, 'icon' => 'fa-file-invoice'],
                                ['label' => 'UMID', 'val' => $emp->empUMID ?? null, 'icon' => 'fa-address-card'],
                                ['label' => 'Passport', 'val' => $emp->empPassport ?? null, 'icon' => 'fa-passport'],
                                ['label' => 'Health Sanitary Card', 'val' => $emp->empSanitaryCardNo ?? null, 'icon' => 'fa-notes-medical'],
                            ];
                            // Sanitary-card expiry status (drives the tile colour + the top-of-page banner).
                            $scExp     = $emp->empSanitaryCardExpDate ? \Carbon\Carbon::parse($emp->empSanitaryCardExpDate) : null;
                            $scExpired = $scExp && $scExp->lt(\Carbon\Carbon::today());
                        @endphp
                        @foreach($governmentFields as $item)
                            <div class="col-md-4">
                                <div class="tile d-flex align-items-center gap-3">
                                    <div class="tile-icon"><i class="fa-solid {{ $item['icon'] }}"></i></div>
                                    <div>
                                        <span class="field-label">{{ $item['label'] }}</span>
                                        <div class="field-value fw-bold">{{ $item['val'] ?? 'Not Provided' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="col-md-4">
                            <div class="tile d-flex align-items-center gap-3">
                                <div class="tile-icon"><i class="fa-solid fa-calendar-xmark"></i></div>
                                <div>
                                    <span class="field-label">Sanitary Card Expiry</span>
                                    <div class="field-value fw-bold {{ $scExpired ? 'text-danger' : '' }}">
                                        {{ $scExp ? $scExp->format('M d, Y') : 'Not Provided' }}
                                        @if($scExpired)<span class="badge bg-danger ms-1">Expired</span>@endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @can('viewe201files')
        {{-- Documents Tab — read-only view of the employee's own 201-file documents --}}
        <div class="tab-pane fade" id="documents" role="tabpanel">
            <div class="sc">
                <div class="sc-head">
                    <div class="sc-head-left">
                        <div class="sc-icon"><i class="fa-solid fa-folder-open"></i></div>
                        <h5 class="sc-title">My Documents</h5>
                    </div>
                </div>
                <div class="sc-body">
                    @php
                        $myDocs = $user->employmentDocuments ?? collect();
                        $clMap  = collect(\App\Services\OffboardingClearanceService::ITEMS)->mapWithKeys(fn ($v, $k) => [$k => $v['label']]);
                    @endphp
                    @if($myDocs->count())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="text-muted small">
                                    <tr>
                                        <th>TYPE</th>
                                        <th>LABEL / FILE</th>
                                        <th>UPLOADED</th>
                                        <th class="text-end">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($myDocs as $doc)
                                        <tr>
                                            <td>
                                                <span class="badge" style="background:var(--teal-light); color:var(--teal-dark);">{{ $doc->doc_type ?: 'Other' }}</span>
                                                @if($doc->clearance_key && $clMap->has($doc->clearance_key))
                                                    <div class="text-muted" style="font-size:.68rem;">{{ $clMap[$doc->clearance_key] }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="field-value">{{ $doc->label ?: $doc->original_name }}</div>
                                                <div class="text-muted" style="font-size:.72rem;">{{ $doc->original_name }}</div>
                                            </td>
                                            <td class="text-muted small">{{ $doc->created_at ? $doc->created_at->format('M d, Y') : '' }}</td>
                                            <td class="text-end">
                                                <a href="/admin/e201/document/{{ $doc->id }}/download" class="btn btn-sm btn-light border" title="Download">
                                                    <i class="fa-solid fa-download" style="color:var(--teal);"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-folder-open fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0 small">No documents on file yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endcan
    </div>
</div>
@endsection

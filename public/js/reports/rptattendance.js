document.addEventListener('DOMContentLoaded', function () {

    // Refresh button
    document.getElementById('btn_rptrefresh').addEventListener('click', fetchAttendance);

    // Print button - branded report (matches 13th-month / payroll print style)
    document.getElementById('btn_rptprint').addEventListener('click', () => {
        const tableEl = document.querySelector('#Report_thisPrint table');
        if (!tableEl) return;
        const tableHtml = tableEl.outerHTML;

        const fromDate = document.getElementById('txtDateFrom').value;
        const toDate   = document.getElementById('txtDateTo').value;
        const empSel   = document.getElementById('txtLastname');
        const empText  = (empSel && empSel.options[empSel.selectedIndex])
            ? empSel.options[empSel.selectedIndex].text : 'All Personnel';

        const logoUrl  = window.payrollLogoUrl || (window.location.origin + '/img/kwatogslogo.jpg');
        const genOn    = new Date().toLocaleDateString('en-US', {
            month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        const w = window.open('', '_blank', 'width=1100,height=800');
        w.document.write(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Attendance Report</title>
                <style>
                    @page { size: landscape; margin: 12mm; }
                    * { box-sizing:border-box; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
                    body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:0; font-size:11px; }
                    .head { display:flex; align-items:center; gap:14px; border-bottom:3px solid #008080; padding-bottom:12px; margin-bottom:6px; }
                    .head img { height:54px; width:auto; }
                    .head .org { font-size:18px; font-weight:800; color:#006666; letter-spacing:.3px; }
                    .head .sub { font-size:12px; color:#475569; margin-top:1px; }
                    .meta { font-size:11px; color:#64748b; margin:10px 0 14px; }
                    .meta b { color:#334155; }
                    table { width:100%; border-collapse:collapse; }
                    thead th { background:#008080 !important; color:#fff !important; font-size:10px; text-transform:uppercase;
                        letter-spacing:.3px; padding:7px 6px; text-align:center; border:none; }
                    thead th:nth-child(2) { text-align:left; }
                    tbody td { padding:5px 6px; border-bottom:1px solid #e2e8f0; font-size:10.5px; text-align:center; vertical-align:middle; }
                    tbody td:nth-child(2) { text-align:left; text-transform:capitalize; }
                    tbody tr:nth-child(even) td { background:#f8fafc; }
                    tbody tr.fw-bold td, tbody tr[style*="background"] td { background:#e0f2f1 !important; font-weight:700; color:#006666; }
                    .text-primary, .text-success { color:#006666 !important; }
                    .text-danger { color:#b91c1c !important; }
                    .note { margin-top:14px; font-size:9.5px; color:#94a3b8; font-style:italic; }
                    .sign { margin-top:42px; display:flex; justify-content:space-between; font-size:11px; }
                    .sign div { width:30%; text-align:center; }
                    .sign .ln { border-top:1px solid #475569; margin-bottom:4px; padding-top:4px; }
                    .endmark { margin-top:18px; text-align:center; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; }
                </style>
            </head>
            <body>
                <div class="head">
                    <img src="${logoUrl}" onerror="this.style.display='none'" alt="">
                    <div>
                        <div class="org">KWATOGS LOMI HOUSE</div>
                        <div class="sub">Attendance Report</div>
                    </div>
                </div>

                <div class="meta">
                    <b>Date Range:</b> ${fromDate} &nbsp;to&nbsp; ${toDate}
                    &nbsp;&bull;&nbsp; <b>Employee:</b> ${empText}
                    &nbsp;&bull;&nbsp; <b>Generated:</b> ${genOn}
                </div>

                ${tableHtml}

                <div class="note">
                    Durations are computed from time-in/out against each employee's assigned schedule. Deductions reflect late, undertime,
                    over-break and out-pass minutes. Net duration = gross duration less manual deductions.
                </div>

                <div class="sign">
                    <div><div class="ln"></div>Prepared by</div>
                    <div><div class="ln"></div>Checked &amp; Verified by</div>
                    <div><div class="ln"></div>Approved by</div>
                </div>

                <div class="endmark">*** End of Report ***</div>

                <script>
                    window.onload = function () {
                        setTimeout(function () { window.focus(); window.print(); window.close(); }, 400);
                    };
                <\/script>
            </body>
            </html>
        `);
        w.document.close();
    });

    const btnRefresh = $("#btn_rptrefresh");
    const empSelect = $("#txtLastname");
    const deptSelect = $("#txtDept");
    const dateFrom = $("#txtDateFrom");
    const dateTo = $("#txtDateTo");
    const tableBody = $("#tbl_rptattendance");

    axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
    axios.defaults.headers.common["X-CSRF-TOKEN"] = $('meta[name="csrf-token"]').attr("content");

    btnRefresh.on("click", function () {
        fetchAttendance();
    });

    function fetchAttendance() {
        const empID = empSelect.val();
        const from = dateFrom.val();
        const to = dateTo.val();
        const department = deptSelect.val();

        // Updated colspan to 13 to match the new columns
        tableBody.html(`<tr><td colspan="14" class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>`);

        axios.post("/attendance/fetch", {
            empID: empID,
            dateFrom: from,
            dateTo: to,
            department: department,
        })
        .then(response => {
            const res = response.data;
            if (res.status === "success") {
                renderTable(res.data);
            } else {
                tableBody.html(`<tr><td colspan="14" class="text-center text-danger">No records found</td></tr>`);
            }
        })
        .catch(error => {
            console.error(error);
            tableBody.html(`<tr><td colspan="14" class="text-center text-danger">Error fetching data</td></tr>`);
        });
    }

    function renderTable(data) {
        if (!data.length) {
            tableBody.html(`<tr><td colspan="14" class="text-center">No records found</td></tr>`);
            return;
        }

        const capitalizeName = (name) => name.toLowerCase().replace(/\b\w/g, s => s.toUpperCase());
        const m = (n) => `${Number(n || 0)}m`;

        // Group rows per employee so each employee gets its own TOTAL row
        const groups = new Map();
        data.forEach(item => {
            const key = item.employee ? (item.employee.empID ?? `${item.employee.lname}, ${item.employee.fname}`) : 'N/A';
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key).push(item);
        });

        let rows = "";
        let no = 0;
        const grand = { gross:0, ded:0, net:0, late:0, ut:0, nd:0, pass:0, ob:0 };

        groups.forEach(items => {
            const sub = { gross:0, ded:0, net:0, late:0, ut:0, nd:0, pass:0, ob:0 };
            let empName = 'N/A';

            items.forEach(item => {
                empName = item.employee ? `${item.employee.lname}, ${item.employee.fname}` : 'N/A';

                let totalDeductedMins = 0;
                if (item.manual_deductions && item.manual_deductions.length > 0) {
                    totalDeductedMins = item.manual_deductions.reduce((sum, d) => sum + parseInt(d.deduction_minutes || 0), 0);
                }
                const grossHours = parseFloat(item.total_hours ?? 0);
                const netHours = grossHours - (totalDeductedMins / 60);

                let logRows = "-";
                if (item.logs && item.logs.length > 0) {
                    logRows = item.logs.map(log => {
                        const range = `${formatTime(log.time_in)} - ${formatTime(log.time_out)}`;
                        return `<div class="mb-1">${range}${remarkLabel(log.remarks)}</div>`;
                    }).join("");
                }

                // Assigned shift for this day (actual schedule)
                let schedCell = '<span class="text-muted">—</span>';
                if (item.schedule && item.schedule.sched_in && item.schedule.sched_out) {
                    const s = item.schedule;
                    let sched = `${formatClock(s.sched_in)} - ${formatClock(s.sched_out)}`;
                    let extra = '';
                    if (s.break_start && s.break_end) {
                        extra += `<div class="text-muted" style="font-size:.68rem;">Break ${formatClock(s.break_start)}-${formatClock(s.break_end)}</div>`;
                    }
                    if (s.shift_type) {
                        extra += `<div class="text-muted text-uppercase" style="font-size:.62rem;letter-spacing:.4px;">${s.shift_type}</div>`;
                    }
                    schedCell = `<span class="badge-soft-primary px-2 py-1 rounded" style="font-size:.72rem;font-weight:600;">${sched}</span>${extra}`;
                }

                const late = parseInt(item.mins_late ?? 0);
                const ut   = parseInt(item.mins_undertime ?? 0);
                const nd   = parseInt(item.mins_night_diff ?? 0);
                const pass = parseInt(item.outpass_minutes ?? 0);
                const ob   = parseInt(item.over_break_minutes ?? 0);

                sub.gross += grossHours; sub.ded += totalDeductedMins; sub.net += netHours;
                sub.late += late; sub.ut += ut; sub.nd += nd; sub.pass += pass; sub.ob += ob;

                const isPartial = !!item.is_partial;
                const dateCell = isPartial
                    ? `${item.formatted_date ?? '-'}<div><span class="badge bg-warning text-dark" style="font-size:.6rem;font-weight:700;">PARTIAL</span></div>`
                    : (item.formatted_date ?? '-');
                // Partial day (logs but no computed summary yet) — leave the computed
                // columns blank rather than showing a misleading 0.
                const dash = isPartial ? '<span class="text-muted">—</span>' : null;

                no++;
                rows += `
                    <tr${isPartial ? ' style="background:#fffbeb;"' : ''}>
                        <td>${no}</td>
                        <td class="text-start text-capitalize">${capitalizeName(empName)}</td>
                        <td>${dateCell}</td>
                        <td>${schedCell}</td>
                        <td colspan="2">${logRows}</td>
                        <td class="fw-bold">${grossHours.toFixed(2)}</td>
                        <td class="text-danger fw-bold">${totalDeductedMins > 0 ? totalDeductedMins + 'm' : '-'}</td>
                        <td class="text-primary fw-bold">${netHours.toFixed(2)}</td>
                        <td>${dash ?? m(late)}</td>
                        <td>${dash ?? m(ut)}</td>
                        <td>${dash ?? m(nd)}</td>
                        <td>${dash ?? m(pass)}</td>
                        <td>${dash ?? m(ob)}</td>
                    </tr>
                `;
            });

            // Per-employee TOTAL row (totals start from the Duration column)
            rows += `
                <tr class="fw-bold" style="background:#eef6f6;">
                    <td colspan="6" class="text-end">TOTAL &mdash; ${capitalizeName(empName)} <span style="font-weight:normal;color:#6b7280;">(${items.length} day${items.length > 1 ? 's' : ''} attended)</span></td>
                    <td>${sub.gross.toFixed(2)}</td>
                    <td class="text-danger">${sub.ded}m</td>
                    <td class="text-primary">${sub.net.toFixed(2)}</td>
                    <td>${sub.late}m</td>
                    <td>${sub.ut}m</td>
                    <td>${sub.nd}m</td>
                    <td>${sub.pass}m</td>
                    <td>${sub.ob}m</td>
                </tr>
            `;

            grand.gross += sub.gross; grand.ded += sub.ded; grand.net += sub.net;
            grand.late += sub.late; grand.ut += sub.ut; grand.nd += sub.nd; grand.pass += sub.pass; grand.ob += sub.ob;
        });

        // Grand total when the report shows more than one employee
        if (groups.size > 1) {
            rows += `
                <tr class="fw-bold" style="background:#dfeeee;border-top:2px solid #008080;">
                    <td colspan="6" class="text-end">GRAND TOTAL <span style="font-weight:normal;color:#6b7280;">(${data.length} total attendance)</span></td>
                    <td>${grand.gross.toFixed(2)}</td>
                    <td class="text-danger">${grand.ded}m</td>
                    <td class="text-primary">${grand.net.toFixed(2)}</td>
                    <td>${grand.late}m</td>
                    <td>${grand.ut}m</td>
                    <td>${grand.nd}m</td>
                    <td>${grand.pass}m</td>
                    <td>${grand.ob}m</td>
                </tr>
            `;
        }

        tableBody.html(rows);
    }

    function formatTime(timeString) {
        if (!timeString) return "-";
        const date = new Date(timeString);
        return date.toLocaleTimeString("en-US", {
            hour: "2-digit",
            minute: "2-digit",
            hour12: true,
        });
    }

    // Surface a punch's remark under its time range — but only when it's noteworthy.
    // "Regular Shift" (the on-time normal case) is suppressed as noise; exception remarks
    // like "Auto-closed (Missed logout)" explain why a session earned 0 paid hours. Errors
    // (invalid / missed-logout) show red, cap/no-schedule notes show amber.
    function remarkLabel(remark) {
        const r = String(remark || "").trim();
        if (!r || /^regular shift$/i.test(r)) return "";
        const isError = /invalid|missed logout|auto-closed/i.test(r);
        const color = isError ? "#b91c1c" : "#b45309";
        const bg    = isError ? "#fee2e2" : "#fef3c7";
        return `<div class="mt-1" style="font-size:.62rem;font-weight:600;color:${color};background:${bg};display:inline-block;padding:1px 7px;border-radius:6px;line-height:1.4;">
                    <i class="fa-solid fa-circle-info me-1"></i>${escapeHtml(r)}
                </div>`;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
        }[c]));
    }

    // Format a plain "HH:MM" / "HH:MM:SS" schedule time into 12-hour clock.
    function formatClock(t) {
        if (!t) return "-";
        const parts = String(t).split(":");
        if (parts.length < 2) return t;
        let h = parseInt(parts[0], 10);
        const min = parts[1].padStart(2, "0");
        if (isNaN(h)) return t;
        const ampm = h >= 12 ? "PM" : "AM";
        h = h % 12 || 12;
        return `${h}:${min} ${ampm}`;
    }

    // Re-fetch when the department scope changes.
    deptSelect.on("change", fetchAttendance);

    // Deep-link support: apply department / date range passed via URL query, then fetch.
    (function applyUrlFilters() {
        const qp = new URLSearchParams(window.location.search);
        if (qp.get("department")) deptSelect.val(qp.get("department"));
        if (qp.get("from")) dateFrom.val(qp.get("from"));
        if (qp.get("to")) dateTo.val(qp.get("to"));
    })();

    fetchAttendance();

});

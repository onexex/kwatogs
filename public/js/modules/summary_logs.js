document.addEventListener('DOMContentLoaded', function () {

    const empSelect = $("#txtLastname");
    const dateFrom  = $("#txtDateFrom");
    const dateTo    = $("#txtDateTo");
    const tableBody = $("#tbl_summarylogs");
    const COLS = 15;

    axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
    axios.defaults.headers.common["X-CSRF-TOKEN"] = $('meta[name="csrf-token"]').attr("content");

    // Rows from the last fetch, indexed by _i (stamped before rendering) so the
    // edit modal can pull the record and in-place update it after a save.
    let rows = [];
    let lastSavedId = null;

    $("#btn_slrefresh").on("click", fetchSummaries);

    function fetchSummaries() {
        tableBody.html(`<tr><td colspan="${COLS}" class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>`);

        axios.post("/summary-logs/fetch", {
            empID: empSelect.val(),
            dateFrom: dateFrom.val(),
            dateTo: dateTo.val(),
        })
        .then(response => {
            const res = response.data;
            if (res.status === "success") {
                rows = res.data || [];
                renderTable(rows);
            } else {
                tableBody.html(`<tr><td colspan="${COLS}" class="text-center text-danger">No records found</td></tr>`);
            }
        })
        .catch(error => {
            console.error(error);
            tableBody.html(`<tr><td colspan="${COLS}" class="text-center text-danger">Error fetching data</td></tr>`);
        });
    }

    function dedTotal(item) {
        // Server sends deduction_minutes pre-summed; fall back to summing the rows.
        if (item.deduction_minutes !== undefined && item.deduction_minutes !== null) {
            return parseInt(item.deduction_minutes) || 0;
        }
        if (item.manual_deductions && item.manual_deductions.length > 0) {
            return item.manual_deductions.reduce((sum, d) => sum + parseInt(d.deduction_minutes || 0), 0);
        }
        return 0;
    }

    function renderTable(data) {
        if (!data.length) {
            tableBody.html(`<tr><td colspan="${COLS}" class="text-center">No computed summaries found for this range</td></tr>`);
            return;
        }

        const capitalizeName = (name) => name.toLowerCase().replace(/\b\w/g, s => s.toUpperCase());
        const m = (n) => `${Number(n || 0)}m`;

        data.forEach((item, i) => { item._i = i; });

        // Group rows per employee so each employee gets its own TOTAL row
        const groups = new Map();
        data.forEach(item => {
            const key = item.employee ? (item.employee.empID ?? `${item.employee.lname}, ${item.employee.fname}`) : 'N/A';
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key).push(item);
        });

        let html = "";
        let no = 0;
        const grand = { gross:0, ded:0, net:0, late:0, ut:0, nd:0, pass:0, ob:0 };

        groups.forEach(items => {
            const sub = { gross:0, ded:0, net:0, late:0, ut:0, nd:0, pass:0, ob:0 };
            let empName = 'N/A';

            items.forEach(item => {
                empName = item.employee ? `${item.employee.lname}, ${item.employee.fname}` : 'N/A';

                const ded        = dedTotal(item);
                const grossHours = parseFloat(item.total_hours ?? 0);
                const netHours   = grossHours - (ded / 60);

                let logRows = "-";
                if (item.logs && item.logs.length > 0) {
                    logRows = item.logs.map(log => `${formatTime(log.time_in)} - ${formatTime(log.time_out)}`).join("<br>");
                }

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

                sub.gross += grossHours; sub.ded += ded; sub.net += netHours;
                sub.late += late; sub.ut += ut; sub.nd += nd; sub.pass += pass; sub.ob += ob;

                no++;
                const flash = item.id === lastSavedId ? ' class="sl-just-saved"' : '';

                // A day already covered by a generated payroll run is read-only:
                // the payslip was computed from these figures. Lock badge, no Edit.
                const actionCell = item.payroll_locked
                    ? `<span class="badge bg-secondary" style="font-size:.62rem;font-weight:700;cursor:help;"
                            title="Locked — payroll ${item.payroll_period ?? ''} already generated. Delete/regenerate that run first.">
                            <i class="fa-solid fa-lock me-1"></i>PAYROLL
                       </span>`
                    : `<button type="button" class="btn btn-sm btn-teal rounded-pill px-3 sl-edit" data-i="${item._i}" title="Edit computed values">
                            <i class="fa-solid fa-pen"></i>
                       </button>`;

                html += `
                    <tr data-id="${item.id}"${flash}>
                        <td>${no}</td>
                        <td class="text-start text-capitalize">${capitalizeName(empName)}</td>
                        <td>${item.formatted_date ?? '-'}</td>
                        <td>${schedCell}</td>
                        <td colspan="2">${logRows}</td>
                        <td class="fw-bold">${grossHours.toFixed(2)}</td>
                        <td class="text-danger fw-bold">${ded > 0 ? ded + 'm' : '-'}</td>
                        <td class="text-primary fw-bold">${netHours.toFixed(2)}</td>
                        <td>${m(late)}</td>
                        <td>${m(ut)}</td>
                        <td>${m(nd)}</td>
                        <td>${m(pass)}</td>
                        <td>${m(ob)}</td>
                        <td class="pe-4">${actionCell}</td>
                    </tr>
                `;
            });

            html += `
                <tr class="fw-bold" style="background:#eef6f6;">
                    <td colspan="6" class="text-end">TOTAL &mdash; ${capitalizeName(empName)} <span style="font-weight:normal;color:#6b7280;">(${items.length} day${items.length > 1 ? 's' : ''})</span></td>
                    <td>${sub.gross.toFixed(2)}</td>
                    <td class="text-danger">${sub.ded}m</td>
                    <td class="text-primary">${sub.net.toFixed(2)}</td>
                    <td>${sub.late}m</td>
                    <td>${sub.ut}m</td>
                    <td>${sub.nd}m</td>
                    <td>${sub.pass}m</td>
                    <td>${sub.ob}m</td>
                    <td></td>
                </tr>
            `;

            grand.gross += sub.gross; grand.ded += sub.ded; grand.net += sub.net;
            grand.late += sub.late; grand.ut += sub.ut; grand.nd += sub.nd; grand.pass += sub.pass; grand.ob += sub.ob;
        });

        if (groups.size > 1) {
            html += `
                <tr class="fw-bold" style="background:#dfeeee;border-top:2px solid #008080;">
                    <td colspan="6" class="text-end">GRAND TOTAL <span style="font-weight:normal;color:#6b7280;">(${data.length} summaries)</span></td>
                    <td>${grand.gross.toFixed(2)}</td>
                    <td class="text-danger">${grand.ded}m</td>
                    <td class="text-primary">${grand.net.toFixed(2)}</td>
                    <td>${grand.late}m</td>
                    <td>${grand.ut}m</td>
                    <td>${grand.nd}m</td>
                    <td>${grand.pass}m</td>
                    <td>${grand.ob}m</td>
                    <td></td>
                </tr>
            `;
        }

        tableBody.html(html);
        lastSavedId = null;
    }

    // ── Edit modal ──────────────────────────────────────────────
    const modalEl = document.getElementById('slEditModal');
    const modal   = new bootstrap.Modal(modalEl);
    const err     = $("#slModalErr");

    const fGross = $("#slGross"), fDed = $("#slDed"), fNet = $("#slNet"),
          fLate = $("#slLate"), fUt = $("#slUt"), fNd = $("#slNd"),
          fPass = $("#slPass"), fOb = $("#slOb"), fNote = $("#slNote");

    const num = (el) => parseFloat(el.val()) || 0;

    // Net is derived, never stored: Gross/Deductions drive Net; typing in Net back-solves Gross.
    function syncNet()   { fNet.val((num(fGross) - num(fDed) / 60).toFixed(2)); }

    fGross.on("input", syncNet);
    fDed.on("input", syncNet);
    fNet.on("input", function () {
        fGross.val(Math.max(0, num(fNet) + num(fDed) / 60).toFixed(2));
    });

    tableBody.on("click", ".sl-edit", function () {
        const item = rows[$(this).data("i")];
        if (!item || item.payroll_locked) return; // locked rows never render this button — belt & braces

        const name = item.employee ? `${item.employee.lname}, ${item.employee.fname}` : 'N/A';
        $("#slEditWho").text(`${name.toUpperCase()} — ${item.formatted_date}`);
        $("#slEditId").val(item.id);

        const ded = dedTotal(item);
        fGross.val(parseFloat(item.total_hours ?? 0).toFixed(2));
        fDed.val(ded);
        fLate.val(parseInt(item.mins_late ?? 0));
        fUt.val(parseInt(item.mins_undertime ?? 0));
        fNd.val(parseInt(item.mins_night_diff ?? 0));
        fPass.val(parseInt(item.outpass_minutes ?? 0));
        fOb.val(parseInt(item.over_break_minutes ?? 0));
        fNote.val("");
        syncNet();

        err.addClass("d-none").text("");
        modal.show();
    });

    $("#slSave").on("click", function () {
        const id = $("#slEditId").val();
        if (!id) return;

        const btn = $(this);
        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
        err.addClass("d-none").text("");

        axios.post(`/summary-logs/update/${id}`, {
            total_hours:        num(fGross).toFixed(2),
            deduction_minutes:  Math.round(num(fDed)),
            mins_late:          Math.round(num(fLate)),
            mins_undertime:     Math.round(num(fUt)),
            mins_night_diff:    Math.round(num(fNd)),
            outpass_minutes:    Math.round(num(fPass)),
            over_break_minutes: Math.round(num(fOb)),
            edit_note:          fNote.val() || null,
        })
        .then(response => {
            const res = response.data;
            if (res.status === "success") {
                if (res.data) {
                    // In-place refresh of the edited record, then re-render
                    const i = rows.findIndex(r => String(r.id) === String(res.data.id));
                    if (i !== -1) {
                        // Keep the context fields the update response doesn't carry
                        res.data.logs = rows[i].logs;
                        res.data.schedule = rows[i].schedule;
                        rows[i] = res.data;
                    }
                    lastSavedId = res.data.id;
                    renderTable(rows);
                }
                modal.hide();
                flash(res.message || "Summary updated.");
            }
        })
        .catch(error => {
            let msg = "Failed to save changes.";
            if (error.response && error.response.status === 422 && error.response.data.errors) {
                msg = Object.values(error.response.data.errors).flat().join(" ");
            } else if (error.response && error.response.data && error.response.data.message) {
                msg = error.response.data.message;
            }
            err.removeClass("d-none").text(msg);
        })
        .finally(() => {
            btn.prop("disabled", false).html('<i class="fa-solid fa-floppy-disk me-2"></i>Save Changes');
        });
    });

    function flash(message) {
        $("#slFlash").html(`
            <div class="alert alert-success alert-dismissible fade show mb-0" role="alert" style="font-size:.8rem;">
                <i class="fa-solid fa-circle-check me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        setTimeout(() => $("#slFlash .alert").fadeOut(400, function () { $(this).remove(); }), 4000);
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

    fetchSummaries();

});

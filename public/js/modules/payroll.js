// ── Uniform alert helper (used across this file) ──────────────────────────
function showAlert(message, type = "warning", title = null) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            icon: type,
            title: title || (type === "error" ? "Error" : "Notice"),
            text: message,
        });
    } else {
        alert(message);
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const printBtn = document.getElementById("btnPrint");

    if (printBtn) {
        printBtn.addEventListener("click", function () {
            const table =
                document.querySelector(".table-responsive")?.innerHTML ||
                "<p>No table data</p>";

            // Get input values
            const payDate = document.getElementById("pay_date")?.value || "N/A";
            const dateFrom =
                document.getElementById("date_from")?.value || "N/A";
            const dateTo = document.getElementById("date_to")?.value || "N/A";
            const departmentSel = document.getElementById("selDepartment");
            const departmentName = departmentSel && departmentSel.value !== "all"
                ? (departmentSel.selectedOptions[0]?.text || "")
                : "All Departments";

            // Get current date and time
            const now = new Date();
            const options = {
                year: "numeric",
                month: "long",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            };
            const generatedAt = now.toLocaleString("en-US", options);

            // Get current logged-in user name (Laravel Blade interpolation)
            const generatedBy = window.loggedEmployee || "Unknown User";

            // Company name (for branded header)
            const companySelMain = document.getElementById("selCompany");
            const companyName = companySelMain && companySelMain.value !== "all"
                ? (companySelMain.selectedOptions[0]?.text || "All Organizations")
                : "All Organizations";

            // Open print window
            const printWindow = window.open("", "", "width=1200,height=800");

            printWindow.document.write(`
                <html>
                    <head>
                        <title>Payroll Report</title>
                        <style>
                            body {
                                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                                color: #333;
                                padding: 20px;
                                background-color: #fff;
                            }
                            h2, h4 {
                                text-align: center;
                                margin: 0;
                            }
                            h2 {
                                font-weight: 500;
                                margin-bottom: 5px;
                                font-size: 18px;
                                color: #008080;
                            }
                            h4 {
                                font-weight: 500;
                                margin-bottom: 15px;
                                color: #555;
                            }
                            .report-header {
                                text-align: center;
                                margin-bottom: 20px;
                                padding-bottom: 12px;
                                border-bottom: 3px solid #008080;
                            }
                            .brand {
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                gap: 14px;
                                margin-bottom: 10px;
                            }
                            .brand-logo { height: 52px; width: auto; }
                            .brand-name {
                                font-size: 17px;
                                font-weight: 800;
                                color: #008080;
                                letter-spacing: .5px;
                                line-height: 1.15;
                                text-align: left;
                            }
                            .brand-sub {
                                font-size: 10px;
                                color: #64748b;
                                text-transform: uppercase;
                                letter-spacing: 2px;
                                text-align: left;
                            }
                            .report-meta {
                                margin-top: 10px;
                                font-size: 0.85rem;
                                line-height: 1.4;
                                text-align: center;
                            }
                            table {
                                width: 100%;
                                border-collapse: collapse;
                                font-size: 0.75rem;
                                margin-top: 15px;
                            }
                            th, td {
                                border: 0.5px solid #ccc;
                                padding: 6px 10px;
                                text-align: center;
                            }
                            thead th {
                                background-color: #008080 !important;
                                color: #fff !important;
                                text-transform: uppercase;
                                font-size: 0.7rem;
                                letter-spacing: .3px;
                            }
                            tbody tr:nth-child(even) {
                                background-color: #f9f9f9;
                            }
                            tbody tr:hover {
                                background-color: #e0f2f1;
                            }
                            .footer {
                                margin-top: 14px;
                                font-size: 0.72rem;
                                text-align: right;
                                font-style: italic;
                                color: #999;
                            }
                            .sign {
                                margin-top: 46px;
                                display: flex;
                                justify-content: space-between;
                                font-size: 0.8rem;
                                page-break-inside: avoid;
                            }
                            .sign div { width: 30%; text-align: center; }
                            .sign .ln {
                                border-top: 1px solid #475569;
                                margin-bottom: 4px;
                                padding-top: 4px;
                            }
                            .endmark {
                                margin-top: 18px;
                                text-align: center;
                                font-size: 0.7rem;
                                color: #94a3b8;
                                text-transform: uppercase;
                                letter-spacing: 1px;
                            }
                            .no-print { display: none !important; }
                            @media print {
                                body { padding: 0; }
                                table { page-break-inside: auto; }
                                tr { page-break-inside: avoid; page-break-after: auto; }
                                .sign { page-break-inside: avoid; }
                                .no-print { display: none !important; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="report-header">
                            <div class="brand">
                                <img src="${window.payrollLogoUrl || (window.location.origin + '/img/kwatogslogo.jpg')}" class="brand-logo" alt="logo" onerror="this.style.display='none'">
                                <div>
                                    <div class="brand-name">${companyName}</div>
                                    <div class="brand-sub">Human Resource &amp; Payroll System</div>
                                </div>
                            </div>
                            <h2>Payroll Report</h2>
                            <h4>Payroll Period: ${dateFrom} to ${dateTo}</h4>
                            <div class="report-meta">
                                <strong>Payroll Date:</strong> ${payDate} <br>
                                <strong>Department:</strong> ${departmentName} <br>
                                <strong>Generated By:</strong> ${generatedBy} <br>
                                <strong>Generated On:</strong> ${generatedAt}
                            </div>
                        </div>
                        ${table}

                        <div class="sign">
                            <div><div class="ln"></div>Prepared by</div>
                            <div><div class="ln"></div>Checked &amp; Verified by</div>
                            <div><div class="ln"></div>Approved by</div>
                        </div>

                        <div class="footer">
                            <i>Generated from KWATOGS Payroll System &mdash; ${generatedAt}</i>
                        </div>

                        <div class="endmark">*** End of Report ***</div>
                    </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();
            let __printed = false;
            const __doPrint = () => {
                if (__printed) return;
                __printed = true;
                try { printWindow.print(); } catch (e) {}
                printWindow.close();
            };
            const __logo = printWindow.document.querySelector(".brand-logo");
            if (__logo && !__logo.complete) {
                __logo.addEventListener("load", __doPrint);
                __logo.addEventListener("error", __doPrint);
                setTimeout(__doPrint, 2500); // safety fallback
            } else {
                setTimeout(__doPrint, 200);
            }
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const summaryBtn = document.getElementById("btnSummary");

    if (summaryBtn) {
        summaryBtn.addEventListener("click", async function () {

            // ── 1. Grab filter values ────────────────────────────────────────
            const payrollId = document.getElementById("payroll_id")?.value;
            const rawPayDate = document.getElementById("pay_date")?.value;
            const payDate   = rawPayDate || "N/A";
            const dateFrom  = document.getElementById("date_from")?.value  || "N/A";
            const dateTo    = document.getElementById("date_to")?.value    || "N/A";
            const companyId    = document.getElementById("selCompany")?.value || "all";
            const classId      = document.getElementById("selFilter")?.value  || "all";
            const departmentId = document.getElementById("selDepartment")?.value || "all";
            const departmentName = departmentId !== "all"
                ? (document.getElementById("selDepartment")?.selectedOptions[0]?.text || "")
                : "All Departments";

            if (!rawPayDate) {
                showAlert("Please select a Payroll Date first.");
                return;
            }

            const params = new URLSearchParams({
                payroll_date: rawPayDate,
                company_id: companyId,
                class_id: classId,
                department_id: departmentId
            });

            // ── 2. Fetch grouped detail records ──────────────────────────────
            let employees = [];
            try {
                const res = await fetch(
                    `/payroll/details/by-payroll?${params.toString()}`,
                    {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            "Accept": "application/json",
                        },
                    }
                );

                const json = await res.json().catch(() => ({}));

                if (!res.ok || json.success === false) {
                    throw new Error(json.message || json.error || `Server error: ${res.status}`);
                }

                employees = json.data ?? [];
            } catch (err) {
                showAlert("Failed to load payroll details: " + err.message);
                return;
            }

            if (!employees.length) {
                showAlert("No payroll detail records found for this payroll.");
                return;
            }

            // ── 3. Build HTML for each employee block ────────────────────────
            const fmt = (n) =>
                Number(n).toLocaleString("en-PH", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });

            const employeeBlocks = employees
                .map((emp) => {
                    const rows = emp.records
                        .map(
                            (r) => `
                        <tr>
                            <td>${r.date}</td>
                            <td>${r.logsType}</td>
                            <td>${fmt(r.totalHours)}</td>
                            <td>${r.late_minutes}</td>
                            <td>${r.undertime_minutes}</td>
                            <td>${fmt(r.late_deduction)}</td>
                            <td>${fmt(r.undertime_deduction)}</td>
                            <td>${fmt(r.night_diff_hours)}</td>
                            <td>${fmt(r.night_diff_pay)}</td>
                            <td>${fmt(r.penalty_amount)}</td>
                            <td>${fmt(r.adjustment_amount)}</td>
                            <td>${r.remarks}</td>
                        </tr>`
                        )
                        .join("");

                    const t = emp.totals;

                    return `
                    <div class="employee-block">
                        <div class="emp-header">
                            <div>
                                <span class="emp-name">${emp.employee_name}</span>
                                <span class="emp-meta">ID: ${emp.employee_id}</span>
                            </div>
                            <div>
                                <span class="emp-meta">${emp.department}</span>
                                &nbsp;|&nbsp;
                                <span class="emp-meta">${emp.position}</span>
                            </div>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Total Hrs</th>
                                    <th>Late (min)</th>
                                    <th>UT (min)</th>
                                    <th>Late Ded.</th>
                                    <th>UT Ded.</th>
                                    <th>ND Hrs</th>
                                    <th>ND Pay</th>
                                    <th>Penalty</th>
                                    <th>Adjustment</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rows}
                            </tbody>
                            <tfoot>
                                <tr class="totals-row">
                                    <td colspan="2"><strong>TOTALS</strong></td>
                                    <td>${fmt(t.totalHours)}</td>
                                    <td>${t.late_minutes}</td>
                                    <td>${t.undertime_minutes}</td>
                                    <td>${fmt(t.late_deduction)}</td>
                                    <td>${fmt(t.undertime_deduction)}</td>
                                    <td>${fmt(t.night_diff_hours)}</td>
                                    <td>${fmt(t.night_diff_pay)}</td>
                                    <td>${fmt(t.penalty_amount)}</td>
                                    <td>${fmt(t.adjustment_amount)}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>`;
                })
                .join("");

            // ── 4. Meta info ─────────────────────────────────────────────────
            const now = new Date();
            const generatedAt = now.toLocaleString("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
            const generatedBy = window.loggedEmployee || "Unknown User";

            // Company name (for branded header)
            const companySelDetail = document.getElementById("selCompany");
            const companyName = companySelDetail && companySelDetail.value !== "all"
                ? (companySelDetail.selectedOptions[0]?.text || "All Organizations")
                : "All Organizations";

            // ── 5. Open print window ─────────────────────────────────────────
            const printWindow = window.open("", "", "width=1400,height=900");

            printWindow.document.write(`
                <html>
                    <head>
                        <meta charset="utf-8">
                        <title>Payroll Detail Report</title>
                        <style>
                            * { box-sizing: border-box; }

                            body {
                                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                                color: #333;
                                padding: 20px;
                                background: #fff;
                                font-size: 0.78rem;
                            }

                            /* ── Report header ── */
                            .report-header {
                                text-align: center;
                                margin-bottom: 20px;
                                padding-bottom: 12px;
                                border-bottom: 3px solid #008080;
                            }
                            .report-header h2 {
                                margin: 0 0 4px;
                                font-size: 18px;
                                font-weight: 500;
                                color: #008080;
                            }
                            .report-header h4 {
                                margin: 0 0 8px;
                                font-weight: 500;
                                color: #555;
                            }
                            .report-meta {
                                font-size: 0.82rem;
                                line-height: 1.6;
                                color: #444;
                            }
                            .brand {
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                gap: 14px;
                                margin-bottom: 10px;
                            }
                            .brand-logo { height: 52px; width: auto; }
                            .brand-name {
                                font-size: 17px;
                                font-weight: 800;
                                color: #008080;
                                letter-spacing: .5px;
                                line-height: 1.15;
                                text-align: left;
                            }
                            .brand-sub {
                                font-size: 10px;
                                color: #64748b;
                                text-transform: uppercase;
                                letter-spacing: 2px;
                                text-align: left;
                            }

                            /* ── Employee block ── */
                            .employee-block {
                                margin-bottom: 30px;
                                page-break-inside: avoid;
                            }
                            .emp-header {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                background-color: #e0f2f1;
                                border-left: 4px solid #008080;
                                padding: 6px 10px;
                                margin-bottom: 4px;
                                border-radius: 2px;
                            }
                            .emp-name {
                                font-weight: 600;
                                font-size: 0.9rem;
                                color: #006060;
                                margin-right: 10px;
                            }
                            .emp-meta {
                                font-size: 0.78rem;
                                color: #555;
                            }

                            /* ── Table ── */
                            table {
                                width: 100%;
                                border-collapse: collapse;
                                font-size: 0.72rem;
                            }
                            th {
                                background-color: #008080;
                                color: #fff;
                                padding: 5px 7px;
                                text-align: center;
                                white-space: nowrap;
                            }
                            td {
                                border: 0.5px solid #ccc;
                                padding: 4px 7px;
                                text-align: center;
                            }
                            tbody tr:nth-child(even) { background-color: #f9f9f9; }
                            tbody tr:hover           { background-color: #e8f5e9; }

                            /* ── Totals row ── */
                            .totals-row {
                                background-color: #fff8e1 !important;
                                font-weight: 600;
                                border-top: 2px solid #008080;
                            }

                            /* ── Footer ── */
                            .footer {
                                margin-top: 14px;
                                font-size: 0.72rem;
                                text-align: right;
                                font-style: italic;
                                color: #999;
                            }
                            .sign {
                                margin-top: 46px;
                                display: flex;
                                justify-content: space-between;
                                font-size: 0.8rem;
                                page-break-inside: avoid;
                            }
                            .sign div { width: 30%; text-align: center; }
                            .sign .ln {
                                border-top: 1px solid #475569;
                                margin-bottom: 4px;
                                padding-top: 4px;
                            }
                            .endmark {
                                margin-top: 18px;
                                text-align: center;
                                font-size: 0.7rem;
                                color: #94a3b8;
                                text-transform: uppercase;
                                letter-spacing: 1px;
                            }

                            @media print {
                                body { padding: 0; }
                                .employee-block { page-break-inside: avoid; }
                                tr { page-break-inside: avoid; page-break-after: auto; }
                                .sign { page-break-inside: avoid; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="report-header">
                            <div class="brand">
                                <img src="${window.payrollLogoUrl || (window.location.origin + '/img/kwatogslogo.jpg')}" class="brand-logo" alt="logo" onerror="this.style.display='none'">
                                <div>
                                    <div class="brand-name">${companyName}</div>
                                    <div class="brand-sub">Human Resource &amp; Payroll System</div>
                                </div>
                            </div>
                            <h2>Payroll Detail Report</h2>
                            <h4>Payroll Period: ${dateFrom} to ${dateTo}</h4>
                            <div class="report-meta">
                                <strong>Payroll Date:</strong> ${payDate} &nbsp;|&nbsp;
                                <strong>Department:</strong> ${departmentName} &nbsp;|&nbsp;
                                <strong>Generated By:</strong> ${generatedBy} &nbsp;|&nbsp;
                                <strong>Generated On:</strong> ${generatedAt}
                            </div>
                        </div>

                        ${employeeBlocks}

                        <div class="sign">
                            <div><div class="ln"></div>Prepared by</div>
                            <div><div class="ln"></div>Checked &amp; Verified by</div>
                            <div><div class="ln"></div>Approved by</div>
                        </div>

                        <div class="footer">
                            <i>Generated from KWATOGS Payroll System &mdash; ${generatedAt}</i>
                        </div>

                        <div class="endmark">*** End of Report ***</div>
                    </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();
            let __printed = false;
            const __doPrint = () => {
                if (__printed) return;
                __printed = true;
                try { printWindow.print(); } catch (e) {}
                printWindow.close();
            };
            const __logo = printWindow.document.querySelector(".brand-logo");
            if (__logo && !__logo.complete) {
                __logo.addEventListener("load", __doPrint);
                __logo.addEventListener("error", __doPrint);
                setTimeout(__doPrint, 2500); // safety fallback
            } else {
                setTimeout(__doPrint, 200);
            }
        });
    }
});
// Initialize jQuery once DOM is ready
$(document).ready(function () {
    const $payrollTableBody = $("#payrollTableBody");

    // ✅ Helper: format numbers with commas and 2 decimals
    function formatNumber(value) {
        const num = parseFloat(value) || 0;
        return num.toLocaleString("en-US", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }
    // Exposed globally so the Print Report handler (a separate closure, defined
    // earlier in this file) can reuse the exact same formatting at click time.
    window.formatNumber = formatNumber;

    // ✅ Fetch Payroll Data
    function fetchPayroll() {
        const dateFrom = $("#date_from").val();
        const dateTo = $("#date_to").val();
        const payDate = $("#pay_date").val();

        // ✨ ADD THIS: Kunin ang values ng bagong filters ✨
        const companyId = $("#selCompany").val() || "all";
        const classificationId = $("#selFilter").val() || "all";
        const departmentId = $("#selDepartment").val() || "all";

        $payrollTableBody.html(
            '<tr class="payroll-state"><td colspan="25" class="text-center text-muted">' +
            '<div class="spinner-border text-teal" role="status"></div>' +
            '<div class="mt-2 small fw-semibold">Loading payroll…</div></td></tr>'
        );

        axios
            .get("/payroll/fetch", {
                params: {
                    date_from: dateFrom,
                    date_to: dateTo,
                    payDate: payDate,
                    company_id: companyId, // Pinasa sa backend
                    classification_id: classificationId, // Pinasa sa backend
                    department_id: departmentId, // Pinasa sa backend
                },
            })
            .then(function (response) {
                const data = response.data;
                $payrollTableBody.empty();

                if (data && data.success === false) {
                    $payrollTableBody.html(
                        `<tr class="payroll-state"><td colspan="25" class="text-center text-danger">` +
                        `<div class="payroll-empty-icon" style="background:#fee2e2;color:#b91c1c;"><i class="fa fa-triangle-exclamation"></i></div>` +
                        `<div class="fw-semibold">${data.message || "Error fetching payroll data."}</div>` +
                        `${data.error ? `<div class="small text-muted">${data.error}</div>` : ""}</td></tr>`,
                    );
                    return;
                }

                if (!Array.isArray(data) || data.length === 0) {
                    $payrollTableBody.html(
                        '<tr class="payroll-state"><td colspan="25" class="text-center text-muted">' +
                        '<div class="payroll-empty-icon"><i class="fa fa-folder-open"></i></div>' +
                        '<div class="fw-semibold">No payroll data found</div>' +
                        '<div class="small">Generate the payroll for this pay date, or adjust your filters.</div></td></tr>',
                    );
                    return;
                }

                const sumFields = ['basic_salary','basicPay','abs_ut_deduction','holiday_pay','overtime_pay','night_diff_pay','gross_pay','sss_contribution','sss_loan','pagibig_contribution','pagibig_loan','philhealth_contribution','taxable_income','withholding_tax','allowances','adjustment_amount','penalty_amount','other_deduction','company_loan','cash_advance','net_pay','pay_rec'];
                const totals = {};
                sumFields.forEach((fld) => { totals[fld] = 0; });

                $.each(data, function (index, payroll) {
                    const row = `
                    <tr>
                        <td class="ps-4 col-num text-muted">${index + 1}</td>
                        <td class="col-emp fw-semibold">
                            ${((payroll.employee?.fname || "") + " " + (payroll.employee?.lname || ""))
                                .toLowerCase()
                                .replace(/\b\w/g, (char) => char.toUpperCase())}
                            <div class="no-print mt-1 d-flex flex-wrap gap-1">
                                <a href="/payroll/payslip?pay_date=${encodeURIComponent(payDate)}&employee_id=${encodeURIComponent(payroll.employee_id || '')}" target="_blank" class="badge bg-secondary text-white text-decoration-none d-inline-block" style="font-size:10px;"><i class="fa fa-file-invoice me-1"></i>Payslip</a>
                                ${window.canViewPayrollLogs ? `<a href="/payroll-logs/print?pay_date=${encodeURIComponent(payDate)}&employee_id=${encodeURIComponent(payroll.employee_id || '')}" target="_blank" class="badge text-white text-decoration-none d-inline-block" style="font-size:10px;background-color:#008080;"><i class="fa fa-list-ul me-1"></i>Log</a>` : ''}
                            </div>
                        </td>
                            <td>${formatNumber(payroll.basic_salary)}</td>
                            <td>${formatNumber(payroll.basicPay)}</td>
                            <td class="text-danger">${formatNumber(payroll.abs_ut_deduction || 0)}</td>

                            <td class="bg-earnings">${formatNumber(payroll.holiday_pay || 0)}</td>
                            <td class="bg-earnings">${formatNumber(payroll.overtime_pay || 0)}</td>
                            <td class="bg-earnings">${formatNumber(payroll.night_diff_pay || 0)}</td>

                            <td class="bg-light fw-bold-total">${formatNumber(payroll.gross_pay || 0)}</td>

                            <td class="bg-deductions">${formatNumber(payroll.sss_contribution || 0)}</td>
                            <td class="bg-deductions">${formatNumber(payroll.sss_loan || 0)}</td>
                            <td class="bg-deductions">${formatNumber(payroll.pagibig_contribution || 0)}</td>
                            <td class="bg-deductions">${formatNumber(payroll.pagibig_loan || 0)}</td>
                            <td class="bg-deductions">${formatNumber(payroll.philhealth_contribution || 0)}</td>

                            <td>${formatNumber(payroll.taxable_income || 0)}</td>
                            <td>${formatNumber(payroll.withholding_tax || 0)}</td>

                            <td>${formatNumber(payroll.allowances || 0)}</td>
                            <td>${formatNumber(payroll.adjustment_amount || 0)}</td>

                            <td>${formatNumber((Number(payroll.penalty_amount) || 0) + (Number(payroll.other_deduction) || 0))}</td>
                            <td>${formatNumber(payroll.company_loan || 0)}</td>
                            <td>${formatNumber(payroll.cash_advance || 0)}</td>
                            <td class="bg-light fw-bold">${formatNumber(payroll.net_pay || 0)}</td>
                            <td class="pe-4 fw-bold-total">${formatNumber(payroll.pay_rec || 0)}</td>
                    </tr>
                    `;
                    sumFields.forEach((fld) => { totals[fld] += Number(payroll[fld]) || 0; });
                    $payrollTableBody.append(row);
                });

                // ── Grand totals row ──
                const totalsRow = `
                    <tr class="payroll-totals fw-bold" style="background:#e0f2f1;border-top:2px solid #008080;">
                        <td colspan="2" class="ps-4 text-uppercase col-totals">Totals (${data.length})</td>
                        <td>${formatNumber(totals.basic_salary)}</td>
                        <td>${formatNumber(totals.basicPay)}</td>
                        <td>${formatNumber(totals.abs_ut_deduction)}</td>
                        <td>${formatNumber(totals.holiday_pay)}</td>
                        <td>${formatNumber(totals.overtime_pay)}</td>
                        <td>${formatNumber(totals.night_diff_pay)}</td>
                        <td>${formatNumber(totals.gross_pay)}</td>
                        <td>${formatNumber(totals.sss_contribution)}</td>
                        <td>${formatNumber(totals.sss_loan)}</td>
                        <td>${formatNumber(totals.pagibig_contribution)}</td>
                        <td>${formatNumber(totals.pagibig_loan)}</td>
                        <td>${formatNumber(totals.philhealth_contribution)}</td>
                        <td>${formatNumber(totals.taxable_income)}</td>
                        <td>${formatNumber(totals.withholding_tax)}</td>
                        <td>${formatNumber(totals.allowances)}</td>
                        <td>${formatNumber(totals.adjustment_amount)}</td>
                        <td>${formatNumber(totals.penalty_amount + totals.other_deduction)}</td>
                        <td>${formatNumber(totals.company_loan)}</td>
                        <td>${formatNumber(totals.cash_advance)}</td>
                        <td>${formatNumber(totals.net_pay)}</td>
                        <td class="pe-4">${formatNumber(totals.pay_rec)}</td>
                    </tr>
                `;
                $payrollTableBody.append(totalsRow);

                // Visually mute zero amounts so meaningful figures stand out. Only
                // touches data rows (not the totals row) and only exact "0.00" cells.
                $payrollTableBody.find("tr:not(.payroll-totals) td").each(function () {
                    if (this.textContent.trim() === "0.00") this.classList.add("cell-zero");
                });
            })
            .catch(function (error) {
                console.error(error);
                const apiError = error.response?.data;
                $payrollTableBody.html(
                    `<tr class="payroll-state"><td colspan="25" class="text-center text-danger">` +
                    `<div class="payroll-empty-icon" style="background:#fee2e2;color:#b91c1c;"><i class="fa fa-triangle-exclamation"></i></div>` +
                    `<div class="fw-semibold">${apiError?.message || "Error fetching payroll data."}</div>` +
                    `${apiError?.error ? `<div class="small text-muted">${apiError.error}</div>` : ""}</td></tr>`,
                );
            });
    }

    // ✅ Bind event: Fetch Payroll
    $("#btnPayroll").on("click", fetchPayroll);
    $("#selCompany, #selFilter, #selDepartment").on("change", fetchPayroll);

    // ✅ Bulk: open printable payslips for everyone in the current pay date (respects filters)

    // ✅ Export: payroll disbursement files (respects current filters)
    function payrollExportParams() {
        const payDate = $("#pay_date").val();
        if (!payDate) { showAlert("Select a pay date first.", "warning", "No Pay Date"); return null; }
        return new URLSearchParams({
            pay_date: payDate,
            company_id: $("#selCompany").val() || "all",
            classification_id: $("#selFilter").val() || "all",
            department_id: $("#selDepartment").val() || "all",
        });
    }
    $("#btnExportCash").on("click", function (e) {
        e.preventDefault();
        const params = payrollExportParams();
        if (params) { window.location.href = `/payroll/export/cash?${params.toString()}`; }
    });
    $("#btnExportCard").on("click", function (e) {
        e.preventDefault();
        const params = payrollExportParams();
        if (params) { window.location.href = `/payroll/export/card?${params.toString()}`; }
    });

    $("#btnPrintPayslips").on("click", function () {
        const payDate = $("#pay_date").val();
        if (!payDate) { showAlert("Select a pay date first.", "warning", "No Pay Date"); return; }
        const params = new URLSearchParams({
            pay_date: payDate,
            company_id: $("#selCompany").val() || "all",
            classification_id: $("#selFilter").val() || "all",
            department_id: $("#selDepartment").val() || "all",
        });
        window.open(`/payroll/payslip?${params.toString()}`, "_blank");
    });

    // ✅ Bind event: Generate Payroll
    $(document).on("click", "#btnGenerate", function (e) {
        e.preventDefault();

        // Get date values
        let dateFrom = $("#date_from").val();
        let dateTo = $("#date_to").val();
        let payDate = $("#pay_date").val();

        if (!dateFrom || !dateTo || !payDate) {
            showAlert("Please fill in the cut-off dates and pay date before generating payroll.", "warning", "Missing Dates");
            return;
        }

        // ✨ Department scope: "all" = compute everyone, else a specific department ✨
        const departmentId = $("#selDepartment").val() || "all";
        const companyId = $("#selCompany").val() || "all";
        const departmentName = departmentId !== "all"
            ? ($("#selDepartment option:selected").text() || "selected department")
            : "ALL departments";

        // Confirm scope before generating so the user knows what's being computed
        Swal.fire({
            icon: "question",
            title: "Generate Payroll?",
            html: `This will compute payroll for <strong>${departmentName}</strong>.<br>` +
                  `Pay date: <strong>${payDate}</strong><br>` +
                  `Cut-off: <strong>${dateFrom}</strong> to <strong>${dateTo}</strong>`,
            showCancelButton: true,
            confirmButtonText: "Yes, generate",
            cancelButtonText: "Cancel",
            confirmButtonColor: "#008080",
        }).then((result) => {
            if (!result.isConfirmed) return;

            $("#btnGenerate").prop("disabled", true).text("Generating...");

            axios
                .get("/payroll/compute", {
                    params: {
                        date_from: dateFrom,
                        date_to: dateTo,
                        pay_date: payDate,
                        department_id: departmentId, // ✨ pass selected department (or "all") ✨
                        company_id: companyId,       // pay schedule / scope by company
                    },
                })
                .then(function (response) {
                    fetchPayroll(); // reload payroll table
                    showAlert(
                        response.data.message || "Payroll computation completed successfully!",
                        "success",
                        "Payroll Generated"
                    );
                })
                .catch(function (error) {
                    console.error(error);
                    const data = (error.response && error.response.data) ? error.response.data : {};
                    if (data.validation === "pending_approvals" && Array.isArray(data.issues)) {
                        const rows = data.issues.map(it => `
                            <tr>
                                <td style="text-align:left;text-transform:uppercase;">${it.employee_name || it.employee_id}</td>
                                <td>${it.type}</td>
                                <td>${it.period}</td>
                                <td>${it.detail}</td>
                                <td><span style="color:#b45309;font-weight:600;">${it.status}</span></td>
                            </tr>`).join("");
                        Swal.fire({
                            icon: "error",
                            title: "Payroll Generation Cancelled",
                            html: `<p style="margin:0 0 8px;">${data.message}</p>`
                                + `<div style="max-height:320px;overflow:auto;">`
                                + `<table style="width:100%;border-collapse:collapse;font-size:12px;">`
                                + `<thead><tr style="background:#f1f5f9;">`
                                + `<th style="padding:6px;text-align:left;">Employee</th>`
                                + `<th style="padding:6px;">Type</th>`
                                + `<th style="padding:6px;">Date(s)</th>`
                                + `<th style="padding:6px;">Detail</th>`
                                + `<th style="padding:6px;">Status</th></tr></thead>`
                                + `<tbody>${rows}</tbody></table></div>`,
                            width: 760,
                            confirmButtonColor: "#008080",
                        });
                    } else {
                        showAlert(
                            data.message || data.error || "An error occurred while generating payroll.",
                            "error",
                            "Error"
                        );
                    }
                })
                .finally(function () {
                    $("#btnGenerate").prop("disabled", false).text("Generate");
                });
        });
    });

    // ✅ Pay Date Logic
    const payDateInput = $("#pay_date");
    const dateFromInput = $("#date_from");
    const dateToInput = $("#date_to");

    // Helper: Format date to YYYY-MM-DD
    const formatDate = (d) => {
        let mm = String(d.getMonth() + 1).padStart(2, "0");
        let dd = String(d.getDate()).padStart(2, "0");
        return `${d.getFullYear()}-${mm}-${dd}`;
    };

    // ── Per-company payroll schedule (pay date + cut-off) ─────
    const companyPeriods = window.companyPayrollPeriods || {};
    const defaultPeriods = [
        { pay_day: 15, pay_end_of_month: false, cutoff_from_day: 26, cutoff_from_prev_month: true, cutoff_to_day: 10 },
        { pay_day: null, pay_end_of_month: true, cutoff_from_day: 11, cutoff_from_prev_month: false, cutoff_to_day: 25 },
    ];

    function activePeriods() {
        const comp = $("#selCompany").val();
        return (comp && comp !== "all" && companyPeriods[comp] && companyPeriods[comp].length)
            ? companyPeriods[comp] : defaultPeriods;
    }

    // Match the selected date to a period's pay rule (specific pay day or end-of-month)
    function matchPeriod(date) {
        if (isNaN(date)) return null;
        const day = date.getDate();
        const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
        return activePeriods().find(p =>
            (p.pay_end_of_month && day === lastDay) ||
            (!p.pay_end_of_month && Number(p.pay_day) === day)
        ) || null;
    }

    // When pay date changes -> validate + auto-fill the cut-off from the company's schedule
    payDateInput.on("change", function () {
        const date = new Date($(this).val());
        const period = matchPeriod(date);

        if (!period) {
            Swal.fire({
                icon: "warning",
                title: "Invalid Pay Date",
                text: "This date is not a valid pay date for the selected company's schedule.",
                confirmButtonColor: "#008080",
            }).then(() => {
                $(this).val("");
                dateFromInput.val("");
                dateToInput.val("");
            });
            return;
        }

        const year = date.getFullYear();
        const month = date.getMonth();
        const fromMonth = period.cutoff_from_prev_month ? month - 1 : month;
        const dateFrom = new Date(year, fromMonth, Number(period.cutoff_from_day));
        const dateTo = new Date(year, month, Number(period.cutoff_to_day));

        dateFromInput.val(formatDate(dateFrom));
        dateToInput.val(formatDate(dateTo));
    });

    // Re-evaluate the cut-off when the company changes (its schedule may differ)
    $("#selCompany").on("change", function () {
        if (payDateInput.val()) payDateInput.trigger("change");
    });

    // ✅ Auto-trigger if pay_date already filled
    if (payDateInput.val()) {
        payDateInput.trigger("change");
    }

    
});


// ── Payroll Approval / Lock ──────────────────────────────────────────────
$(function () {
    function refreshApprovalStatus() {
        const payDate = $("#pay_date").val();
        if (!payDate) { applyApproval(null); return; }
        axios.get("/payroll/approval/status", { params: { pay_date: payDate } })
            .then(res => applyApproval(res.data))
            .catch(() => applyApproval(null));
    }

    function applyApproval(data) {
        const canApprove = window.canApprovePayroll === true;
        const canRegen   = window.canRegeneratePayroll === true;
        const approved   = !!(data && data.approved);

        const $badge = $("#approvalBadge");
        const $approve = $("#btnApprovePayroll");
        const $reopen = $("#btnReopenPayroll");
        const $delete = $("#btnDeletePayroll");
        const $gen = $("#btnGenerate");

        $badge.add($approve).add($reopen).add($delete).addClass("d-none");

        if (approved) {
            $("#approvalBadgeText").text(`APPROVED · FINAL${data.approved_by_name ? " · " + data.approved_by_name : ""}${data.approved_at ? " · " + data.approved_at : ""}`);
            $badge.removeClass("d-none");
            if (canRegen) {
                $reopen.removeClass("d-none");
                $gen.prop("disabled", false);
            } else {
                $gen.prop("disabled", true);
            }
        } else {
            $gen.prop("disabled", false);
            if (canApprove) { $approve.removeClass("d-none"); }
            // Delete is only offered while unapproved, and only with a pay date selected.
            if (canRegen && $("#pay_date").val()) { $delete.removeClass("d-none"); }
        }
    }

    $("#pay_date").on("change", refreshApprovalStatus);
    refreshApprovalStatus();

    $("#btnApprovePayroll").on("click", function () {
        const payDate = $("#pay_date").val();
        if (!payDate) { showAlert("Select a pay date first.", "warning", "No Pay Date"); return; }
        Swal.fire({
            icon: "warning",
            title: "Approve & finalize payroll?",
            html: `Pay date: <strong>${payDate}</strong><br>Once approved, this payroll cannot be regenerated or edited (except by an authorized override).`,
            input: "text",
            inputPlaceholder: "Remarks (optional)",
            showCancelButton: true,
            confirmButtonText: "Yes, approve",
            confirmButtonColor: "#16a34a",
        }).then(r => {
            if (!r.isConfirmed) return;
            axios.post("/payroll/approve", { pay_date: payDate, remarks: r.value || "" })
                .then(res => Swal.fire({ icon: "success", title: "Approved", text: res.data.message, timer: 1500, showConfirmButton: false }).then(refreshApprovalStatus))
                .catch(err => Swal.fire("Error", err.response?.data?.message || "Unable to approve.", "error"));
        });
    });

    $("#btnReopenPayroll").on("click", function () {
        const payDate = $("#pay_date").val();
        Swal.fire({
            icon: "warning",
            title: "Reopen this payroll?",
            html: `Pay date: <strong>${payDate}</strong><br>This lifts the lock so it can be regenerated and edited again.`,
            showCancelButton: true,
            confirmButtonText: "Yes, reopen",
            confirmButtonColor: "#d97706",
        }).then(r => {
            if (!r.isConfirmed) return;
            axios.post("/payroll/reopen", { pay_date: payDate })
                .then(res => Swal.fire({ icon: "success", title: "Reopened", text: res.data.message, timer: 1500, showConfirmButton: false }).then(refreshApprovalStatus))
                .catch(err => Swal.fire("Error", err.response?.data?.message || "Unable to reopen.", "error"));
        });
    });

    $("#btnDeletePayroll").on("click", function () {
        const payDate = $("#pay_date").val();
        if (!payDate) { showAlert("Select a pay date first.", "warning", "No Pay Date"); return; }
        Swal.fire({
            icon: "warning",
            title: "Delete this payroll?",
            html: `Pay date: <strong>${payDate}</strong><br>This permanently removes the <strong>entire</strong> computed payroll for this pay date (all companies/departments) and restores any loan balances it deducted. Approved payroll cannot be deleted.`,
            showCancelButton: true,
            confirmButtonText: "Yes, delete",
            confirmButtonColor: "#dc2626",
        }).then(r => {
            if (!r.isConfirmed) return;
            axios.post("/payroll/delete", {
                pay_date: payDate,
            })
                .then(res => Swal.fire({ icon: "success", title: "Deleted", text: res.data.message, timer: 2000, showConfirmButton: false })
                    .then(() => { refreshApprovalStatus(); $("#btnPayroll").trigger("click"); }))
                .catch(err => Swal.fire("Error", err.response?.data?.message || "Unable to delete payroll.", "error"));
        });
    });

    // ===================== Email Payslips =====================
    if (window.canSendPayslipEmail) {
        const payslipEmailParams = () => ({
            pay_date: $("#pay_date").val(),
            company_id: $("#selCompany").val() || "all",
            classification_id: $("#selFilter").val() || "all",
            department_id: $("#selDepartment").val() || "all",
        });

        function loadPayslipEmailSettings() {
            axios.get("/payroll/payslip-email/settings").then(res => {
                const data = res.data.data;
                const options = res.data.options;
                const $select = $("#selPayslipPasswordSource").empty();
                Object.keys(options).forEach(key => {
                    $select.append(`<option value="${key}">${options[key]}</option>`);
                });
                $select.val(data.password_source);
                $("#chkAutoSendOnApproval").prop("checked", data.auto_send_on_approval);

                const sourceLabel = options[data.password_source] || data.password_source;
                $("#payslipEmailSettingsView").html(
                    `PDF password: <strong>${sourceLabel}</strong> &middot; Auto-send on approval: <strong>${data.auto_send_on_approval ? "On" : "Off"}</strong>`
                );
            }).catch(() => {
                $("#payslipEmailSettingsView").html('<span class="text-danger">Could not load settings.</span>');
            });
        }

        function statusBadge(status) {
            if (status === "sent") return '<span class="badge bg-success text-white">Sent</span>';
            if (status === "failed") return '<span class="badge bg-danger text-white">Failed</span>';
            if (status === "queued") return '<span class="badge bg-secondary text-white">Queued</span>';
            return '<span class="badge bg-light text-muted">Not sent</span>';
        }

        function loadPayslipEmailStatus() {
            const params = payslipEmailParams();
            if (!params.pay_date) {
                $("#payslipEmailStatusBody").html('<tr><td colspan="4" class="text-center text-muted">Select a pay date first.</td></tr>');
                return;
            }
            $("#payslipEmailStatusBody").html('<tr><td colspan="4" class="text-center text-muted">Loading…</td></tr>');
            axios.get("/payroll/payslip/status", { params }).then(res => {
                const rows = res.data.data || [];
                if (!rows.length) {
                    $("#payslipEmailStatusBody").html('<tr><td colspan="4" class="text-center text-muted">No payroll records for this selection.</td></tr>');
                    return;
                }
                $("#payslipEmailStatusBody").html(rows.map(r => `
                    <tr>
                        <td>${r.name || r.employee_id}</td>
                        <td>${r.email || '<span class="text-danger">No email on file</span>'}</td>
                        <td>${statusBadge(r.status)}${r.error ? `<div class="small text-danger">${r.error}</div>` : ''}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary btn-resend-payslip" data-payroll-id="${r.payroll_id}">Resend</button>
                        </td>
                    </tr>
                `).join(''));
            }).catch(() => {
                $("#payslipEmailStatusBody").html('<tr><td colspan="4" class="text-center text-danger">Failed to load status.</td></tr>');
            });
        }

        $("#payslipEmailModal").on("show.bs.modal", function () {
            $("#payslipEmailAlert").empty();
            loadPayslipEmailSettings();
            loadPayslipEmailStatus();
        });

        $("#btnTogglePayslipEmailSettings").on("click", function () {
            $("#payslipEmailSettingsForm").toggleClass("d-none");
        });

        $("#btnSavePayslipEmailSettings").on("click", function () {
            axios.post("/payroll/payslip-email/settings", {
                password_source: $("#selPayslipPasswordSource").val(),
                auto_send_on_approval: $("#chkAutoSendOnApproval").is(":checked"),
            }).then(() => {
                $("#payslipEmailSettingsForm").addClass("d-none");
                loadPayslipEmailSettings();
            }).catch(err => {
                Swal.fire("Error", err.response?.data?.message || "Could not save settings.", "error");
            });
        });

        $("#btnSendPayslipEmails").on("click", function () {
            const params = payslipEmailParams();
            if (!params.pay_date) {
                showAlert("Select a pay date first.", "warning", "No Pay Date");
                return;
            }
            Swal.fire({
                icon: "question",
                title: "Email payslips?",
                html: `Pay date: <strong>${params.pay_date}</strong><br>This sends a password-protected PDF to every matching employee's email on file.`,
                showCancelButton: true,
                confirmButtonText: "Yes, send",
                confirmButtonColor: "#008080",
            }).then(r => {
                if (!r.isConfirmed) return;
                axios.post("/payroll/payslip/send", params)
                    .then(res => {
                        Swal.fire({ icon: "success", title: "Queued", text: res.data.message, timer: 1800, showConfirmButton: false });
                        setTimeout(loadPayslipEmailStatus, 1200);
                    })
                    .catch(err => {
                        Swal.fire("Error", err.response?.data?.message || "Unable to send payslip emails.", "error");
                    });
            });
        });

        $(document).on("click", ".btn-resend-payslip", function () {
            const payrollId = $(this).data("payroll-id");
            const $btn = $(this).prop("disabled", true).text("Sending…");
            axios.post(`/payroll/payslip/${payrollId}/resend`)
                .then(() => { setTimeout(loadPayslipEmailStatus, 1000); })
                .catch(err => {
                    Swal.fire("Error", err.response?.data?.message || "Unable to resend.", "error");
                    $btn.prop("disabled", false).text("Resend");
                });
        });
    }
});

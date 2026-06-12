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
                                margin-bottom: 20px;
                                padding-bottom: 10px;
                                border-bottom: 2px solid #008080;
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
                            tbody tr:nth-child(even) {
                                background-color: #f9f9f9;
                            }
                            tbody tr:hover {
                                background-color: #ffe5e5;
                            }
                            .footer {
                                margin-top: 25px;
                                font-size: 0.8rem;
                                text-align: right;
                                font-style: italic;
                                color: #555;
                            }
                            @media print {
                                body { padding: 0; }
                                table { page-break-inside: auto; }
                                tr { page-break-inside: avoid; page-break-after: auto; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="report-header">
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
                        <div class="footer">
                            <i>Generated from Payroll System</i>
                        </div>
                    </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
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

            // ── 5. Open print window ─────────────────────────────────────────
            const printWindow = window.open("", "", "width=1400,height=900");

            printWindow.document.write(`
                <html>
                    <head>
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
                                padding-bottom: 10px;
                                border-bottom: 2px solid #008080;
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
                                margin-top: 30px;
                                font-size: 0.78rem;
                                text-align: right;
                                font-style: italic;
                                color: #777;
                            }

                            @media print {
                                body { padding: 0; }
                                .employee-block { page-break-inside: avoid; }
                                tr { page-break-inside: avoid; page-break-after: auto; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="report-header">
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

                        <div class="footer">
                            <i>Generated from Payroll System</i>
                        </div>
                    </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
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

    // ✅ Fetch Payroll Data
    function fetchPayroll() {
        const dateFrom = $("#date_from").val();
        const dateTo = $("#date_to").val();
        const payDate = $("#pay_date").val();

        // ✨ ADD THIS: Kunin ang values ng bagong filters ✨
        const companyId = $("#selCompany").val() || "all";
        const classificationId = $("#selFilter").val() || "all";
        const departmentId = $("#selDepartment").val() || "all";

        $payrollTableBody.html('<tr><td colspan="24">Loading...</td></tr>');

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
                        `<tr><td colspan="24">${data.message || "Error fetching payroll data."}${data.error ? " (" + data.error + ")" : ""}</td></tr>`,
                    );
                    return;
                }

                if (!Array.isArray(data) || data.length === 0) {
                    $payrollTableBody.html(
                        '<tr><td colspan="24">No payroll data found.</td></tr>',
                    );
                    return;
                }

                $.each(data, function (index, payroll) {
                    const row = `
                    <tr>
                        <td class="ps-4">${index + 1}</td>
                        <td>
                            ${((payroll.employee?.fname || "") + " " + (payroll.employee?.lname || ""))
                                .toLowerCase()
                                .replace(/\b\w/g, (char) => char.toUpperCase())}
                            <br><a href="/payroll/payslip?pay_date=${encodeURIComponent(payDate)}&employee_id=${encodeURIComponent(payroll.employee_id || '')}" target="_blank" class="badge bg-secondary text-white text-decoration-none mt-1 d-inline-block" style="font-size:10px;"><i class="fa fa-file-invoice me-1"></i>Payslip</a>
                        </td>
                            <td>${formatNumber(payroll.basic_salary)}</td>
                            <td>${formatNumber(payroll.basicPay)}</td>
                            <td>${formatNumber(payroll.abs_ut_deduction || 0)}</td>

                            <td>${formatNumber(payroll.overtime_pay || 0)}</td>
                            <td>${formatNumber(payroll.night_diff_pay || 0)}</td>

                            <td class="bg-light fw-bold">${formatNumber(payroll.gross_pay || 0)}</td>

                            <td>${formatNumber(payroll.sss_contribution || 0)}</td>
                            <td>${formatNumber(payroll.sss_loan || 0)}</td>
                            <td>${formatNumber(payroll.pagibig_contribution || 0)}</td>
                            <td>${formatNumber(payroll.pagibig_loan || 0)}</td>
                            <td>${formatNumber(payroll.philhealth_contribution || 0)}</td>

                            <td>${formatNumber(payroll.taxable_income || 0)}</td>
                            <td>${formatNumber(payroll.withholding_tax || 0)}</td>

                            <td>${formatNumber(payroll.allowances || 0)}</td>
                            <td>${formatNumber(payroll.adjustment || 0)}</td>

                            <td>${formatNumber(payroll.penalty_amount || 0)}</td>
                            <td>${formatNumber(payroll.company_loan || 0)}</td>
                            <td class="bg-light fw-bold">${formatNumber(payroll.net_pay || 0)}</td>
                            <td class="pe-4 fw-bold">${formatNumber(payroll.pay_rec || 0)}</td>
                    </tr>
                    `;
                    $payrollTableBody.append(row);
                });
            })
            .catch(function (error) {
                console.error(error);
                const apiError = error.response?.data;
                $payrollTableBody.html(
                    `<tr><td colspan="24">${apiError?.message || "Error fetching payroll data."}${apiError?.error ? " (" + apiError.error + ")" : ""}</td></tr>`,
                );
            });
    }

    // ✅ Bind event: Fetch Payroll
    $("#btnPayroll").on("click", fetchPayroll);
    $("#selCompany, #selFilter, #selDepartment").on("change", fetchPayroll);

    // ✅ Bulk: open printable payslips for everyone in the current pay date (respects filters)
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
                    showAlert(
                        error.response?.data?.message || error.response?.data?.error || "An error occurred while generating payroll.",
                        "error",
                        "Error"
                    );
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

    // Helper: Check if selected date is valid pay date (15th or last day)
    const isValidPayDate = (dateStr) => {
        const date = new Date(dateStr);
        if (isNaN(date)) return false;
        const day = date.getDate();
        const lastDay = new Date(
            date.getFullYear(),
            date.getMonth() + 1,
            0,
        ).getDate();
        return day === 15 || day === lastDay;
    };

    // ✅ When pay date changes
    payDateInput.on("change", function () {
        const selectedDate = $(this).val();

        if (!isValidPayDate(selectedDate)) {
            Swal.fire({
                icon: "warning",
                title: "Invalid Pay Date",
                text: "Only the 15th and the end of the month are valid pay dates.",
                confirmButtonColor: "#008080",
            }).then(() => {
                $(this).val(""); // Clear invalid date
                dateFromInput.val("");
                dateToInput.val("");
            });
            return;
        }

        const payDate = new Date(selectedDate);
        const year = payDate.getFullYear();
        const month = payDate.getMonth();
        const day = payDate.getDate();
        let dateFrom, dateTo;

        if (day === 15) {
            // ✅ 26 of previous month → 10 of current month
            dateFrom = new Date(year, month - 1, 26);
            dateTo = new Date(year, month, 10);
        } else {
            // ✅ 11 → 25 of current month
            dateFrom = new Date(year, month, 11);
            dateTo = new Date(year, month, 25);
        }

        // ✅ Auto-fill cut-off dates
        dateFromInput.val(formatDate(dateFrom));
        dateToInput.val(formatDate(dateTo));
    });

    // ✅ Auto-trigger if pay_date already filled
    if (payDateInput.val()) {
        payDateInput.trigger("change");
    }

    
});

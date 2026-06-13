document.addEventListener('DOMContentLoaded', function () {

    

    // Refresh button
    document.getElementById('btn_rptrefresh').addEventListener('click', fetchAttendance);

    // Print button
    document.getElementById('btn_rptprint').addEventListener('click', () => {
    const printArea = document.getElementById('Report_thisPrint').innerHTML;
    const printWindow = window.open('', '_blank', 'width=1000,height=800');

    const fromDate = document.getElementById('txtDateFrom').value;
    const toDate = document.getElementById('txtDateTo').value;
    
    const printTimestamp = new Date().toLocaleDateString('en-US', { 
        month: 'short', day: '2-digit', year: 'numeric', 
        hour: '2-digit', minute: '2-digit' 
    });

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>Attendance Viewer</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                
                <style>
                    /* 📄 Page Configuration (Landscape & 0.5 Margin) */
                    @page { 
                        size: landscape; 
                        margin: 0.5in; 
                    }
                    
                    body { 
                        font-family: 'Segoe UI', Roboto, Arial, sans-serif; 
                        color: #000 !important; 
                        background: white !important;
                    }
                    
                    /* 🚀 Force remove ALL colors from Bootstrap classes */
                    * {
                        color: #000 !important;
                        background-color: transparent !important;
                    }
                    
                    /* 🏢 Header Styling */
                    .print-header { 
                 
                        padding-bottom: 5px; /* Binawasan ko rin ang padding dito para mas masiksik sa taas */
                        margin-bottom: 15px;
                    }
                    .report-title {
                        font-size: 24px;
                        font-weight: bold;
                        text-transform: uppercase;
                        margin: 0;
                        letter-spacing: 1px;
                    }
                    
                    /* 📅 Metadata Table */
                    .meta-table { width: 100%; margin-bottom: 15px; font-size: 13px; }
                    .meta-table td { padding: 4px 0; }
                    
                    /* 📊 Table Adjustments for Print Quality */
                    #Report_thisPrint table, table { 
                        width: 100% !important; 
                        border-collapse: collapse !important; 
                        font-size: 12px !important; 
                    }
                    th { 
                        text-transform: uppercase; 
                        font-size: 11px !important; 
                        padding: 8px !important; /* Binawasan ng konti para mas kumasiya sa landscape */
                        border-bottom: 1px solid #000 !important;
                    }
                    td { 
                        padding: 6px 8px !important; /* Binawasan ng konti ang space para mas maraming rows ang magkasya */
                        border-bottom: 1px solid #ccc !important; 
                        vertical-align: middle;
                    }
                    
                    /* Clean up badges */
                    .badge { 
                        border: 1px solid #666 !important; 
                        font-weight: normal !important;
                        padding: 4px 8px !important;
                    }
                    
                    /* Hide unnecessary UI elements */
                    .btn, .spinner-border, input, .toggle-password { display: none !important; }
                </style>
            </head>
            <body>
                <div class="print-header d-flex justify-content-between align-items-end">
                    <div>
                        <h1 class="report-title">Attendance Viewer</h1>
                    </div>
                    <div class="text-end" style="font-size: 11px;">
                        <strong>GENERATED ON</strong><br>
                        ${printTimestamp}
                    </div>
                </div>

                <table class="meta-table">
                    <tr>
                        <td style="width: 100px;"><strong>Date Range:</strong></td>
                        <td>${fromDate} &nbsp;to&nbsp; ${toDate}</td>
                    </tr>
                </table>

                <div id="print-content">
                    ${printArea}
                </div>

                <div style="margin-top: 30px; font-size: 11px; text-align: center; text-transform: uppercase;">
                    <p>*** End of Report ***</p>
                </div>

                <script>
                    window.onload = function() {
                        setTimeout(() => {
                            window.focus();
                            window.print();
                            window.close();
                        }, 500);
                    };
                </script>
            </body>
        </html>
    `);
    printWindow.document.close();
});

        const btnRefresh = $("#btn_rptrefresh");
    const empSelect = $("#txtLastname");
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

    // Updated colspan to 13 to match the new columns
    tableBody.html(`<tr><td colspan="13" class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>`);

    axios.post("/attendance/fetch", {
        empID: empID,
        dateFrom: from,
        dateTo: to,
    })
    .then(response => {
        const res = response.data;
        if (res.status === "success") {
            renderTable(res.data);
        } else {
            tableBody.html(`<tr><td colspan="13" class="text-center text-danger">No records found</td></tr>`);
        }
    })
    .catch(error => {
        console.error(error);
        tableBody.html(`<tr><td colspan="13" class="text-center text-danger">Error fetching data</td></tr>`);
    });
}

function renderTable(data) {
    if (!data.length) {
        tableBody.html(`<tr><td colspan="13" class="text-center">No records found</td></tr>`);
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
                logRows = item.logs.map(log => `${formatTime(log.time_in)} - ${formatTime(log.time_out)}`).join("<br>");
            }

            const late = parseInt(item.mins_late ?? 0);
            const ut   = parseInt(item.mins_undertime ?? 0);
            const nd   = parseInt(item.mins_night_diff ?? 0);
            const pass = parseInt(item.outpass_minutes ?? 0);
            const ob   = parseInt(item.over_break_minutes ?? 0);

            sub.gross += grossHours; sub.ded += totalDeductedMins; sub.net += netHours;
            sub.late += late; sub.ut += ut; sub.nd += nd; sub.pass += pass; sub.ob += ob;

            no++;
            rows += `
                <tr>
                    <td>${no}</td>
                    <td class="text-start text-capitalize">${capitalizeName(empName)}</td>
                    <td>${item.formatted_date ?? '-'}</td>
                    <td colspan="2">${logRows}</td>
                    <td class="fw-bold">${grossHours.toFixed(2)}</td>
                    <td class="text-danger fw-bold">${totalDeductedMins > 0 ? totalDeductedMins + 'm' : '-'}</td>
                    <td class="text-primary fw-bold">${netHours.toFixed(2)}</td>
                    <td>${m(late)}</td>
                    <td>${m(ut)}</td>
                    <td>${m(nd)}</td>
                    <td>${m(pass)}</td>
                    <td>${m(ob)}</td>
                </tr>
            `;
        });

        // Per-employee TOTAL row (totals start from the Duration column)
        rows += `
            <tr class="fw-bold" style="background:#eef6f6;">
                <td colspan="5" class="text-end">TOTAL &mdash; ${capitalizeName(empName)} <span style="font-weight:normal;color:#6b7280;">(${items.length} day${items.length > 1 ? 's' : ''} attended)</span></td>
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
                <td colspan="5" class="text-end">GRAND TOTAL <span style="font-weight:normal;color:#6b7280;">(${data.length} total attendance)</span></td>
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

    fetchAttendance();
    
});

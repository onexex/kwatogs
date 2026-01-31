document.addEventListener('DOMContentLoaded', function () {

    

    // Refresh button
    document.getElementById('btn_rptrefresh').addEventListener('click', fetchAttendance);

    // Print button
    document.getElementById('btn_rptprint').addEventListener('click', () => {
        const printArea = document.getElementById('Report_thisPrint').innerHTML;
        const printWindow = window.open('', '_blank', 'width=900,height=600');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Attendance Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                        th { background-color: #f2f2f2; }
                        .fw-bold { font-weight: bold; }
                        .bg-secondary { background-color: #495057; color: #fff; }
                        .bg-warning { background-color: #ffeeba; }
                        .bg-light { background-color: #f8f9fa; }
                        h2 { text-align: center; margin-bottom: 10px; }
                    </style>
                </head>
                <body>
                    <h2>Attendance Report</h2>
                    <p><strong>Date Range:</strong> ${document.getElementById('txtDateFrom').value} - ${document.getElementById('txtDateTo').value}</p>
                    ${printArea}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
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

    let rows = "";
    data.forEach((item, i) => {
        const empName = item.employee ? `${item.employee.lname}, ${item.employee.fname}` : "N/A";

        // 1. Calculate Manual Deductions
        let totalDeductedMins = 0;
        if (item.manual_deductions && item.manual_deductions.length > 0) {
            totalDeductedMins = item.manual_deductions.reduce((sum, d) => sum + parseInt(d.deduction_minutes || 0), 0);
        }

        // 2. Calculate Net Hours (Gross - [Mins/60])
        let grossHours = parseFloat(item.total_hours ?? 0);
        let netHours = (grossHours - (totalDeductedMins / 60)).toFixed(2);

        // Build logs list
        let logRows = "-";
        if (item.logs && item.logs.length > 0) {
            const logArray = item.logs.map(log => {
                return `${formatTime(log.time_in)} - ${formatTime(log.time_out)}`;
            });
            logRows = logArray.join("<br>");
        }

        rows += `
            <tr>
                <td>${i + 1}</td>
                <td class="text-start">${empName}</td>
                <td>${item.formatted_date ?? '-'}</td>
                <td colspan="2">${logRows}</td>
                <td class="fw-bold">${grossHours.toFixed(2)}</td>
                <td class="text-danger fw-bold">${totalDeductedMins > 0 ? totalDeductedMins + 'm' : '-'}</td>
                <td class="text-primary fw-bold">${netHours}</td>
                <td>${item.mins_late ?? 0}m</td>
                <td>${item.mins_undertime ?? 0}m</td>
                <td>${item.mins_night_diff ?? 0}m</td>
                <td>${item.outpass_minutes ?? 0}m</td>
                <td>${item.over_break_minutes ?? 0}m</td>
            </tr>
        `;
    });

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

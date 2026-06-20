$(document).ready(function () {
    // Ensure CSRF + AJAX headers for the POST toggles
    axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
    axios.defaults.headers.common["X-CSRF-TOKEN"] = $('meta[name="csrf-token"]').attr("content");

    var allRows = [];

    loadEmployees();

    function loadEmployees() {
        axios.get("/govdues/get_all")
            .then(function (res) {
                allRows = res.data.data || [];
                buildCompanyFilter(allRows);
                render();
            })
            .catch(function (err) {
                $("#gdBody").html(
                    "<tr><td colspan='7' class='text-center py-5 text-danger'>Could not load employees. " +
                    (err.message || "") + "</td></tr>"
                );
            });
    }

    function buildCompanyFilter(rows) {
        var companies = [];
        rows.forEach(function (r) {
            if (r.company && companies.indexOf(r.company) === -1) companies.push(r.company);
        });
        companies.sort();
        var html = "<option value=''>All Companies</option>";
        companies.forEach(function (c) {
            html += "<option value='" + escapeHtml(c) + "'>" + escapeHtml(c) + "</option>";
        });
        $("#gdCompanyFilter").html(html);
    }

    function render() {
        var term = ($("#gdSearch").val() || "").toLowerCase().trim();
        var company = $("#gdCompanyFilter").val() || "";

        var rows = allRows.filter(function (r) {
            var matchTerm = !term ||
                (r.name && r.name.toLowerCase().indexOf(term) !== -1) ||
                (r.empID && String(r.empID).toLowerCase().indexOf(term) !== -1);
            var matchCo = !company || r.company === company;
            return matchTerm && matchCo;
        });

        $("#gdCount").text(rows.length + " of " + allRows.length + " employees");

        if (!rows.length) {
            $("#gdBody").html("<tr><td colspan='7' class='text-center py-5 text-muted small'>No employees match your filter.</td></tr>");
            return;
        }

        var html = "";
        rows.forEach(function (r) {
            html +=
                "<tr data-id='" + r.id + "'>" +
                    "<td class='ps-4'>" +
                        "<div class='gd-emp-name'>" + escapeHtml(r.name) + "</div>" +
                        "<div class='gd-emp-id'>" + escapeHtml(r.empID || "") + "</div>" +
                    "</td>" +
                    "<td>" + escapeHtml(r.company || "—") + "</td>" +
                    "<td><span class='gd-chip'>" + escapeHtml(r.classification || "—") + "</span></td>" +
                    switchCell(r, "sss", r.sss_enabled) +
                    switchCell(r, "philhealth", r.philhealth_enabled) +
                    switchCell(r, "pagibig", r.pagibig_enabled) +
                    "<td class='text-center pe-4'>" +
                        "<button type='button' class='gd-rowbtn gd-all' data-id='" + r.id + "' data-on='1'>All ON</button> " +
                        "<button type='button' class='gd-rowbtn gd-all' data-id='" + r.id + "' data-on='0'>All OFF</button>" +
                    "</td>" +
                "</tr>";
        });
        $("#gdBody").html(html);
    }

    function switchCell(r, due, on) {
        return "<td class='text-center'>" +
            "<label class='gd-switch'>" +
                "<input type='checkbox' class='gd-toggle' data-id='" + r.id + "' data-due='" + due + "' " + (on ? "checked" : "") + ">" +
                "<span class='gd-slider'></span>" +
            "</label>" +
        "</td>";
    }

    // ── Single toggle ──
    $(document).on("change", ".gd-toggle", function () {
        var $cb = $(this);
        var id = $cb.data("id");
        var due = $cb.data("due");
        var enabled = $cb.is(":checked");
        $cb.prop("disabled", true).closest(".gd-switch").addClass("busy");

        axios.post("/govdues/toggle", { id: id, due: due, enabled: enabled })
            .then(function () {
                updateLocal(id, due, enabled);
                toast(enabled ? "Enabled" : "Disabled");
            })
            .catch(function (err) {
                $cb.prop("checked", !enabled); // revert on failure
                Swal.fire({ icon: "error", title: "Update failed", text: (err.response && err.response.data && err.response.data.msg) || err.message });
            })
            .then(function () {
                $cb.prop("disabled", false).closest(".gd-switch").removeClass("busy");
            });
    });

    // ── Row quick-set (all on / all off) ──
    $(document).on("click", ".gd-all", function () {
        var id = $(this).data("id");
        var on = String($(this).data("on")) === "1";

        axios.post("/govdues/toggle-all", { id: id, enabled: on })
            .then(function () {
                ["sss", "philhealth", "pagibig"].forEach(function (d) { updateLocal(id, d, on); });
                $("tr[data-id='" + id + "'] .gd-toggle").prop("checked", on);
                toast("All government dues " + (on ? "enabled" : "disabled"));
            })
            .catch(function (err) {
                Swal.fire({ icon: "error", title: "Update failed", text: err.message });
            });
    });

    function updateLocal(id, due, enabled) {
        var row = allRows.find(function (r) { return String(r.id) === String(id); });
        if (row) row[due + "_enabled"] = enabled;
    }

    $("#gdSearch").on("input", render);
    $("#gdCompanyFilter").on("change", render);

    function toast(msg) {
        Swal.fire({ toast: true, position: "top-end", icon: "success", title: msg, timer: 1400, showConfirmButton: false });
    }

    function escapeHtml(s) {
        return String(s == null ? "" : s)
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});

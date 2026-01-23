$(document).ready(function () {
    var formAction = 0;
    var classID = 0;
    get_classification();

    function get_classification() {
        axios
            .get("/classification/get_all")
            .then(function (response) {
                var htmlData = "";
                var i = 1;
                var resultData = response.data.data;

                if (resultData.length > 0) {
                    $(resultData).each(function (index, row) {
                        htmlData +=
                            "<tr>" +
                            // Index column with modern alignment
                            "<td class='ps-4 text-center fw-medium text-secondary'>" +
                            i++ +
                            "</td>" +
                            // Bold code for better scannability
                            "<td class='fw-bold text-dark'>" +
                            row.class_code.toUpperCase() +
                            "</td>" +
                            // Description in slightly muted text
                            "<td class='text-muted'>" +
                            row.class_desc.toUpperCase() +
                            "</td>" +
                            // Modern Action Buttons (Circle style)
                            "<td class='pe-4 text-end'>" +
                            "<div class='d-flex justify-content-end gap-2'>" +
                            // Edit Button
                            "<button type='button' value='" +
                            row.id +
                            "' class='btn btn-light btn-sm rounded-circle shadow-sm p-2' id='btnUpdateClassification' data-bs-toggle='modal' data-bs-target='#mdlClassification' title='Edit'>" +
                            "<i class='fa-solid fa-pencil text-primary'></i>" +
                            "</button>" +
                            // Delete Button
                            "<button type='button' value='" +
                            row.id +
                            "' class='btn btn-light btn-sm rounded-circle shadow-sm p-2' id='btnDeleteClassification' title='Delete'>" +
                            "<i class='fa-solid fa-trash text-danger'></i>" +
                            "</button>" +
                            "</div>" +
                            "</td>" +
                            "</tr>";
                    });
                } else {
                    htmlData =
                        "<tr><td colspan='4' class='text-center py-5 text-muted small'>No classifications found.</td></tr>";
                }

                $("#tblClasification").empty().append(htmlData);

                // Re-initialize tooltips if you are using them
                var tooltipTriggerList = [].slice.call(
                    document.querySelectorAll('[data-bs-toggle="tooltip"]'),
                );
                var tooltipList = tooltipTriggerList.map(
                    function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    },
                );
            })
            .catch(function (error) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Could not fetch data: " + error,
                });
            });
    }

    $(document).on("click", "#btnCreateClassification", function (e) {
        formAction = 1;
        $(".lblActionDesc").text("Creating Classification");
        $("#frmCreateClassification")[0].reset();
        $("span.error-text").text("");
        $("input.border").removeClass("border border-danger");
    });

    $(document).on("click", "#btnUpdateClassification", function (e) {
        formAction = 2;
        classID = $(this).val();
        $("span.error-text").text("");
        $("input.border").removeClass("border border-danger");
        $(".lblActionDesc").text("Updating Classification");
        // $('#frmCreateClassification')[0].reset();
        axios
            .get("/classification/edit", {
                params: {
                    classID: classID,
                },
            })
            .then(function (response) {
                $(response.data.data).each(function (index, row) {
                    $("#txtClassificationCode").val(row.class_code);
                    $("#txtClassificationDesc").val(row.class_desc);
                });
            })
            .catch(function (error) {
                dialog.alert({
                    message: error,
                });
            })
            .then(function () {});
    });

    $(document).on("click", "#btnSaveClassification", function (e) {
        e.preventDefault();

        var datas = $("#frmCreateClassification");
        var formData = new FormData($(datas)[0]);
        formData.append("formAction", formAction);
        formData.append("classID", classID);

        // 1. Show Loading Animation
        Swal.fire({
            title: "Saving changes...",
            text: "Please wait while we update the records.",
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        axios
            .post("/classification/create_update", formData)
            .then(function (response) {
                // Clear previous error states
                $("span.error-text").text("");
                $("input").removeClass("border border-danger");

                // 2. Handle Validation Errors (Status 201)
                if (response.data.status == 201) {
                    Swal.close(); // Close the loader so user can see errors
                    $.each(response.data.error, function (prefix, val) {
                        $("input[name=" + prefix + "]").addClass(
                            "border border-danger",
                        );
                        $("span." + prefix + "_error").text(val[0]);
                    });
                }

                // 3. Handle Success (Status 200 or 202)
                if (
                    response.data.status == 200 ||
                    response.data.status == 202
                ) {
                    // Refresh the table
                    get_classification();

                    // Clear form if it was a new entry (Status 200)
                    if (response.data.status == 200) {
                        $("#frmCreateClassification")[0].reset();
                    }

                    // Show Success Message and Close Modal
                    Swal.fire({
                        icon: "success",
                        title: "Success!",
                        text: response.data.msg,
                        timer: 2000,
                        showConfirmButton: false,
                    }).then(() => {
                        $("#mdlClassification").modal("hide");
                    });
                }
            })
            .catch(function (error) {
                // Handle System Errors
                Swal.fire({
                    icon: "error",
                    title: "Request Failed",
                    text:
                        error.message ||
                        "Something went wrong with the server connection.",
                });
            });
    });

    $(document).on("click", "#btnDeleteClassification", function (e) {
        const id = $(this).val();

        // 1. Trigger Confirmation Dialog
        Swal.fire({
            title: "Are you sure?",
            text: "You are about to remove this classification. This action cannot be undone!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33", // Red color for delete
            cancelButtonColor: "#6c757d", // Grey color for cancel
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "Cancel",
            reverseButtons: true, // Places "Cancel" on the left and "Delete" on the right
        }).then((result) => {
            if (result.isConfirmed) {
                // 2. Show "Deleting" Spinner
                Swal.fire({
                    title: "Deleting...",
                    text: "Please wait while we process your request.",
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                });

                // 3. Execute Axios Request
                axios
                    .get("/classification/delete", {
                        params: { id: id },
                    })
                    .then(function (response) {
                        // 4. Show Success Toast/Alert
                        Swal.fire({
                            icon: "success",
                            title: "Deleted!",
                            text:
                                response.data.msg ||
                                "The record has been removed.",
                            timer: 2000,
                            showConfirmButton: false,
                        });

                        // Refresh the table design
                        get_classification();
                    })
                    .catch(function (error) {
                        // 5. Handle Error
                        Swal.fire({
                            icon: "error",
                            title: "Error!",
                            text:
                                "An error occurred while deleting: " +
                                error.message,
                        });
                    });
            }
        });
    });
});

$(document).ready(function() {
    var formAction=0;
    var now = moment().format("YYYY-MM-DD");
    var updateID = '';

    getusers();

    function getusers(){
        axios.get('/sil/getusers')
        .then(function(response){

            var resultData = response.data.data;
            var stat = response.data.status;
            var select = '';

            if(stat == 1)
            {
                // select += "<option>" + "---" + "</option>";
                $(resultData).each(function(index, item){

                    select += '<option value='+ item.empID +'>' + item.fname + " " + item.lname + '</option>';
                });
                $("#selEmployee").empty().append(select);
            }
        })
        .catch(function(error){
            dialog.alert({
                message: error
            });
        })
        .then(function(response){})
    }

    $(document).on('click', '#btnAddSil', function(e)
    {
        formAction = 1;
        $('#frmSil')[0].reset();
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
    });

    $(document).on('click', '#btnUpdateSILLoan', function(e)
    {
        formAction = 2;
        $(".lblActionDesc").html("Update SIL Loan");
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');

        updateID = $(this).val();

        axios.get('/sil/edit', {
            params: {
                updateID: updateID,
            }
        })
        .then(function(response){

            var resultData = response.data.data;
            var stat = response.data.status;

            if(stat == 200)
            {
                $(resultData).each(function(index, item){
                    $('#selEmployee').val(item.silEmpID);
                    $('#numLoan').val(item.silAmount);
                    $("#selLoanType").val(item.silType);
                    $('#selStatus').val(item.silStatus);
                });
                $('#btnSaveSil').val(updateID);

            }
        })
        .catch(function(error){
            dialog.alert({
                message: error,
            });
        })
        .then(function(response){})
    });

    // btnSaveSil
    $(document).on('click', '#btnSaveSil', function(e){

        var datas = $('#frmSil');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('updateID', updateID);
        formData.append('now', now);

        axios.post('/sil/create_update',formData)
        .then(function(response){

            //error response
            if (response.data.status == 201) {
                $.each(response.data.error, function(prefix, val) {
                    $('input[name='+ prefix +']').addClass(" border border-danger") ;
                    $('span.' + prefix + '_error').text(val[0]);
                });
            }
            //success respose
            if(response.data.status == 200){
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('#frmSil')[0].reset();
                getall();
                dialog.alert({
                    message: response.data.msg
                });
            }
             //success respose
             if(response.data.status == 202){
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                dialog.alert({
                    message: response.data.msg
                });
            }

        })
        .catch(function(error){
            dialog.alert({
                message: error
            });
        })
        .then(function(response){})
    });

    function getall()
    {
        axios.get('/sil/getall')
        .then(function(response){
            var dataResult = response.data.data;
            var content = "";

            $(dataResult).each(function(index, item){

                var Type;
                var stats;

                if(item.silType === "SL"){
                    Type = "Salary Loan";
                }
                else if(item.silType === "PagIbig"){
                    Type = "PAG-IBIG";
                }
                else{
                    Type = item.silType;
                }

                if(item.loanStatus == 0)
                {
                    stats = "INACTIVE";
                }
                else{
                    stats = "ACTIVE";
                }

                content += '<tr>' +
                    '<td>' + item.fname + " "+ item.lname + '</td>' +
                    '<td>' + item.silAmount + '</td>' +
                    '<td>' + Type + '</td>' +
                    '<td>' + stats + '</td>' +
                    '<td>' + '<button type="button" value='+ item.id +' class="btn btn-details" id="btnUpdateSILLoan" data-toggle="tooltip" data-placement="bottom" title=" Update Data" data-bs-toggle="modal" data-bs-target="#mdlSil"> <i class="fa fa-pencil"></i> </button>' + '</td>'
                content += '</tr>';

            });
            $('#tblSil').empty().append(content);

        })
        .catch(function(error){
            dialog.alert({
                message: error
            });
        })
        .then(function(response){})
    }

    getall();

});

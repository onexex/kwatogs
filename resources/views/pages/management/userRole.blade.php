@extends('layout.app')
@section('content')
<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3 title">User Roles</h4>

        <div class="row">
            <div class="col-lg-4">
                <form  action='' id="frmSearch">
                    <div class="form-floating mb-1">
                        <input class="form-control" id="txtSearchStr" name="lastname" type="text" placeholder="Search Employee Lastname"/>
                        <label for="txtUserRole"> Search Employee Lastname <label for="" class="text-danger">*</label></label>
                        <span class="text-danger small error-text lastname_error"></span>
                    </div>
                </form>
            </div>
        </div>

    </div>

     <!-- Content Row dar -->
     <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card   mb-4">
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area overflow-auto">
                        <div class="table-responsive fixTableHead">
                            <table class="table table-hover table-scroll sticky">
                                <thead style="background-color: #f1f1f1; ">
                                    <tr>
                                        <th scope="col">Name</th>
                                        <th scope="col">User Role</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblUserRole">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

  <!-- Modal -->
    <div class="modal fade" id="mdlUserRole" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog   modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch" >
                    <h5 class="modal-title title" id="staticBackdropLabel"><label for=""> User Role </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmUserRole">

                                <div class="form-floating mb-1">
                                    <!-- <input class="form-control" id="txtUserRole" name="role" type="text" placeholder="Role"/> -->
                                        <select  class="form-control" name="role" id="txtUserRole">
                                            <option value="1">Superuser</option>
                                            <option value="2">Admin</option>
                                            <option value="3">User</option>
                                        </select>
                                    <label for="txtUserRole"> User Role <label for="" class="text-danger">*</label></label>
                                    <span class="text-danger small error-text role_error"></span>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveUserRole" type="button" class="btn btn-details">Save Entries</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/userroles.js') }}" defer></script>
@endsection

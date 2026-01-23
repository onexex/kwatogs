@extends('layout.app')
@section('content')

<!--SHAIRA-->

<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3">Relationships</h4>
        <button class=" mt-3 btn btn-details radius-1" name="btnCreateRelation" id="btnCreateRelation" data-bs-toggle="modal" data-bs-target="#mdlRelation"> <i class="fa fa-plus"></i> Relationship</button>
    </div>

     <!-- Content Row dar -->
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card mb-4">
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area overflow-auto">
                        <div class="table-responsive fixTableHead">
                            <table class="table table-hover table-scroll sticky">
                                <thead style="background-color: #f1f1f1; ">
                                    <tr>
                                        <th class="text-dark" scope="col">Relationship</th>
                                        <th class="text-dark" scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblRelationship">
                                    <!-- <tr>
                                        <td>001</td>
                                        <td>Father</td>
                                        <td><button class="btn btn-danger btn-sm radius-1" name="btnUpdateRelation" id="btnUpdateRelation" data-bs-toggle="modal" data-bs-target="#mdlRelation"><i class="fa-solid fa-pen"></i></button></td>

                                    </tr>
                                    <tr>
                                        <td>002</td>
                                        <td>Mother</td>
                                        <td><button class="btn btn-danger btn-sm radius-1" name="btnUpdateRelation" id="btnUpdateRelation" data-bs-toggle="modal" data-bs-target="#mdlRelation"><i class="fa-solid fa-pen"></i></button></td>
                                    </tr> -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Relationship -->
    <div class="modal fade" id="mdlRelation" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header dragable_touch" >
                    <h5 class="modal-title lblActionDesc" id="staticBackdropLabel"><label for="" class="" id="lblTitleRelation"> Relationship</label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card rounded">
                        <div class="card-body ">

                            <form action="" id="frmRelation">
                                <div class="row">
                                    <div class="col-xl-12 col-lg-12">
                                        <div class="form-floating mb-1">
                                            <input class="form-control" id="txtRelationship" name="relationship" type="text" placeholder="-"/>
                                            <label  class="form-check-label" for="missionDesc">Relationship <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text relationship_error"></span>
                                        </div>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- <button type="button" class="btn btn-secondary closereset_update" data-bs-dismiss="modal">Close</button> -->
                    <button  id="btnRelationSave" type="button" class="btn btn-details">Create</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="{{ asset('js/settings/relationship.js') }}"  deffer></script>

@endsection


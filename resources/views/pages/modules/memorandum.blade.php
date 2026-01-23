@extends('layout.app')
@section('content')

{{-- JOHN MARC CASQUIO --}}

<div class="container-fluid">
    <div class="mb-5">
        <h4 class="text-gray-800 mb-3">Memorandum Generator</h4>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="form-floating mb-3">
              <input type="text" class="form-control" name="search" id="txtSearchMemo" placeholder="">
              <label for="txtSearchMemo">Search Memorandum</label>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <label for="" class="col-lg-2 col-md-2 col-sm-3">Memorandum ID : </label> <label class="col-lg-1 col-md-1 col-sm-2">2022_04</label>
    </div>

    <div class="row mt-3">

        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="form-floating mb-3">
              <input type="text" class="form-control" name="to" id="txtTo" placeholder="">
              <label for="txtTo">Enter To </label>
            </div>

            <div class="form-floating mb-3">
              <input type="text" class="form-control" name="from" id="txtFrom" placeholder="">
              <label for="txtFrom">Enter From</label>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="form-floating mb-3">
              <input type="text" class="form-control" name="date" id="txtMemoDate" placeholder="" readonly>
              <label for="txtMemoDate">Memo Date</label>
            </div>

            <div class="form-floating mb-3">
              <input type="text" class="form-control" name="subject" id="txtSubject" placeholder="">
              <label for="txtSubject">Subject</label>
            </div>
        </div>

        <label for="" class="mt-3">Body</label>
        <textarea name="body" id="txtBody" cols="10" rows="20"></textarea>

    </div>

    <button class="btn btn-info mt-2">
        <i class="fa fa-print" aria-hidden="true"></i> Save and Print
    </button>

</div>

<script src="{{ asset('js/report/memorandum.js') }}" defer></script>
@endsection    
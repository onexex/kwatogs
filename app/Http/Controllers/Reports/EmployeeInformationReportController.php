<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;

class EmployeeInformationReportController extends Controller
{
    public function index()
    {
        return view('pages.reports.employeeInformation');
    }
}
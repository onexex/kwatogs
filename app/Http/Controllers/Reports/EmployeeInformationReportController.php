<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\classification;
use App\Models\company;
use App\Models\department;
use App\Models\EmpDetail;
use App\Models\position;
use Illuminate\Http\Request;

class EmployeeInformationReportController extends Controller
{
    public function index(Request $request)
    {
        $getCompany= company::get();
        $getClassification= classification::get();
        $getDepartment= department::get();
        $getPosition= position::get();

        $query = EmpDetail::query();

        if ($request->date_from && $request->date_to) {
            $query = $query->whereBetween('empDateHired', [$request->date_from, $request->date_to]);
        }

        if ($request->classification_id) {
            $query = $query->where('empClassification', $request->classification_id);
        }

        if ($request->company_id) {
            $query = $query->where('empCompID', $request->company_id);
        }

        if ($request->department_id) {
            $query = $query->where('empDepID', $request->department_id);
        }

        if ($request->position_id) {
            $query = $query->where('empPos', $request->position_id);
        }

        $employees = $query->paginate(10)
            ->withQueryString();

        return view('pages.reports.employeeInformation', [
            'employees' => $employees,
            'companies' => $getCompany,
            'classifications' => $getClassification,
            'departments' => $getDepartment,
            'positions' => $getPosition,
        ]);
    }
}
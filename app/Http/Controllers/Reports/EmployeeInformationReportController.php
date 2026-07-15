<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\classification;
use App\Models\company;
use App\Models\department;
use App\Models\empDetail;
use App\Models\position;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeInformationReportController extends Controller
{
    private const LETTERHEAD = 'DEMO';

    private const HEADERS = [
        'NO.', 'EMP ID', 'EMPLOYEE NAME', 'SUFFIX', 'GENDER', 'CITIZENSHIP',
        'DATE OF BIRTH', 'CIVIL STATUS', 'PHONE NUMBER', 'EMAIL', 'ADDRESS',
        'COMPANY', 'CLASSIFICATION', 'DEPARTMENT', 'POSITION', 'IMMEDIATE SUPERIOR',
        'STATUS', 'DATE HIRED', 'DATE REGULAR', 'BASIC SALARY', 'ALLOWANCE',
    ];

    private function baseQuery(Request $request)
    {
        $query = empDetail::with([
            'user', 'employeeInformation', 'company',
            'classification', 'department', 'position', 'immediateSupervisor',
        ]);

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('empDateHired', [$request->date_from, $request->date_to]);
        }
        if ($request->filled('classification_id') && $request->classification_id !== 'all') {
            $query->where('empClassification', $request->classification_id);
        }
        if ($request->filled('company_id') && $request->company_id !== 'all') {
            $query->where('empCompID', $request->company_id);
        }
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $query->where('empDepID', $request->department_id);
        }
        if ($request->filled('position_id') && $request->position_id !== 'all') {
            $query->where('empPos', $request->position_id);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('empStatus', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('user', fn($q) => $q->where(function ($q2) use ($s) {
                $q2->where('fname', 'like', "%{$s}%")
                   ->orWhere('lname', 'like', "%{$s}%")
                   ->orWhere('empID', 'like', "%{$s}%");
            }));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $getCompany = company::get();
        $getClassification = classification::get();
        $getDepartment = department::get();
        $getPosition = position::get();

        $employees = $this->baseQuery($request)->paginate(10)->withQueryString();

        return view('pages.reports.employeeInformation', [
            'employees' => $employees,
            'companies' => $getCompany,
            'classifications' => $getClassification,
            'departments' => $getDepartment,
            'positions' => $getPosition,
        ]);
    }

    public function print(Request $request)
    {
        $rows = $this->baseQuery($request)->get();

        return view('pages.reports.employee_information_print', [
            'rows'    => $rows,
            'filters' => $request->all(),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->baseQuery($request)->get();

        $x = new SimpleXlsx('Employee Info');
        $x->setColumnWidths(['A'=>5, 'B'=>14, 'C'=>30, 'D'=>9, 'E'=>9, 'F'=>18, 'G'=>16, 'H'=>14, 'I'=>16, 'J'=>30, 'K'=>36, 'L'=>18, 'M'=>18, 'N'=>18, 'O'=>18, 'P'=>22, 'Q'=>12, 'R'=>14, 'S'=>14, 'T'=>16, 'U'=>14]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', 'EMPLOYEE INFORMATION REPORT', SimpleXlsx::S_TITLE);
        $x->mergeCells('A1:U1');
        $x->mergeCells('A2:U2');

        $hr = 4;
        $colLetters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U'];
        foreach ($colLetters as $i => $col) {
            $x->setString("{$col}{$hr}", self::HEADERS[$i], SimpleXlsx::S_BOLD);
        }

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $employee) {
            $n++;
            $info = $employee->employeeInformation;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) $employee->empID, SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", trim(($employee->user?->fname ?? '') . ' ' . ($employee->user?->lname ?? '')), SimpleXlsx::S_NORMAL);
            $x->setString("D{$r}", (string) ($employee->user?->suffix ?? ''), SimpleXlsx::S_NORMAL);
            $x->setString("E{$r}", (string) ($info?->gender ?? ''), SimpleXlsx::S_NORMAL);
            $x->setString("F{$r}", (string) ($info?->citizenship ?? ''), SimpleXlsx::S_NORMAL);
            $x->setString("G{$r}", $info?->empBdate ? optional(Carbon::parse($info->empBdate))->format('F d, Y') : '', SimpleXlsx::S_NORMAL);
            $x->setString("H{$r}", $this->civilStatus($info?->empCStatus), SimpleXlsx::S_NORMAL);
            $x->setString("I{$r}", (string) ($info?->empPContact ?? ''), SimpleXlsx::S_NORMAL);
            $x->setString("J{$r}", (string) ($info?->empEmail ?? ''), SimpleXlsx::S_NORMAL);
            $x->setString("K{$r}", trim(implode(' ', array_filter([
                $info?->empAddStreet ?? '',
                $info?->empAddBrgyDesc ?? '',
                $info?->empAddCityDesc ?? '',
            ]))), SimpleXlsx::S_NORMAL);
            $x->setString("L{$r}", (string) ($employee->company?->comp_name ?? '—'), SimpleXlsx::S_NORMAL);
            $x->setString("M{$r}", (string) ($employee->classification?->class_desc ?? '—'), SimpleXlsx::S_NORMAL);
            $x->setString("N{$r}", (string) ($employee->department?->dep_name ?? '—'), SimpleXlsx::S_NORMAL);
            $x->setString("O{$r}", (string) ($employee->position?->pos_desc ?? '—'), SimpleXlsx::S_NORMAL);
            $x->setString("P{$r}", trim(($employee->immediateSupervisor?->fname ?? '') . ' ' . ($employee->immediateSupervisor?->lname ?? '')), SimpleXlsx::S_NORMAL);
            $x->setString("Q{$r}", $employee->empStatus == '1' ? 'Employed' : 'Resigned', SimpleXlsx::S_NORMAL);
            $x->setString("R{$r}", $employee->empDateHired ? $employee->empDateHired->format('F d, Y') : '', SimpleXlsx::S_NORMAL);
            $x->setString("S{$r}", $employee->empDateRegular ? $employee->empDateRegular->format('F d, Y') : '', SimpleXlsx::S_NORMAL);
            $x->setNumber("T{$r}", (float) ($employee->empBasic ?? 0), SimpleXlsx::S_MONEY);
            $x->setNumber("U{$r}", (float) ($employee->empAllowance ?? 0), SimpleXlsx::S_MONEY);
            $r++;
        }

        $path = $x->saveToTempFile();

        return response()->download($path, 'employee_information.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function civilStatus(?string $code): string
    {
        return match ($code) {
            '0' => 'Single',
            '1' => 'Married',
            '2' => 'Divorced',
            default => 'N/A',
        };
    }
}
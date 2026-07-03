<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\department;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * COE Issuance Log — every Certificate of Employment request with its status,
 * certificate number, purpose, signatory and review trail. A register/audit view
 * over `coe_requests` (complements the operational COE screen).
 */
class CoeIssuanceReportController extends Controller
{
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

    public function index()
    {
        return view('pages.reports.coe_log', [
            'departments' => department::orderBy('dep_name')->get(),
            'companies'   => DB::table('companies')->orderBy('comp_name')->get(),
            'years'       => range((int) now()->year, (int) now()->year - 5),
        ]);
    }

    private function compute(Request $request): Collection
    {
        $q = DB::table('coe_requests as c')
            ->join('users as u', 'u.empID', '=', 'c.employee_id')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'c.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->leftJoin('users as rv', 'rv.id', '=', 'c.reviewed_by');

        if ($request->filled('status') && $request->status !== 'all') {
            $q->where('c.status', $request->status);
        }
        if ($request->filled('year') && $request->year !== 'all') {
            $q->whereYear('c.created_at', (int) $request->year);
        }
        if ($request->filled('company_id') && $request->company_id !== 'all') {
            $q->where('ed.empCompID', $request->company_id);
        }
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $q->where('ed.empDepID', $request->department_id);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('u.fname', 'like', "%{$s}%")
                  ->orWhere('u.lname', 'like', "%{$s}%")
                  ->orWhere('u.empID', 'like', "%{$s}%")
                  ->orWhere('c.certificate_no', 'like', "%{$s}%");
            });
        }

        return $q->selectRaw("
                c.id, c.certificate_no,
                u.empID as employee_id,
                TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name,
                COALESCE(d.dep_name,'—') as department_name,
                c.purpose, c.copies, c.status, c.include_salary, c.date_needed,
                c.signatory_name, c.signatory_title,
                TRIM(CONCAT(COALESCE(rv.lname,''), ', ', COALESCE(rv.fname,''))) as reviewer,
                c.reviewed_at, c.rejection_reason, c.created_at
            ")
            ->orderByDesc('c.created_at')
            ->get()
            ->map(function ($r) {
                $r->include_salary = (bool) $r->include_salary;
                return $r;
            });
    }

    private function stats(Collection $rows): array
    {
        return [
            'total'    => $rows->count(),
            'pending'  => $rows->where('status', 'pending')->count(),
            'approved' => $rows->where('status', 'approved')->count(),
            'rejected' => $rows->where('status', 'rejected')->count(),
        ];
    }

    public function fetch(Request $request)
    {
        $rows = $this->compute($request);
        return response()->json(['data' => $rows, 'stats' => $this->stats($rows)]);
    }

    public function export(Request $request)
    {
        $rows = $this->compute($request);

        $x = new SimpleXlsx('COE Log');
        $x->setColumnWidths([5, 16, 12, 28, 22, 26, 10, 10, 22, 16]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', 'CERTIFICATE OF EMPLOYMENT — ISSUANCE LOG', SimpleXlsx::S_TITLE);

        $hr = 4;
        $headers = ['NO.', 'CERT NO.', 'EMP ID', 'EMPLOYEE NAME', 'DEPARTMENT', 'PURPOSE', 'COPIES', 'SALARY?', 'SIGNATORY', 'STATUS'];
        $cols = range('A', 'J');
        foreach ($headers as $i => $h) { $x->setString("{$cols[$i]}{$hr}", $h, SimpleXlsx::S_BOLD); }

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) ($row->certificate_no ?: '—'), SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", (string) $row->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("D{$r}", strtoupper((string) $row->employee_name), SimpleXlsx::S_NORMAL);
            $x->setString("E{$r}", (string) $row->department_name, SimpleXlsx::S_NORMAL);
            $x->setString("F{$r}", (string) $row->purpose, SimpleXlsx::S_NORMAL);
            $x->setNumber("G{$r}", (float) $row->copies, SimpleXlsx::S_NORMAL);
            $x->setString("H{$r}", $row->include_salary ? 'Yes' : 'No', SimpleXlsx::S_NORMAL);
            $x->setString("I{$r}", (string) ($row->signatory_name ?: '—'), SimpleXlsx::S_NORMAL);
            $x->setString("J{$r}", ucfirst((string) $row->status), SimpleXlsx::S_NORMAL);
            $r++;
        }

        $path = $x->saveToTempFile();
        return response()->download($path, 'COE_Issuance_Log_' . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        $rows = $this->compute($request);
        return view('pages.reports.coe_log_print', [
            'rows'       => $rows,
            'stats'      => $this->stats($rows),
            'letterhead' => self::LETTERHEAD,
        ]);
    }
}

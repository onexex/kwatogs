<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\department;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Leave Ledger / Balance — per-employee, per-leave-type credit allocation, used,
 * and remaining balance for a year, cross-referenced with the leave days actually
 * filed (approved `leave_details`). Rows are the union of allocations and filed
 * leave, so employees who filed leave without a tracked allocation still appear.
 */
class LeaveLedgerReportController extends Controller
{
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

    public function index()
    {
        return view('pages.reports.leave_ledger', [
            'departments' => department::orderBy('dep_name')->get(),
            'companies'   => DB::table('companies')->orderBy('comp_name')->get(),
            'leaveTypes'  => DB::table('leavetypes')->orderBy('type_leave')->get(),
            'years'       => range((int) now()->year, (int) now()->year - 5),
        ]);
    }

    private function applyFilters($q, Request $request)
    {
        if ($request->filled('company_id') && $request->company_id !== 'all') {
            $q->where('ed.empCompID', $request->company_id);
        }
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $q->where('ed.empDepID', $request->department_id);
        }
        if ($request->filled('leavetype_id') && $request->leavetype_id !== 'all') {
            $q->where('lt.id', $request->leavetype_id);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('u.fname', 'like', "%{$s}%")
                  ->orWhere('u.lname', 'like', "%{$s}%")
                  ->orWhere('u.empID', 'like', "%{$s}%");
            });
        }
        return $q;
    }

    private function compute(Request $request): array
    {
        $year = (int) ($request->input('year') ?: now()->year);
        $rows = [];

        // 1) Allocations for the year
        $allocQ = DB::table('leave_credit_allocations as lca')
            ->join('users as u', 'u.empID', '=', 'lca.employee_id')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'lca.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->leftJoin('leavetypes as lt', 'lt.id', '=', 'lca.leavetype_id')
            ->where('lca.year', $year);
        $this->applyFilters($allocQ, $request);
        $allocs = $allocQ->selectRaw("
                u.empID as employee_id,
                TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name,
                COALESCE(d.dep_name,'—') as department_name,
                lca.leavetype_id, COALESCE(lt.type_leave,'—') as leave_type,
                COALESCE(lca.credits_allocated,0) as allocated,
                COALESCE(lca.balance,0) as balance
            ")->get();

        foreach ($allocs as $a) {
            $key = $a->employee_id . '|' . $a->leavetype_id;
            $rows[$key] = (object) [
                'employee_id' => $a->employee_id, 'employee_name' => $a->employee_name,
                'department_name' => $a->department_name, 'leave_type' => $a->leave_type,
                'allocated' => (float) $a->allocated, 'balance' => (float) $a->balance,
                'used' => round((float) $a->allocated - (float) $a->balance, 2),
                'filed_days' => 0.0, 'filed_hours' => 0.0, 'has_alloc' => true,
            ];
        }

        // 2) Approved leave actually filed within the year
        $usageQ = DB::table('leave_details as ld')
            ->join('users as u', 'u.empID', '=', 'ld.employee_id')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'ld.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->leftJoin('leavetypes as lt', 'lt.id', '=', 'ld.leavetype_id')
            ->whereYear('ld.date', $year)
            ->where('ld.status', 'not like', 'reject%');
        $this->applyFilters($usageQ, $request);
        $usage = $usageQ->selectRaw("
                u.empID as employee_id,
                TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name,
                COALESCE(d.dep_name,'—') as department_name,
                ld.leavetype_id, COALESCE(lt.type_leave,'—') as leave_type,
                COUNT(*) as filed_days, SUM(COALESCE(ld.total_hours,0)) as filed_hours
            ")
            ->groupBy('u.empID', 'employee_name', 'department_name', 'ld.leavetype_id', 'leave_type')
            ->get();

        foreach ($usage as $u) {
            $key = $u->employee_id . '|' . $u->leavetype_id;
            if (isset($rows[$key])) {
                $rows[$key]->filed_days  = (float) $u->filed_days;
                $rows[$key]->filed_hours = (float) $u->filed_hours;
            } else {
                $rows[$key] = (object) [
                    'employee_id' => $u->employee_id, 'employee_name' => $u->employee_name,
                    'department_name' => $u->department_name, 'leave_type' => $u->leave_type,
                    'allocated' => 0.0, 'balance' => 0.0, 'used' => 0.0,
                    'filed_days' => (float) $u->filed_days, 'filed_hours' => (float) $u->filed_hours,
                    'has_alloc' => false,
                ];
            }
        }

        $rows = collect(array_values($rows))
            ->sortBy([['employee_name', 'asc'], ['leave_type', 'asc']])
            ->values();

        return [$rows, $year];
    }

    private function stats(Collection $rows): array
    {
        return [
            'count'     => $rows->count(),
            'allocated' => round($rows->sum('allocated'), 2),
            'used'      => round($rows->where('has_alloc', true)->sum('used'), 2),
            'balance'   => round($rows->where('has_alloc', true)->sum('balance'), 2),
            'filed'     => round($rows->sum('filed_days'), 2),
        ];
    }

    public function fetch(Request $request)
    {
        [$rows, $year] = $this->compute($request);
        return response()->json(['data' => $rows, 'year' => $year, 'stats' => $this->stats($rows)]);
    }

    public function export(Request $request)
    {
        [$rows, $year] = $this->compute($request);
        $st = $this->stats($rows);

        $x = new SimpleXlsx('Leave Ledger');
        $x->setColumnWidths([5, 12, 30, 22, 22, 13, 13, 13, 14, 14]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', "LEAVE LEDGER / BALANCE — {$year}", SimpleXlsx::S_TITLE);

        $hr = 4;
        $headers = ['NO.', 'EMP ID', 'EMPLOYEE NAME', 'DEPARTMENT', 'LEAVE TYPE', 'ALLOCATED', 'USED', 'BALANCE', 'FILED (DAYS)', 'FILED (HRS)'];
        $cols = range('A', 'J');
        foreach ($headers as $i => $h) { $x->setString("{$cols[$i]}{$hr}", $h, SimpleXlsx::S_BOLD); }

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) $row->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", strtoupper((string) $row->employee_name), SimpleXlsx::S_NORMAL);
            $x->setString("D{$r}", (string) $row->department_name, SimpleXlsx::S_NORMAL);
            $x->setString("E{$r}", (string) $row->leave_type, SimpleXlsx::S_NORMAL);
            $x->setNumber("F{$r}", (float) $row->allocated, SimpleXlsx::S_NORMAL);
            $x->setNumber("G{$r}", (float) $row->used, SimpleXlsx::S_NORMAL);
            $x->setNumber("H{$r}", (float) $row->balance, SimpleXlsx::S_NORMAL);
            $x->setNumber("I{$r}", (float) $row->filed_days, SimpleXlsx::S_NORMAL);
            $x->setNumber("J{$r}", (float) $row->filed_hours, SimpleXlsx::S_NORMAL);
            $r++;
        }

        $x->setString("C{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("F{$r}", (float) $st['allocated'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("G{$r}", (float) $st['used'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("H{$r}", (float) $st['balance'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("I{$r}", (float) $st['filed'], SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();
        return response()->download($path, "Leave_Ledger_{$year}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$rows, $year] = $this->compute($request);
        return view('pages.reports.leave_ledger_print', [
            'rows'       => $rows,
            'year'       => $year,
            'stats'      => $this->stats($rows),
            'letterhead' => self::LETTERHEAD,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\department;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Disciplinary Notices Summary — per-employee tally of memos vs disciplinary
 * notices with the same escalation flags the Notices module uses (>= WARN =
 * at-risk, >= SUSPEND = over-limit), plus the list of pending suspension
 * recommendations. Reads `notices` + `suspension_recommendations`.
 */
class NoticesReportController extends Controller
{
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

    // Mirrors App\Services\NoticeService thresholds (active disciplinary count).
    private const WARN = 3;
    private const SUSPEND = 4;

    public function index()
    {
        return view('pages.reports.notices_report', [
            'departments' => department::orderBy('dep_name')->get(),
            'companies'   => DB::table('companies')->orderBy('comp_name')->get(),
            'years'       => range((int) now()->year, (int) now()->year - 5),
        ]);
    }

    private function compute(Request $request): array
    {
        $q = DB::table('notices as n')
            ->join('users as u', 'u.empID', '=', 'n.employee_id')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'n.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID');

        if ($request->filled('year') && $request->year !== 'all') {
            $q->whereYear('n.issued_at', (int) $request->year);
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
                  ->orWhere('u.empID', 'like', "%{$s}%");
            });
        }

        $rows = $q->selectRaw("
                u.empID as employee_id,
                TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name,
                COALESCE(d.dep_name,'—') as department_name,
                SUM(CASE WHEN n.type='disciplinary' THEN 1 ELSE 0 END) as disciplinary,
                SUM(CASE WHEN n.type='disciplinary' AND n.status='active' THEN 1 ELSE 0 END) as active_disciplinary,
                SUM(CASE WHEN n.type='memo' THEN 1 ELSE 0 END) as memos,
                SUM(CASE WHEN n.status='void' THEN 1 ELSE 0 END) as voided,
                COUNT(*) as total,
                MAX(n.issued_at) as last_issued
            ")
            ->groupBy('u.empID', 'employee_name', 'department_name')
            ->get()
            ->map(function ($r) {
                foreach (['disciplinary', 'active_disciplinary', 'memos', 'voided', 'total'] as $k) { $r->$k = (int) $r->$k; }
                $r->escalation = $r->active_disciplinary >= self::SUSPEND ? 'over'
                    : ($r->active_disciplinary >= self::WARN ? 'at_risk' : 'ok');
                return $r;
            })
            ->sortByDesc('active_disciplinary')
            ->values();

        // Pending suspension recommendations (all open ones)
        $recs = DB::table('suspension_recommendations as s')
            ->join('users as u', 'u.empID', '=', 's.employee_id')
            ->where('s.status', 'pending')
            ->selectRaw("s.employee_id, TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name,
                s.notice_count, s.reason, s.recommended_by, s.recommended_at")
            ->orderByDesc('s.recommended_at')->get();

        return [$rows, $recs];
    }

    private function stats(Collection $rows, $recs): array
    {
        return [
            'employees'    => $rows->count(),
            'disciplinary' => $rows->sum('disciplinary'),
            'memos'        => $rows->sum('memos'),
            'over'         => $rows->where('escalation', 'over')->count(),
            'at_risk'      => $rows->where('escalation', 'at_risk')->count(),
            'pending_recs' => $recs->count(),
        ];
    }

    public function fetch(Request $request)
    {
        [$rows, $recs] = $this->compute($request);
        return response()->json(['data' => $rows, 'recommendations' => $recs, 'stats' => $this->stats($rows, $recs)]);
    }

    public function export(Request $request)
    {
        [$rows, $recs] = $this->compute($request);

        $x = new SimpleXlsx('Notices');
        $x->setColumnWidths([5, 12, 30, 22, 14, 18, 10, 10, 16]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', 'DISCIPLINARY NOTICES SUMMARY', SimpleXlsx::S_TITLE);

        $hr = 4;
        $headers = ['NO.', 'EMP ID', 'EMPLOYEE NAME', 'DEPARTMENT', 'DISCIPLINARY', 'ACTIVE DISCIPLINARY', 'MEMOS', 'VOID', 'STATUS'];
        $cols = range('A', 'I');
        foreach ($headers as $i => $h) { $x->setString("{$cols[$i]}{$hr}", $h, SimpleXlsx::S_BOLD); }

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) $row->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", strtoupper((string) $row->employee_name), SimpleXlsx::S_NORMAL);
            $x->setString("D{$r}", (string) $row->department_name, SimpleXlsx::S_NORMAL);
            $x->setNumber("E{$r}", (float) $row->disciplinary, SimpleXlsx::S_NORMAL);
            $x->setNumber("F{$r}", (float) $row->active_disciplinary, SimpleXlsx::S_NORMAL);
            $x->setNumber("G{$r}", (float) $row->memos, SimpleXlsx::S_NORMAL);
            $x->setNumber("H{$r}", (float) $row->voided, SimpleXlsx::S_NORMAL);
            $x->setString("I{$r}", $row->escalation === 'over' ? 'OVER-LIMIT' : ($row->escalation === 'at_risk' ? 'AT RISK' : 'OK'), SimpleXlsx::S_NORMAL);
            $r++;
        }

        if ($recs->count()) {
            $r += 2;
            $x->setString("A{$r}", 'PENDING SUSPENSION RECOMMENDATIONS', SimpleXlsx::S_BOLD); $r++;
            $x->setString("A{$r}", 'EMP ID', SimpleXlsx::S_BOLD); $x->setString("B{$r}", 'NAME', SimpleXlsx::S_BOLD);
            $x->setString("C{$r}", 'NOTICES', SimpleXlsx::S_BOLD); $x->setString("D{$r}", 'REASON', SimpleXlsx::S_BOLD); $r++;
            foreach ($recs as $rec) {
                $x->setString("A{$r}", (string) $rec->employee_id, SimpleXlsx::S_TEXT);
                $x->setString("B{$r}", strtoupper((string) $rec->employee_name), SimpleXlsx::S_NORMAL);
                $x->setNumber("C{$r}", (float) $rec->notice_count, SimpleXlsx::S_NORMAL);
                $x->setString("D{$r}", (string) $rec->reason, SimpleXlsx::S_NORMAL); $r++;
            }
        }

        $path = $x->saveToTempFile();
        return response()->download($path, 'Disciplinary_Notices_' . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$rows, $recs] = $this->compute($request);
        return view('pages.reports.notices_report_print', [
            'rows'            => $rows,
            'recommendations' => $recs,
            'stats'           => $this->stats($rows, $recs),
            'letterhead'      => self::LETTERHEAD,
        ]);
    }
}

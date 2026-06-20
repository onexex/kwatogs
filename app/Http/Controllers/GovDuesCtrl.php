<?php

namespace App\Http\Controllers;

use App\Models\empDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Government Dues enrolment screen.
 *
 * Per-employee on/off toggles deciding whether SSS / PhilHealth / Pag-IBIG
 * contributions are deducted during payroll. The flags live on emp_details
 * (sss_enabled / philhealth_enabled / pagibig_enabled) and are read by
 * ContributionHelper::computeAll() via PayrollController.
 */
class GovDuesCtrl extends Controller
{
    /** Allowed due => emp_details column map (whitelist guards the toggle). */
    private const DUES = [
        'sss'        => 'sss_enabled',
        'philhealth' => 'philhealth_enabled',
        'pagibig'    => 'pagibig_enabled',
    ];

    public function index()
    {
        return view('pages.management.govdues');
    }

    /** Employee list with their current enrolment flags (for the grid). */
    public function getAll(Request $request)
    {
        $rows = empDetail::query()
            ->leftJoin('users as u', 'u.empID', '=', 'emp_details.empID')
            ->leftJoin('companies as c', 'c.comp_id', '=', 'emp_details.empCompID')
            ->leftJoin('classifications as cl', 'cl.class_code', '=', 'emp_details.empClassification')
            ->orderBy('u.lname')
            ->orderBy('u.fname')
            ->get([
                'emp_details.id',
                'emp_details.empID',
                'emp_details.empClassification',
                'emp_details.sss_enabled',
                'emp_details.philhealth_enabled',
                'emp_details.pagibig_enabled',
                'u.fname',
                'u.lname',
                'u.mname',
                'c.comp_name as company',
                'cl.class_desc as classification',
            ])
            ->map(function ($r) {
                $name = trim(strtoupper(trim(($r->lname ?? '') . ', ' . ($r->fname ?? '') . ' ' . ($r->mname ?? ''))), ', ');
                return [
                    'id'                 => $r->id,
                    'empID'              => $r->empID,
                    'name'               => $name !== '' ? $name : $r->empID,
                    'company'            => $r->company,
                    'classification'     => $r->classification ?? $r->empClassification,
                    'sss_enabled'        => (bool) $r->sss_enabled,
                    'philhealth_enabled' => (bool) $r->philhealth_enabled,
                    'pagibig_enabled'    => (bool) $r->pagibig_enabled,
                ];
            });

        return response()->json(['status' => 200, 'data' => $rows]);
    }

    /** Flip a single due for a single employee. */
    public function toggle(Request $request)
    {
        $id   = $request->input('id');
        $due  = $request->input('due');
        $on   = filter_var($request->input('enabled'), FILTER_VALIDATE_BOOLEAN);

        if (!isset(self::DUES[$due])) {
            return response()->json(['status' => 422, 'msg' => 'Unknown government due.'], 422);
        }

        $emp = empDetail::find($id);
        if (!$emp) {
            return response()->json(['status' => 404, 'msg' => 'Employee not found.'], 404);
        }

        $column = self::DUES[$due];
        $emp->{$column} = $on;
        $emp->save();

        return response()->json([
            'status'  => 200,
            'msg'     => strtoupper($due) . ($on ? ' enabled' : ' disabled') . ' for ' . $emp->empID . '.',
            'enabled' => $on,
        ]);
    }

    /** Bulk set every due for one employee (the "all on / all off" row action). */
    public function toggleAll(Request $request)
    {
        $id = $request->input('id');
        $on = filter_var($request->input('enabled'), FILTER_VALIDATE_BOOLEAN);

        $emp = empDetail::find($id);
        if (!$emp) {
            return response()->json(['status' => 404, 'msg' => 'Employee not found.'], 404);
        }

        $emp->sss_enabled        = $on;
        $emp->philhealth_enabled = $on;
        $emp->pagibig_enabled    = $on;
        $emp->save();

        return response()->json([
            'status' => 200,
            'msg'    => 'All government dues ' . ($on ? 'enabled' : 'disabled') . ' for ' . $emp->empID . '.',
        ]);
    }
}

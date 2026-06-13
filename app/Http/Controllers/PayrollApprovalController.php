<?php

namespace App\Http\Controllers;

use App\Models\PayrollApproval;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayrollApprovalController extends Controller
{
    /** AJAX: approval state for a pay date (drives the UI). */
    public function status(Request $request)
    {
        $request->validate(['pay_date' => 'required|date']);

        $a = PayrollApproval::whereDate('pay_date', $request->pay_date)->first();

        return response()->json([
            'approved'         => (bool) $a,
            'approved_by_name' => $a->approved_by_name ?? null,
            'approved_at'      => $a ? Carbon::parse($a->approved_at)->format('M d, Y h:i A') : null,
            'remarks'          => $a->remarks ?? null,
            'can_approve'      => (bool) optional($request->user())->can('approvepayroll'),
            'can_regenerate'   => (bool) optional($request->user())->can('regeneratepayroll'),
        ]);
    }

    /** Approve / finalize a pay date. Requires approvepayroll. */
    public function approve(Request $request)
    {
        $request->validate([
            'pay_date' => 'required|date',
            'remarks'  => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        PayrollApproval::updateOrCreate(
            ['pay_date' => $request->pay_date],
            [
                'approved_by'      => optional($user)->empID,
                'approved_by_name' => $user ? trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) : null,
                'approved_at'      => now(),
                'remarks'          => $request->remarks,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Payroll approved and locked as final.']);
    }

    /** Reopen an approved pay date (override). Requires regeneratepayroll. */
    public function reopen(Request $request)
    {
        $request->validate(['pay_date' => 'required|date']);

        PayrollApproval::whereDate('pay_date', $request->pay_date)->delete();

        return response()->json(['success' => true, 'message' => 'Payroll reopened. Regeneration and edits are allowed again.']);
    }
}

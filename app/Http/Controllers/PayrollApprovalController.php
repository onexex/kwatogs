<?php

namespace App\Http\Controllers;

use App\Jobs\SendPayslipEmailJob;
use App\Models\MailIntegrationSetting;
use App\Models\Payroll;
use App\Models\PayrollApproval;
use App\Models\PayslipEmailSetting;
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

        $message = 'Payroll approved and locked as final.';

        // Optional: fire off payslip emails automatically when approval happens,
        // if an admin has turned this on in the Email Payslips settings panel.
        $emailSetting = PayslipEmailSetting::current();

        if ($emailSetting->auto_send_on_approval && MailIntegrationSetting::where('is_active', true)->exists()) {
            $payrolls = Payroll::where('pay_date', $request->pay_date)->get();

            foreach ($payrolls as $payroll) {
                SendPayslipEmailJob::dispatch($payroll->id, 'system (auto-send on approval)');
            }

            $message .= ' Payslip emails for '.$payrolls->count().' employee(s) have been queued.';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /** Reopen an approved pay date (override). Requires regeneratepayroll. */
    public function reopen(Request $request)
    {
        $request->validate(['pay_date' => 'required|date']);

        PayrollApproval::whereDate('pay_date', $request->pay_date)->delete();

        return response()->json(['success' => true, 'message' => 'Payroll reopened. Regeneration and edits are allowed again.']);
    }
}

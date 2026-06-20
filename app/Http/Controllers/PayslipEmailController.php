<?php

namespace App\Http\Controllers;

use App\Jobs\SendPayslipEmailJob;
use App\Models\MailIntegrationSetting;
use App\Models\Payroll;
use App\Models\PayslipEmailLog;
use App\Models\PayslipEmailSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Sending side of the payslip-email feature. Mirrors the same filter
 * shape (pay_date / company_id / classification_id / department_id) as
 * PayrollController@payslip so "Send Payslips" always targets the same
 * set of employees "Print Payslips" would show.
 */
class PayslipEmailController extends Controller
{
    /**
     * Queue one SendPayslipEmailJob per employee matching the given filters.
     */
    public function sendBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pay_date'          => 'required|date',
            'employee_id'       => 'nullable',
            'company_id'        => 'nullable',
            'classification_id' => 'nullable',
            'department_id'     => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        if (!MailIntegrationSetting::where('is_active', true)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No active mail integration. Set one up and pass a test email in Settings -> Mail Integration first.',
            ], 422);
        }

        $payrolls = $this->matchingPayrolls($request);

        if ($payrolls->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No payroll records found for that selection.'], 422);
        }

        $actor = $this->actorName($request);

        foreach ($payrolls as $payroll) {
            SendPayslipEmailJob::dispatch($payroll->id, $actor);
        }

        return response()->json([
            'success' => true,
            'message' => 'Queued '.$payrolls->count().' payslip email(s).',
            'count'   => $payrolls->count(),
        ]);
    }

    /**
     * Resend a single employee's payslip (e.g. after a failure, or their
     * email was corrected).
     */
    public function resend(Request $request, Payroll $payroll)
    {
        SendPayslipEmailJob::dispatch($payroll->id, $this->actorName($request));

        return response()->json(['success' => true, 'message' => 'Payslip email re-queued.']);
    }

    /**
     * Latest send status per employee for a given pay date (+ optional
     * filters), for the status table on the Payroll screen.
     */
    public function status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pay_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $payrolls = $this->matchingPayrolls($request)->keyBy('id');

        $logs = PayslipEmailLog::whereIn('payroll_id', $payrolls->keys())
            ->orderByDesc('id')
            ->get()
            ->unique('payroll_id') // latest attempt per payroll (already ordered desc)
            ->keyBy('payroll_id');

        $rows = $payrolls->map(function (Payroll $payroll) use ($logs) {
            $log  = $logs->get($payroll->id);
            $emp  = $payroll->employee;
            $name = $emp ? trim(($emp->fname ?? '').' '.($emp->lname ?? '')) : $payroll->employee_id;

            return [
                'payroll_id'   => $payroll->id,
                'employee_id'  => $payroll->employee_id,
                'name'         => $name,
                'email'        => optional($emp)->email,
                'status'       => $log->status ?? 'not_sent',
                'sent_at'      => optional(optional($log)->sent_at)->format('M d, Y h:i A'),
                'error'        => optional($log)->error_message,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function getSettings()
    {
        $setting = PayslipEmailSetting::current();

        return response()->json([
            'success' => true,
            'data'    => [
                'password_source'       => $setting->password_source,
                'auto_send_on_approval' => (bool) $setting->auto_send_on_approval,
            ],
            'options' => PayslipEmailSetting::PASSWORD_SOURCES,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password_source'       => 'required|in:'.implode(',', array_keys(PayslipEmailSetting::PASSWORD_SOURCES)),
            'auto_send_on_approval' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $setting = PayslipEmailSetting::current();
        $setting->update([
            'password_source'       => $request->input('password_source'),
            'auto_send_on_approval' => $request->boolean('auto_send_on_approval'),
            'updated_by'            => $this->actorName($request),
        ]);

        return response()->json(['success' => true, 'message' => 'Payslip email settings saved.']);
    }

    private function matchingPayrolls(Request $request)
    {
        $payDate          = $request->query('pay_date') ?? $request->input('pay_date');
        $employeeId       = $request->query('employee_id') ?? $request->input('employee_id');
        $companyId        = $request->query('company_id', $request->input('company_id', 'all')) ?: 'all';
        $classificationId = $request->query('classification_id', $request->input('classification_id', 'all')) ?: 'all';
        $departmentId     = $request->query('department_id', $request->input('department_id', 'all')) ?: 'all';

        $query = Payroll::with(['employee'])
            ->join('users', 'payrolls.employee_id', '=', 'users.empID')
            ->where('payrolls.pay_date', $payDate)
            ->select('payrolls.*');

        if (!empty($employeeId)) {
            $query->where('payrolls.employee_id', $employeeId);
        }

        if ($companyId !== 'all' || $classificationId !== 'all' || $departmentId !== 'all') {
            $query->whereHas('employee.empDetail', function ($q) use ($companyId, $classificationId, $departmentId) {
                if ($companyId !== 'all')        { $q->where('empCompID', $companyId); }
                if ($classificationId !== 'all') { $q->where('empClassification', $classificationId); }
                if ($departmentId !== 'all')     { $q->where('empDepID', $departmentId); }
            });
        }

        return $query->orderBy('users.lname')->orderBy('users.fname')->get();
    }

    private function actorName(Request $request): ?string
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        return $user->community_full_name
            ?: trim(($user->fname ?? '').' '.($user->lname ?? ''))
            ?: ($user->name ?? null);
    }
}

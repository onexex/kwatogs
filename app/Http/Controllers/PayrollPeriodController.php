<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollPeriodController extends Controller
{
    /**
     * Return the payroll periods configured for a company.
     */
    public function byCompany($companyId)
    {
        $periods = PayrollPeriod::where('company_id', $companyId)
            ->orderBy('sort')->orderBy('id')->get();

        return response()->json(['status' => 200, 'data' => $periods]);
    }

    /**
     * Replace the company's payroll periods with the posted set.
     */
    public function save(Request $request, $companyId)
    {
        $data = $request->validate([
            'periods'                          => 'required|array|min:1',
            'periods.*.label'                  => 'nullable|string|max:50',
            'periods.*.pay_end_of_month'       => 'boolean',
            'periods.*.pay_day'                => 'nullable|integer|min:1|max:31',
            'periods.*.cutoff_from_day'        => 'required|integer|min:1|max:31',
            'periods.*.cutoff_from_prev_month' => 'boolean',
            'periods.*.cutoff_to_day'          => 'required|integer|min:1|max:31',
        ]);

        DB::transaction(function () use ($companyId, $data) {
            PayrollPeriod::where('company_id', $companyId)->delete();

            foreach ($data['periods'] as $i => $p) {
                $eom = !empty($p['pay_end_of_month']);
                PayrollPeriod::create([
                    'company_id'             => $companyId,
                    'label'                  => $p['label'] ?? null,
                    'pay_end_of_month'       => $eom,
                    'pay_day'                => $eom ? null : ($p['pay_day'] ?? null),
                    'cutoff_from_day'        => $p['cutoff_from_day'],
                    'cutoff_from_prev_month' => !empty($p['cutoff_from_prev_month']),
                    'cutoff_to_day'          => $p['cutoff_to_day'],
                    'sort'                   => $i,
                ]);
            }
        });

        return response()->json(['status' => 200, 'msg' => 'Payroll schedule saved.']);
    }
}

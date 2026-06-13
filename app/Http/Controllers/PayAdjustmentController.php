<?php

namespace App\Http\Controllers;

use App\Models\PayAdjustment;
use App\Models\User;
use Illuminate\Http\Request;

class PayAdjustmentController extends Controller
{
    public function index()
    {
        $adjustments = PayAdjustment::with('employee')->latest()->get();
        $employees   = User::select('empID', 'lname', 'fname')->orderBy('fname')->get();

        return view('pages.modules.pay_adjustments', compact('adjustments', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required',
            'pay_date'    => 'required|date',
            'label'       => 'required|string|max:255',
            'kind'        => 'required|in:addition,deduction',
            'apply_to'    => 'required|in:gross,net',
            'amount'      => 'required|numeric|min:0.01',
            'remarks'     => 'nullable|string|max:255',
        ]);

        if ($locked = $this->lockResponse($request, $data['pay_date'])) { return $locked; }

        $data['created_by'] = optional($request->user())->empID ?? optional($request->user())->fname;

        PayAdjustment::create($data);

        return response()->json(['success' => true]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'adjustment_id' => 'required|exists:pay_adjustments,id',
            'employee_id'   => 'required',
            'pay_date'      => 'required|date',
            'label'         => 'required|string|max:255',
            'kind'          => 'required|in:addition,deduction',
            'apply_to'      => 'required|in:gross,net',
            'amount'        => 'required|numeric|min:0.01',
            'remarks'       => 'nullable|string|max:255',
        ]);

        if ($locked = $this->lockResponse($request, $data['pay_date'])) { return $locked; }

        $adj = PayAdjustment::findOrFail($request->adjustment_id);
        unset($data['adjustment_id']);
        $adj->update($data);

        return response()->json(['success' => true]);
    }

    /** Block edits to an approved pay date unless the user can regenerate. */
    private function lockResponse(Request $request, $payDate)
    {
        if (\App\Models\PayrollApproval::isLocked($payDate)
            && !optional($request->user())->can('regeneratepayroll')) {
            return response()->json([
                'success' => false,
                'message' => 'This pay date is approved and final. Adjustments are locked.',
            ], 423);
        }
        return null;
    }

    public function destroy($id)
    {
        PayAdjustment::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}

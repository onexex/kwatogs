<?php

namespace App\Http\Controllers;

use App\Models\PayAdjustment;
use App\Models\User;
use Illuminate\Http\Request;

class PayAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->input('search', ''));
        $kind     = $request->input('kind', '');
        $applyTo  = $request->input('apply_to', '');
        $payDate  = $request->input('pay_date', '');

        $adjustments = PayAdjustment::with('employee')
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('employee', function ($e) use ($search) {
                    $e->where('fname', 'like', "%{$search}%")
                      ->orWhere('lname', 'like', "%{$search}%");
                });
            })
            ->when($kind !== '', fn ($q) => $q->where('kind', $kind))
            ->when($applyTo !== '', fn ($q) => $q->where('apply_to', $applyTo))
            ->when($payDate !== '', fn ($q) => $q->whereDate('pay_date', $payDate))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $employees = User::select('empID', 'lname', 'fname')->orderBy('fname')->get();

        return view('pages.modules.pay_adjustments', compact(
            'adjustments', 'employees', 'search', 'kind', 'applyTo', 'payDate'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'required',
            'pay_date'       => 'required|date',
            'label'          => 'required|string|max:255',
            'kind'           => 'required|in:addition,deduction',
            'apply_to'       => 'required|in:gross,net',
            'amount'         => 'required|numeric|min:0.01',
            'remarks'        => 'nullable|string|max:255',
        ]);

        if ($locked = $this->lockResponse($request, $data['pay_date'])) { return $locked; }

        $createdBy = optional($request->user())->empID ?? optional($request->user())->fname;

        // Bulk create: one entry per selected employee, same adjustment details
        foreach ($data['employee_ids'] as $employeeId) {
            PayAdjustment::create([
                'employee_id' => $employeeId,
                'pay_date'    => $data['pay_date'],
                'label'       => $data['label'],
                'kind'        => $data['kind'],
                'apply_to'    => $data['apply_to'],
                'amount'      => $data['amount'],
                'remarks'     => $data['remarks'] ?? null,
                'created_by'  => $createdBy,
            ]);
        }

        return response()->json([
            'success' => true,
            'count'   => count($data['employee_ids']),
        ]);
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

<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $search    = trim((string) $request->input('search', ''));
        $type      = $request->input('type', '');
        $status    = $request->input('status', '');
        $recurring = $request->input('recurring', '');

        $loans = Loan::with('employee')
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('employee', function ($e) use ($search) {
                    $e->where('fname', 'like', "%{$search}%")
                      ->orWhere('lname', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn ($q) => $q->where('loans.loan_type', $type))
            ->when($status !== '', fn ($q) => $q->where('loans.status', $status))
            ->when($recurring !== '', fn ($q) => $q->where('loans.is_recurring', $recurring === '1'))
            ->join('users', 'users.empID', '=', 'loans.employee_id')
            ->orderBy('users.lname')
            ->orderBy('users.fname')
            ->select('loans.*')
            ->paginate(15)
            ->withQueryString();

        $employees = User::select('empID', 'lname', 'fname')->orderBy('lname')->orderBy('fname')->get();

        return view('pages.management.loan', compact('loans', 'employees', 'search', 'type', 'status', 'recurring'));
    }

    /**
     * Government loan types are inherently balance-based and cannot be recurring.
     */
    private const GOV_TYPES = ['sss', 'pagibig', 'philhealth'];

    public function store(Request $request)
    {
        $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'required',
            'loan_type' => 'required',
            'other_description' => 'required_if:loan_type,other|nullable|string|max:255',
            'loan_amount' => 'required_unless:is_recurring,1|nullable|numeric',
            'monthly_amortization' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
        ]);

        // Recurring only applies to non-government charges.
        $isRecurring = $request->boolean('is_recurring') && !in_array($request->loan_type, self::GOV_TYPES);

        // Only keep the specification when the type is "other"
        $otherDescription = $request->loan_type === 'other' ? $request->other_description : null;

        // Recurring charges carry no principal/balance and no end date.
        $loanAmount = $isRecurring ? 0 : $request->loan_amount;

        // Bulk create: one record per selected employee, same amount details
        foreach ($request->employee_ids as $employeeId) {
            Loan::create([
                'employee_id'          => $employeeId,
                'loan_type'            => $request->loan_type,
                'other_description'    => $otherDescription,
                'loan_amount'          => $loanAmount,
                'balance'              => $loanAmount, // Initial balance = loan amount (0 for recurring)
                'monthly_amortization' => $request->monthly_amortization,
                'start_date'           => $request->start_date,
                'end_date'             => $isRecurring ? null : $request->end_date,
                'is_recurring'         => $isRecurring,
            ]);
        }

        return response()->json([
            'success' => true,
            'count'   => count($request->employee_ids),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'loan_id'   => 'required',
            'loan_type' => 'required',
            'other_description' => 'required_if:loan_type,other|nullable|string|max:255',
            'loan_amount' => 'required_unless:is_recurring,1|nullable|numeric',
            'monthly_amortization' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
        ]);

        $loan = Loan::findOrFail($request->loan_id);
        $wasRecurring = $loan->is_recurring;

        $isRecurring = $request->boolean('is_recurring') && !in_array($request->loan_type, self::GOV_TYPES);

        $loan->employee_id          = $request->employee_id ?? $loan->employee_id;
        $loan->loan_type            = $request->loan_type;
        $loan->other_description    = $request->loan_type === 'other' ? $request->other_description : null;
        $loan->monthly_amortization = $request->monthly_amortization;
        $loan->start_date           = $request->start_date;
        $loan->end_date             = $isRecurring ? null : $request->end_date;
        $loan->is_recurring         = $isRecurring;

        if ($isRecurring) {
            // Recurring charge: no principal/balance.
            $loan->loan_amount = 0;
            $loan->balance     = 0;
        } elseif ($wasRecurring) {
            // Switched recurring -> finite: seed the balance from the new principal.
            $loan->loan_amount = $request->loan_amount;
            $loan->balance     = $request->loan_amount;
        } else {
            // Stays finite: update principal but keep the paid-down balance intact.
            $loan->loan_amount = $request->loan_amount;
        }

        $loan->save();

        return response()->json(['success' => true]);
    }

    /**
     * Pause / resume a recurring charge (inline on/off switch on the row).
     * Flips status between 'active' and 'cancelled' via an instance save so the
     * change is captured by the Auditable trait.
     */
    public function toggleStatus($id)
    {
        $loan = Loan::findOrFail($id);

        if (!$loan->is_recurring) {
            return response()->json([
                'success' => false,
                'message' => 'Only recurring charges can be toggled on or off.',
            ], 422);
        }

        $loan->status = $loan->status === 'active' ? 'cancelled' : 'active';
        $loan->save();

        return response()->json(['success' => true, 'status' => $loan->status]);
    }

    public function destroy($id)
    {
        Loan::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}

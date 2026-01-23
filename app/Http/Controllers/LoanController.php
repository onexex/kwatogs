<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        $loans = Loan::with('employee')->latest()->get();
        $employees = User::select('empID', 'fname')->orderBy('fname')->get();

        return view('pages.management.loan', compact('loans', 'employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'loan_type' => 'required',
            'loan_amount' => 'required|numeric',
            'monthly_amortization' => 'required|numeric',
            'start_date' => 'required|date',
        ]);

        $request->merge(['balance' => $request->loan_amount]); // Initial balance = loan amount

        Loan::create($request->all());

        return response()->json(['success' => true]);
    }

    public function update(Request $request)
    {
       
        $loan = Loan::findOrFail($request->loan_id);

        $loan->update($request->except('loan_id'));

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        Loan::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\leavetype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LeaveCreditAllocation;

class LeaveCreditAllocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!(Auth::user()->can('leavecreditallocation'))) {
            abort(403, 'Unauthorized action.');
        }
        $employees = User::orderBy('lname')->get();
        $leavetypes = leavetype::all();
        $leaveCreditAllocations = LeaveCreditAllocation::where('year', date('Y'))->get();
        return view('pages.management.leavecreditallocation', compact('employees', 'leavetypes', 'leaveCreditAllocations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $leaveCreditAllocation = LeaveCreditAllocation::where('employee_id', $request->employee_id)
            ->where('leavetype_id', $request->leave_type)
            ->where('year', $request->year)
            ->first();

        if ($leaveCreditAllocation) {
            return response()->json(['status' => 'error', 'message' => 'Leave credit allocation already exists for this employee, leave type, and year.']);
        } else {
            $newAllocation = new LeaveCreditAllocation();
            $newAllocation->employee_id = $request->employee_id;
            $newAllocation->leavetype_id = $request->leave_type;
            $newAllocation->year = $request->year;
            $newAllocation->credits_allocated = $request->leave_credit;
            $newAllocation->balance = $request->leave_credit; // Set initial balance equal to allocated credits
            $newAllocation->save();

            return response()->json(['status' => 'success', 'message' => 'Leave credit allocation created successfully.']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveCreditAllocation $leaveCreditAllocation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveCreditAllocation $leaveCreditAllocation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveCreditAllocation $leaveCreditAllocation)
    {
        LeaveCreditAllocation::where('id', $request->id)->update([
            'employee_id' => $request->employee_id,
            'leavetype_id' => $request->leave_type,
            'credits_allocated' => $request->leave_credit,
            'balance' => $request->leave_credit, // Update balance to match new allocated credits
            'year' => $request->year,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Leave credit updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveCreditAllocation $leaveCreditAllocation)
    {
        $leaveCreditAllocation->delete();
        return response()->json(['status' => 'success', 'message' => 'Leave credit allocation deleted successfully.']);
    }
}

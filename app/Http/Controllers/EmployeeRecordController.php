<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class EmployeeRecordController extends Controller
{
    // Load the initial Admin Search Page
    public function index() {
        // Fetch users with their details, ordered alphabetically by last name
        $resultUser = User::with('empDetail.position')
            ->orderBy('lname', 'asc')
            ->get();

        return view('pages.management.e201', compact('resultUser'));
    }

    // THE GET FUNCTION: Fetch full bio-data using Eloquent
    public function getEmployeeDetails($empID) {
        $user = User::with([
            'empDetail.department',
            'empDetail.position',
            'empDetail.company',
            'education' // <--- Add your new relationship here
        ])
        ->where('empID', $empID)
        ->first();

        if ($user) {
            return response()->json([
                'status' => 200,
                'data' => $user
            ]);
        }

        return response()->json(['status' => 404, 'message' => 'Record not found']);
    }
}
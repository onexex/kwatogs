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
    // public function getEmployeeDetails($empID) {
    //     $user = User::with([
    //         'empDetail.department',
    //         'empDetail.position',
    //         'empDetail.company',
    //         'education' // <--- Add your new relationship here
    //     ])
    //     ->where('empID', $empID)
    //     ->first();

    //     if ($user) {
    //         return response()->json([
    //             'status' => 200,
    //             'data' => $user
    //         ]);
    //     }

    //     return response()->json(['status' => 404, 'message' => 'Record not found']);
    // }

    public function getEmployeeDetails($empID) {
    $user = User::with([
        'empDetail.department',
        'empDetail.position',
        'empDetail.company',
        'education'
    ])
    ->where('empID', $empID)
    ->first();

    if ($user) {
    // 1. Get the path from the relationship
    $imageName = $user->empDetail->empPicPath ?? null;

    // 2. Build the URL. 
    // If you are on Folder 2 (cPanel), asset() is perfect.
    $fullUrl = $imageName 
        ? asset("img/profile/" . $imageName) 
        : asset("img/undraw_profile.svg");

    return response()->json([
        'status' => 200, 
        'data' => $user,
        'image_url' => $fullUrl // Pass it as a top-level key for easy access
    ]);
}

    return response()->json(['status' => 404, 'message' => 'Record not found']);
}
}
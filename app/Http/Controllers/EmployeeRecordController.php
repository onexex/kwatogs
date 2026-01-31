<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\company;
use App\Models\agencies;
use App\Models\HMOModel;
use App\Models\joblevel;
use App\Models\position;
use App\Models\department;
use Illuminate\Http\Request;
use App\Models\classification;

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
    public function editEmployee(User $user) 
    {
        $getCompany= company::get();
        $getClassification= classification::get();
        // $getClassification=classification::get();
        $getDepartment= department::get();
        $getPosition= position::get();
        $getImmediateList=User::get();
        $getJoblevel= joblevel::get();
        $getAgency= agencies::get();
        $getHMO= HMOModel::get();

        return view('pages.modules.employee.edit_employee', [
            'user' => $user,
        ])->with('hmoData',$getHMO)
            ->with('agencyData',$getAgency)
            ->with('joblevelData',$getJoblevel)
            ->with('companyData',$getCompany)
            ->with('immediateData',$getImmediateList)
            ->with('positionData',$getPosition)
            ->with('departmentData',$getDepartment)
            ->with('employeeClassification',$getClassification);;
    }


    public function getEmployeeDetails($empID) 
    {
        $user = User::with([
            'empDetail.department',
            'empDetail.position',
            'empDetail.company',
            'empDetail.classification',
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
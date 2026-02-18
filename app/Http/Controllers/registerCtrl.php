<?php

namespace App\Http\Controllers;

use App\Models\access;
use App\Models\emp_education;
use App\Models\emp_info;

use App\Models\empDetail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class registerCtrl extends Controller
{
    public function generateEmpID() {
        // 1. Get only the latest ID, don't load all users
        $latestEmployee = User::orderBy('id', 'desc')->first();

        if (!$latestEmployee) {
            $id = str_pad(1, 4, '0', STR_PAD_LEFT);
        } else {
            // 2. Increment the actual ID value, not the count
            $id = str_pad($latestEmployee->id + 1, 4, '0', STR_PAD_LEFT);
        }

        return response()->json(['status' => 200, 'data' => $id]);
    }

    public function get_province(Request $request){
        $data = DB::table('refprovince')
                // ->where('provCode','0410')
                ->orderBy('provDesc','desc')
                ->get();

        return json_encode(array('status'=>200,'data'=>$data));
    }

    public function get_city(Request $request) {
        $provcode=$request->id;
        $data = DB::table('refcitymun')
            ->where('provCode',$provcode)
            ->get();
        return json_encode(array('status'=>200,'data'=>$data));
    }

    public function get_brgy(Request $request){
        $citycode=$request->id;
        $data = DB::table('refbrgy')
            ->where('citymunCode',$citycode)
            ->get();
        return json_encode(array('status'=>200,'data'=>$data));
    }

    public function create(Request $request)
    {
       
        $defaultpass = "123456";
        $current_date_time = now();
        // dd($request->path->getClientOriginalName());

        $validator = Validator::make($request->all(), [
            'email' => 'required|unique:users',
            'firstname' => [
                'required',
                Rule::unique('users')->where(function ($query) use ($request) {
                    return $query->where('firstname', $request->firstname)
                                ->where('lastname', $request->lastname);
                }),
            ],
            'lastname' => 'required',
            'company' => 'required',
            'gender' => 'required',
            'citizenship' => 'required',
            'status' => 'required',
            'immediate' => 'required',
            'department' => 'required',
            'classification' => 'required',
            'position' => 'required',
            // 'job_level' => 'required',
            // 'religion' => 'required',
            'birthdate' => 'required',
            // 'homephone' => 'required',
            'province' => 'required',
            'mobile' => 'required',
            'city' => 'required',
            'barangay' => 'required',
            'zipcode' => 'required',
            'country' => 'required',
            // 'agency' => 'required',
            // 'hmo' => 'required',
            'no_work_days' => 'required',
            'date_hired' => 'required',
            'basic'=>'required|numeric',
            'allowance'=>'required|numeric',
            
            
        ]);

        if (!$validator->passes()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }
   

        DB::beginTransaction();
        try {
            /**
             * 🔒 Generate safe, unique empID
             * Format: COMPANYCODE-YYYY-0001
             */
            $companyCode = strtoupper($request->company);
            $year = now()->format('Y');

            // Lock table to prevent race conditions
            $latestEmp = DB::table('users')
                ->where('empID', 'LIKE', "{$companyCode}-{$year}-%")
                ->lockForUpdate()
                ->orderBy('empID', 'desc')
                ->first();

            if ($latestEmp) {
                // Extract last 4 digits
                $lastNumber = (int) substr($latestEmp->empID, -4);
                $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '0001';
            }

            $empID = "{$companyCode}-{$year}-{$nextNumber}";

            /**
             * 👇 Insert operations
             */
            $valuesUser = [
                'email' => $request->email,
                'empID' => $empID,
                'status' => '1',
                'suffix' => $request->suffix,
                'lname' => $request->lastname,
                'fname' => $request->firstname,
                'mname' => $request->middlename,
                'password' => Hash::make($defaultpass),
                'role' => "3",
                'created_at' => $current_date_time,
                'updated_at' => $current_date_time,
            ];

            $valueInfos = [
                'empEmail' => $request->email,
                'empID' => $empID,
                'gender' => $request->gender,
                'citizenship' => $request->citizenship,
                'empBdate' => $request->birthdate,
                'empCStatus' => $request->status,
                'empReligion' => $request->religion,
                'empPContact' => $request->homephone,
                'empHContact' => $request->mobile,
                'empAddStreet' => $request->street,
                'empAddCityDesc' => $request->citydesc,
                'empAddCity' => $request->city,
                'empAddBrgyDesc' => $request->brgydesc,
                'empAddBrgy' => $request->barangay,
                'empProvDesc' => $request->provdesc,
                'empProv' => $request->province,
                'empZipcode' => $request->zipcode,
                'empCountry' => $request->country,
                'created_at' => $current_date_time,
                'updated_at' => $current_date_time,
            ];

            $valueEdu = collect([
                [
                    'empID' => $empID,
                    'schoolLevel' => "Primary",
                    'schoolName' => $request->primary_school,
                    'schoolYearStarted' => $request->primary_year_started,
                    'schoolYearEnded' => $request->primary_year_graduated,
                    'schoolAddress' => $request->primary_school_address,
                ],
                [
                    'empID' => $empID,
                    'schoolLevel' => "Secondary",
                    'schoolName' => $request->secondary_school,
                    'schoolYearStarted' => $request->secondary_year_started,
                    'schoolYearEnded' => $request->secondary_year_graduated,
                    'schoolAddress' => $request->secondary_school_address,
                ],
                [
                    'empID' => $empID,
                    'schoolLevel' => "Tertiary",
                    'schoolName' => $request->tertiary_school,
                    'schoolYearStarted' => $request->tertiary_year_started,
                    'schoolYearEnded' => $request->tertiary_year_graduated,
                    'schoolAddress' => $request->tertiary_school_address,
                ]
            ])->map(function ($item) use ($current_date_time) {
                return array_merge($item, [
                    'created_at' => $current_date_time,
                    'updated_at' => $current_date_time,
                ]);
            })->toArray();

            $valueDetails = [
                'empID' => $empID,
                'empISID' => $request->immediate,
                'empDepID' => $request->department,
                'empCompID' => $request->company,
                'empClassification' => $request->classification,
                'empPos' => $request->position,
                'empBasic' => $request->basic,
                'empStatus' => $request->status,
                'empAllowance' => $request->allowance,
                'empHrate' => $request->hourly_rate,
                'empWday' => $request->no_work_days,
                'empJobLevel' => $request->job_level,
                'empAgencyID' => $request->agency,
                'empHMOID' => $request->hmo,
                'empHMONo' => $request->hmo_number,
                'empPicPath' => $request->path->getClientOriginalName(),
                'empDateHired' => $request->date_hired,
                'empDateResigned' => $request->date_resigned,
                'empDateRegular' => $request->date_regularization,
                'empPrevPos' => $request->previous_position,
                'empPrevDep' => $request->previous_department,
                'empPrevDesignation' => $request->previous_designation,
                'empPrevWorkStartDate' => $request->start_date,
                'empPassport' => $request->passport_no,
                'empPassportExpDate' => $request->passport_exp_date,
                'empPassportIssueAuth' => $request->issuing_authority,
                'empPagibig' => $request->pagibig,
                'empPhilhealth' => $request->philhealth,
                'empSSS' => $request->sss,
                'empTIN' => $request->tin,
                'empUMID' => $request->umid,
                'created_at' => $current_date_time,
                'updated_at' => $current_date_time,
            ];

            $valuessysaccess = [
                'empID' => $empID,
                'home' => 1,
                'settings' => 0,
                'rpt_attend' => 0,
            ];

            // Insert all data
            DB::table('users')->insert($valuesUser);
            DB::table('emp_infos')->insert($valueInfos);
            DB::table('emp_educations')->insert($valueEdu);
            DB::table('emp_details')->insert($valueDetails);
            DB::table('access')->insert($valuessysaccess);

            if ($request->hasFile('path')) {
                $imageName = $request->path->getClientOriginalName();
                $request->path->move('img/profile/', $imageName);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'msg' => 'The employee was added successfully!',
                'empID' => $empID
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 203, 'msg' => $e->getMessage()]);
        }
    }

    public function update(Request $request)
    {
        $user = User::where('empID', $request->empID)
            ->first();

        $userEmail = User::where('email', $request->email)
            ->where('empID', '!=', $request->empID) // exclude current user
            ->first();

        if ($userEmail) {
            return response()->json([
                'status' => 201,
                'error' => [
                    'email' => ['The email address is already in use.']
                ]
            ]);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'firstname' => 'required',
            'lastname' => 'required',
            'company' => 'required',
            'gender' => 'required',
            'citizenship' => 'required',
            'status' => 'required',
            'immediate' => 'required',
            'department' => 'required',
            'classification' => 'required',
            'position' => 'required',
            // 'job_level' => 'required',
            'birthdate' => 'required',
            'province' => 'required',
            'mobile' => 'required',
            'city' => 'required',
            'barangay' => 'required',
            'zipcode' => 'required',
            'country' => 'required',
            // 'agency' => 'required',
            // 'hmo' => 'required',
            'no_work_days' => 'required',
            'date_hired' => 'required',
            'basic'=>'required|numeric',
            'allowance'=>'required|numeric',
            
            
        ]);

        if (!$validator->passes()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        DB::beginTransaction();
        try {
            /**
             * 🔒 Generate safe, unique empID
             * Format: COMPANYCODE-YYYY-0001
             */
            $companyCode = strtoupper($request->company);
            $year = now()->format('Y');

            // Lock table to prevent race conditions
            $latestEmp = DB::table('users')
                ->where('empID', 'LIKE', "{$companyCode}-{$year}-%")
                ->lockForUpdate()
                ->orderBy('empID', 'desc')
                ->first();

            if ($latestEmp) {
                // Extract last 4 digits
                $lastNumber = (int) substr($latestEmp->empID, -4);
                $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '0001';
            }

            /**
             * 👇 Insert operations
             */
            $valuesUser = [
                'email' => $request->email,
                'status' => '1',
                'suffix' => $request->suffix,
                'lname' => $request->lastname,
                'fname' => $request->firstname,
                'mname' => $request->middlename,
                'role' => "3",
            ];

            $valueInfos = [
                'empEmail' => $request->email,
                'gender' => $request->gender,
                'citizenship' => $request->citizenship,
                'empBdate' => $request->birthdate,
                'empCStatus' => $request->status,
                'empReligion' => $request->religion,
                'empPContact' => $request->homephone,
                'empHContact' => $request->mobile,
                'empAddStreet' => $request->street,
                'empAddCityDesc' => $request->citydesc,
                'empAddCity' => $request->city,
                'empAddBrgyDesc' => $request->brgydesc,
                'empAddBrgy' => $request->barangay,
                'empProvDesc' => $request->provdesc,
                'empProv' => $request->province,
                'empZipcode' => $request->zipcode,
                'empCountry' => $request->country,
            ];

            $valueEdu = collect([
                [
                    'empID' => $request->empID,
                    'schoolLevel' => "Primary",
                    'schoolName' => $request->primary_school,
                    'schoolYearStarted' => $request->primary_year_started,
                    'schoolYearEnded' => $request->primary_year_graduated,
                    'schoolAddress' => $request->primary_school_address,
                ],
                [
                    'empID' => $request->empID,
                    'schoolLevel' => "Secondary",
                    'schoolName' => $request->secondary_school,
                    'schoolYearStarted' => $request->secondary_year_started,
                    'schoolYearEnded' => $request->secondary_year_graduated,
                    'schoolAddress' => $request->secondary_school_address,
                ],
                [
                    'empID' => $request->empID,
                    'schoolLevel' => "Tertiary",
                    'schoolName' => $request->tertiary_school,
                    'schoolYearStarted' => $request->tertiary_year_started,
                    'schoolYearEnded' => $request->tertiary_year_graduated,
                    'schoolAddress' => $request->tertiary_school_address,
                ]
            ])->toArray();

            $valueDetails = [
                'empISID' => $request->immediate,
                'empDepID' => $request->department,
                'empCompID' => $request->company,
                'empClassification' => $request->classification,
                'empPos' => $request->position,
                'empBasic' => $request->basic,
                'empStatus' => $request->status,
                'empAllowance' => $request->allowance,
                'empHrate' => $request->hourly_rate,
                'empWday' => $request->no_work_days,
                'empJobLevel' => $request->job_level,
                'empAgencyID' => $request->agency,
                'empHMOID' => $request->hmo,
                'empHMONo' => $request->hmo_number,
                'empDateHired' => $request->date_hired,
                'empDateResigned' => $request->date_resigned,
                'empDateRegular' => $request->date_regularization,
                'empPrevPos' => $request->previous_position,
                'empPrevDep' => $request->previous_department,
                'empPrevDesignation' => $request->previous_designation,
                'empPrevWorkStartDate' => $request->start_date,
                'empPassport' => $request->passport_no,
                'empPassportExpDate' => $request->passport_exp_date,
                'empPassportIssueAuth' => $request->issuing_authority,
                'empPagibig' => $request->pagibig,
                'empPhilhealth' => $request->philhealth,
                'empSSS' => $request->sss,
                'empTIN' => $request->tin,
                'empUMID' => $request->umid,
            ];


            $user->education()->delete();

            // Insert all data
            DB::table('users')
                ->where('empID', $request->empID)
                ->update($valuesUser);
            DB::table('emp_infos')
                ->where('empID', $request->empID)
                ->update($valueInfos);
            DB::table('emp_educations')->insert($valueEdu);
            DB::table('emp_details')
                ->where('empID', $request->empID)
                ->update($valueDetails);

            if ($request->hasFile('path')) {
                $imageName = $request->path->getClientOriginalName();
                $request->path->move('img/profile/', $imageName);
                DB::table('emp_details')
                    ->where('empID', $request->empID)
                    ->update([
                        'empPicPath' => $request->path->getClientOriginalName(),
                    ]);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'msg' => 'The employee was added successfully!',
                'empID' => $request->empID
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 203, 'msg' => $e->getMessage()]);
        }
    }
    // Check if email is already taken (AJAX) Feb 18 2026
    public function checkEmailAvailability(Request $request) {

        $email = $request->email;  
        $userExists = User::where('email', $email)->exists();

        if ($userExists) {
            echo json_encode(['exists' => true]);
        } else {
            echo json_encode(['exists' => false]);
        }
    }

    public function checkFullName(Request $request) {
        $exists = User::where('fname', $request->firstname)
                    ->where('lname', $request->lastname)
                    ->exists();
        return response()->json(['exists' => $exists]);
    }

}


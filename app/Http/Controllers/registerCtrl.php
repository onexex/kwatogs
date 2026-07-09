<?php

namespace App\Http\Controllers;

// use App\Models\access;
// use App\Models\emp_education;
// use App\Models\emp_info;

// use App\Models\empDetail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class registerCtrl extends Controller
{
    /**
     * Build a safe, collision-free profile picture filename derived from the
     * employee's ID. Using the raw client filename (getClientOriginalName) is
     * unsafe — two employees uploading "photo.jpg" overwrite each other, and a
     * crafted name can attempt path traversal. We keep only the extension.
     */
    private function profileImageName(Request $request, string $empID): string
    {
        $ext  = strtolower($request->file('path')->getClientOriginalExtension() ?: 'jpg');
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '', $empID);
        return "{$slug}_" . time() . ".{$ext}";
    }

    /**
     * Build the three education rows, skipping any level the user left blank
     * (no school name) so we don't persist empty placeholder rows.
     */
    private function buildEducation(Request $request, string $empID, $timestamp = null): array
    {
        $levels = [
            'Primary'   => 'primary',
            'Secondary' => 'secondary',
            'Tertiary'  => 'tertiary',
        ];

        $rows = [];
        foreach ($levels as $label => $prefix) {
            $school = $request->input("{$prefix}_school");
            if (empty($school)) {
                continue; // nothing entered for this level — don't store an empty row
            }

            $row = [
                'empID'             => $empID,
                'schoolLevel'       => $label,
                'schoolName'        => $school,
                'schoolYearStarted' => $request->input("{$prefix}_year_started"),
                'schoolYearEnded'   => $request->input("{$prefix}_year_graduated"),
                'schoolAddress'     => $request->input("{$prefix}_school_address"),
            ];

            if ($timestamp) {
                $row['created_at'] = $timestamp;
                $row['updated_at'] = $timestamp;
            }

            $rows[] = $row;
        }

        return $rows;
    }
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

    /**
     * Candidate login handles in priority order (ASCII, lowercase, punctuation
     * stripped so multi-word names collapse to one token):
     *   1. first initial + surname        ("Juan Dela Cruz" -> "jdelacruz")
     *   2. surname initial + first name    (collision swap: "Realyn Cuenca"
     *      -> "crealyn", "Roselyn Cuenca" -> "croselyn")
     * Falls back to the employee ID. Mirrors the one-time backfill migration.
     */
    private function usernameCandidates(?string $fname, ?string $lname, string $empID): array
    {
        $first = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', Str::ascii((string) $fname)));
        $last  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', Str::ascii((string) $lname)));

        $candidates = [];
        if ($first !== '' && $last !== '') {
            $candidates[] = substr($first, 0, 1) . $last;  // jdelacruz
            $candidates[] = substr($last, 0, 1) . $first;  // crealyn (interchanged)
        } elseif ($last !== '') {
            $candidates[] = $last;
        } elseif ($first !== '') {
            $candidates[] = $first;
        }

        $emp = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $empID));
        if ($emp !== '') {
            $candidates[] = $emp;
        }

        $out = [];
        foreach ($candidates as $c) {
            $c = substr($c, 0, 50);
            if ($c !== '' && ! in_array($c, $out, true)) {
                $out[] = $c;
            }
        }

        return $out;
    }

    /**
     * Build a unique login username: try each candidate format as-is, and only
     * if all are taken fall back to a numeric suffix on the primary format.
     */
    private function generateUsername(?string $fname, ?string $lname, string $empID): ?string
    {
        $candidates = $this->usernameCandidates($fname, $lname, $empID);
        if (! $candidates) {
            return null; // nothing usable — leave NULL, employee logs in by email
        }

        foreach ($candidates as $c) {
            if (! DB::table('users')->where('username', $c)->exists()) {
                return $c;
            }
        }

        // Every preferred format is taken — number the primary one.
        $base = $candidates[0];
        $i = 1;
        do {
            $suffix   = (string) $i++;
            $username = substr($base, 0, 50 - strlen($suffix)) . $suffix;
        } while (DB::table('users')->where('username', $username)->exists());

        return $username;
    }

    public function create(Request $request)
    {

        $defaultpass = "123456";
        $current_date_time = now();
        // dd($request->path->getClientOriginalName()); sdsd

        $validator = Validator::make($request->all(), [
            'email' => 'required|unique:users',
            // Optional short login handle; letters/numbers/dash/underscore only.
            'username' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'firstname' => [
                'required',
                 'string',
                'min:2',           
                'max:50',          
                'regex:/^[a-zA-Z\sñÑ-]+$/', 
                Rule::unique('users', 'fname')->where(function ($query) use ($request) {
                    return $query->where('lname', $request->lastname);
                }),
            ],
            // 'lastname' => 'required',
            'lastname' => [
                'required',
                'string',
                'min:2',           
                'max:50',          
                'regex:/^[a-zA-Z\sñÑ-]+$/', 
            ],
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
            'mobile' => [
                'required',
                'numeric',
                'digits:11',
                'regex:/^09\d{9}$/',
            ],
            'city' => 'required',
            'barangay' => 'required',
            'zipcode' => 'required|numeric',
            'country' => 'required',
            // 'agency' => 'required',
            // 'hmo' => 'required',
            // 'no_work_days' => 'required',
            'date_hired' => 'required',
            'basic'=>'required|numeric',
            'allowance'=>'required|numeric',

            //education
            'primary_school' => 'string|nullable',
            'primary_year_started' => 'nullable|numeric',
            'primary_year_graduated' => 'nullable|numeric',
            'secondary_school' => 'string|nullable',
            'secondary_year_started' => 'nullable|numeric',
            'secondary_year_graduated' => 'nullable|numeric',
            'tertiary_school' => 'string|nullable',
            'tertiary_year_started' => 'nullable|numeric',
            'tertiary_year_graduated' => 'nullable|numeric',

            //compliance
            'philhealth' => 'nullable|digits:12', // PhilHealth PIN is 12 digits
            'pagibig'    => 'nullable|digits:12', // Pag-IBIG MID is 12 digits
            'sss'        => 'nullable|digits:10', // SSS Number is 10 digits
            'tin'        => 'nullable|digits:9',  // Standard TIN is 9 (minsan 12 kung may branch code)
            'umid'       => 'nullable|digits:12', // UMID is 12 digits

             //profile picture
             'path' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            
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
                // Use the admin-typed username, otherwise auto-generate "first
                // initial + surname" (e.g. Juan Dela Cruz -> jdelacruz).
                'username' => $request->username ?: $this->generateUsername($request->firstname, $request->lastname, $empID),
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

            $valueEdu = $this->buildEducation($request, $empID, $current_date_time);

            // Safe, unique profile picture filename (never trust the client name).
            $imageName = $this->profileImageName($request, $empID);

            $valueDetails = [
                'empID' => $empID,
                'empISID' => $request->immediate,
                'empDepID' => $request->department,
                'empCompID' => $request->company,
                'empClassification' => $request->classification,
                'empPos' => $request->position,
                'empBasic' => $request->basic,
                // New hires default to Employed (1). The enroll form has no emp_status field,
                // so fall back to 1 rather than the Civil Status value ($request->status).
                'empStatus' => $request->input('emp_status', 1),
                'empAllowance' => $request->allowance,
                'empPayrollType' => $request->payroll_type ?: 'CASH',
                'empCardNo' => ($request->payroll_type === 'CARD') ? $request->card_number : null,
                'empHrate' => $request->hourly_rate,
                // 'empWday' => $request->no_work_days,
                'empWday' => 8,
                'empJobLevel' => $request->job_level,
                'empAgencyID' => $request->agency,
                'empHMOID' => $request->hmo,
                'empHMONo' => $request->hmo_number,
                'empPicPath' => $imageName,
                'empDateHired' => $request->date_hired,
                'empDateResigned' => $request->emp_status == 1 ? null : $request->date_resigned,
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
            if (!empty($valueEdu)) {
                DB::table('emp_educations')->insert($valueEdu);
            }
            DB::table('emp_details')->insert($valueDetails);
            DB::table('access')->insert($valuessysaccess);

            // Audit the new employee (inserts via query builder bypass model events).
            \App\Models\AuditLog::record('created', 'empDetail', $empID, $valueDetails);

            // If this onboarding was launched from the Applicant module's "Hire"
            // action, flag that applicant as hired and link it to the new employee.
            if ($request->filled('applicant_id')) {
                $applicant = \App\Models\Applicant::find($request->applicant_id);
                if ($applicant && $applicant->status !== 'hired') {
                    $applicant->forceFill([
                        'status'      => 'hired',
                        'hired_empID' => $empID,
                        'hired_at'    => $current_date_time,
                        'reviewed_by' => auth()->user()
                            ? (trim((auth()->user()->fname ?? '') . ' ' . (auth()->user()->lname ?? '')) ?: 'HR')
                            : 'HR',
                    ])->save();
                }
            }

            if ($request->hasFile('path')) {
                // Absolute path to the real served public/ folder. More robust than a
                // relative path here: the deploy ships the whole app (public/ included)
                // and routes through it via the root .htaccess, so public_path() always
                // resolves to .../public regardless of PHP's CWD (mod_php vs PHP-FPM).
                $request->file('path')->move(public_path('img/profile'), $imageName);
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

        // Guard against a missing/invalid empID so we don't hit a null-method
        // fatal (e.g. $user->education()) further down.
        if (!$user) {
            return response()->json([
                'status' => 203,
                'msg'    => 'Employee not found.',
            ]);
        }

        $validator = Validator::make($request->all(), [
            // Email must be valid and unique, ignoring this employee's own row.
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($request->empID, 'empID'),
            ],
            // Optional short login handle; unique across users, ignoring this row.
            'username' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($request->empID, 'empID'),
            ],
            'firstname' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-Z\sñÑ-]+$/',
            ],
            'lastname' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-Z\sñÑ-]+$/',
            ],
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
            'mobile' => [
                'required',
                'numeric',
                'digits:11',
                'regex:/^09\d{9}$/',
            ],
            'city' => 'required',
            'barangay' => 'required',
            'zipcode' => 'required|numeric',
            'country' => 'required',
            // 'agency' => 'required',
            // 'hmo' => 'required',
            // 'no_work_days' => 'required',
            'date_hired' => 'required',
            'basic'=>'required|numeric',
            'allowance'=>'required|numeric',

            // Government IDs — same digit rules as registration.
            'philhealth' => 'nullable|digits:12',
            'pagibig'    => 'nullable|digits:12',
            'sss'        => 'nullable|digits:10',
            'tin'        => 'nullable|digits:9',
            'umid'       => 'nullable|digits:12',

            // Profile picture is optional on update, but if supplied must be a valid image.
            'path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            // Resignation date is mandatory when the employee is marked Resigned.
            'date_resigned' => 'nullable|required_if:emp_status,0',
        ], [
            'date_resigned.required_if' => 'Date Resigned is required when the status is Resigned.',
            'email.unique'              => 'The email address is already in use.',
        ]);

        if (!$validator->passes()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        DB::beginTransaction();
        try {
            /**
             * 👇 Update operations (empID is fixed on update — never regenerated)
             */
            $valuesUser = [
                'email' => $request->email,
                'username' => $request->username ?: null,
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
                // 'empReligion' => $request->religion,
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

            $valueEdu = $this->buildEducation($request, $request->empID);

            $valueDetails = [
                'empISID' => $request->immediate,
                'empDepID' => $request->department,
                'empCompID' => $request->company,
                'empClassification' => $request->classification,
                'empPos' => $request->position,
                'empBasic' => $request->basic,
                // Employment status comes from the "emp_status" dropdown (1 = Employed, 0 = Resigned).
                // Previously this read $request->status, which is the Civil Status field — so changing
                // employment status to Resigned never saved. Fixed to read emp_status.
                'empStatus' => $request->emp_status,
                'empAllowance' => $request->allowance,
                'empPayrollType' => $request->payroll_type ?: 'CASH',
                'empCardNo' => ($request->payroll_type === 'CARD') ? $request->card_number : null,
                'empHrate' => $request->hourly_rate,
                // 'empWday' => $request->no_work_days,
                'empWday' => 8,
                // 'empJobLevel' => $request->job_level,
                // 'empAgencyID' => $request->agency,
                // 'empHMOID' => $request->hmo,
                // 'empHMONo' => $request->hmo_number,
                'empDateHired' => $request->date_hired,
                'empDateResigned' => $request->emp_status == 1 ? null : $request->date_resigned,
                'empDateRegular' => $request->date_regularization,
                'empPrevPos' => $request->previous_position,
                'empPrevDep' => $request->previous_department,
                'empPrevDesignation' => $request->previous_designation,
                'empPrevWorkStartDate' => $request->start_date,
                // 'empPassport' => $request->passport_no,
                // 'empPassportExpDate' => $request->passport_exp_date,
                // 'empPassportIssueAuth' => $request->issuing_authority,
                'empPagibig' => $request->pagibig,
                'empPhilhealth' => $request->philhealth,
                'empSSS' => $request->sss,
                'empTIN' => $request->tin,
                'empUMID' => $request->umid,
            ];


            $user->education()->delete();

            // Snapshot current values BEFORE updating so we can record before→after
            // diffs. These tables are written via the query builder, which bypasses
            // the Auditable model events, so we log the changes manually.
            $oldUser    = (array) DB::table('users')->where('empID', $request->empID)->first();
            $oldInfo    = (array) DB::table('emp_infos')->where('empID', $request->empID)->first();
            $oldDetail  = (array) DB::table('emp_details')->where('empID', $request->empID)->first();

            // Insert all data
            DB::table('users')
                ->where('empID', $request->empID)
                ->update($valuesUser);
            DB::table('emp_infos')
                ->where('empID', $request->empID)
                ->update($valueInfos);
            if (!empty($valueEdu)) {
                DB::table('emp_educations')->insert($valueEdu);
            }
            DB::table('emp_details')
                ->where('empID', $request->empID)
                ->update($valueDetails);

            // Record audit entries (one per table) for whatever actually changed.
            $userChanges   = \App\Models\AuditLog::diff($oldUser, $valuesUser);
            $infoChanges   = \App\Models\AuditLog::diff($oldInfo, $valueInfos);
            $detailChanges = \App\Models\AuditLog::diff($oldDetail, $valueDetails);
            if (!empty($userChanges))   \App\Models\AuditLog::record('updated', 'User', $request->empID, $userChanges);
            if (!empty($infoChanges))   \App\Models\AuditLog::record('updated', 'emp_info', $request->empID, $infoChanges);
            if (!empty($detailChanges)) \App\Models\AuditLog::record('updated', 'empDetail', $request->empID, $detailChanges);

            if ($request->hasFile('path')) {
                // Safe, unique filename (never trust the client-supplied name).
                // public_path() targets the real served public/ folder deterministically.
                $imageName = $this->profileImageName($request, $request->empID);
                $request->file('path')->move(public_path('img/profile'), $imageName);
                DB::table('emp_details')
                    ->where('empID', $request->empID)
                    ->update([
                        'empPicPath' => $imageName,
                    ]);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'msg' => 'The employee was updated successfully!',
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


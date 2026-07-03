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
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Models\EmployeeDocument;
use Illuminate\Support\Facades\Hash;
use App\Services\OffboardingClearanceService;

class EmployeeRecordController extends Controller
{
    /** Employment documents live under public/docs/employees/<user_id>/. */
    private const DOC_DIR = 'docs/employees';

    /** Allowed document categories — kept in sync with the E-201 upload dropdown. */
    public const DOC_TYPES = [
        'Employment Contract',
        'Government ID',
        'Resume/CV',
        'Certificate',
        'Clearance',
        'Other',
    ];

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
            'employeeInformation',
            'education',
            'employmentDocuments'
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
            : null;

        return response()->json([
            'status' => 200,
            'data' => $user,
            'image_url' => $fullUrl,
            'gender'    => $user->employeeInformation->gender ?? null,
        ]);
    }

        return response()->json(['status' => 404, 'message' => 'Record not found']);
    }

    /**
     * Admin "forgot password" reset, triggered from the E-201 Personnel Viewer.
     * Resets the account to the same default temp password used for new hires
     * ("123456") and raises the must_change_password flag, so the employee is
     * forced through ForcePasswordChange to set their own private password on
     * next login — the admin never learns the final password.
     */
    public function resetPassword(User $user)
    {
        $tempPassword = '123456';

        try {
            $user->update([
                'password'             => Hash::make($tempPassword),
                'must_change_password' => true,
            ]);

            return response()->json([
                'status'        => 200,
                'message'       => "Password reset for {$user->fname} {$user->lname}. They must set a new password on their next login.",
                'temp_password' => $tempPassword,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Failed to reset password: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * E-201 "Update Status" — manage an employee's employment exit and/or flag.
     *
     * Two independent layers handled in one call:
     *  - Employment exit: empStatus '1'=Employed, '0'=Resigned, '2'=End of Contract.
     *    Payroll already filters empStatus='1', so a non-'1' status auto-deactivates and
     *    excludes from payroll. Resigned/EOC also capture a reason + date and freeze the
     *    employee's years rendered (Date Hired -> separation date). Re-selecting Employed
     *    reactivates and clears those exit fields.
     *  - Flag: 'redflag' | 'blacklist' (or none). A flag is independent of employment state —
     *    it can mark a still-active employee and never touches empStatus / payroll.
     */
    public function updateStatus(Request $request, User $user, OffboardingClearanceService $clearance)
    {
        $data = $request->validate([
            'emp_status'         => 'required|in:1,0,2',
            'separation_date'    => 'required_unless:emp_status,1|nullable|date',
            'separation_reason'  => 'required_unless:emp_status,1|nullable|string|max:1000',
            'flag_status'        => 'nullable|in:redflag,blacklist',
            'flag_reason'        => 'required_with:flag_status|nullable|string|max:1000',
            // Offboarding clearance — checked item keys + optional per-item reference notes
            'clearance'          => 'nullable|array',
            'clearance.*'        => 'string',
            'clearance_refs'     => 'nullable|array',
            'clearance_refs.*'   => 'nullable|string|max:255',
            // Optional proof file per clearance item (keyed by item key) — attached during offboarding.
            'clearance_files'    => 'nullable|array',
            'clearance_files.*'  => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $detail = $user->empDetail;
        if (!$detail) {
            return response()->json([
                'status'  => 404,
                'message' => 'Employment record not found for this employee.',
            ], 404);
        }

        try {
            $isExit = $data['emp_status'] !== '1';

            // --- Employment-exit layer ---
            $detail->empStatus = $data['emp_status'];
            if ($isExit) {
                $sepDate = Carbon::parse($data['separation_date']);
                $detail->separation_date   = $sepDate->toDateString();
                $detail->empDateResigned   = $sepDate->toDateString(); // mirror for legacy reports
                $detail->separation_reason = $data['separation_reason'];
                $detail->years_rendered    = $detail->empDateHired
                    ? round(Carbon::parse($detail->empDateHired)->floatDiffInYears($sepDate), 2)
                    : 0;

                // --- Offboarding clearance checklist ---
                // Reset all flags, then tick only the items that both APPLY to this
                // exit type and were checked — a non-applicable item can never be set.
                $checked = $data['clearance'] ?? [];
                $refsIn  = $request->input('clearance_refs', []);
                foreach (OffboardingClearanceService::columns() as $col) {
                    $detail->{$col} = false;
                }
                $cleanRefs = [];
                $clFiles   = $request->file('clearance_files', []);
                foreach ($clearance->applicableItems($data['emp_status']) as $key => $item) {
                    $detail->{$item['column']} = in_array($key, $checked, true);
                    if (!empty($refsIn[$key])) {
                        $cleanRefs[$key] = (string) $refsIn[$key];
                    }
                    // Attach the uploaded proof file for this clearance item, if any —
                    // replacing the previous attachment (file + row) for the same key.
                    if (!empty($clFiles[$key])) {
                        EmployeeDocument::where('user_id', $user->id)
                            ->where('clearance_key', $key)
                            ->get()
                            ->each(function ($old) {
                                if ($old->file_path && is_file(public_path($old->file_path))) {
                                    @unlink(public_path($old->file_path));
                                }
                                $old->delete();
                            });
                        $this->persistDocument($user, $clFiles[$key], 'Clearance', $item['label'], $key);
                    }
                }
                $actor = auth()->user();
                $detail->clearance_refs = $cleanRefs ?: null;
                $detail->cleared_by     = $actor
                    ? (trim(($actor->fname ?? '') . ' ' . ($actor->lname ?? '')) ?: 'Admin')
                    : 'Admin';
                $detail->cleared_at     = now()->toDateString();
            } else {
                // Reactivated — clear the exit snapshot + the offboarding clearance
                $detail->separation_date   = null;
                $detail->empDateResigned   = null;
                $detail->separation_reason = null;
                $detail->years_rendered    = null;

                foreach (OffboardingClearanceService::columns() as $col) {
                    $detail->{$col} = false;
                }
                $detail->clearance_refs = null;
                $detail->cleared_by     = null;
                $detail->cleared_at     = null;
            }

            // --- Flag layer (independent of employment state) ---
            if (!empty($data['flag_status'])) {
                $actor = auth()->user();
                $detail->flag_status = $data['flag_status'];
                $detail->flag_reason = $data['flag_reason'];
                $detail->flagged_at  = now()->toDateString();
                $detail->flagged_by  = $actor
                    ? trim(($actor->fname ?? '') . ' ' . ($actor->lname ?? '')) ?: 'Admin'
                    : 'Admin';
            } else {
                $detail->flag_status = null;
                $detail->flag_reason = null;
                $detail->flagged_at  = null;
                $detail->flagged_by  = null;
            }

            // Instance save so the Auditable trait records the before->after diff.
            $detail->save();

            return response()->json([
                'status'  => 200,
                'message' => "Status updated for {$user->fname} {$user->lname}.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload one employment document (PDF or image) into an employee's 201 file.
     * Gated by can:manageemployeedocuments at the route. Size/mime/name are captured
     * BEFORE move() (the temp file is gone afterwards); create() goes through the
     * Auditable trait so every upload is recorded on the audit trail.
     */
    public function uploadDocument(Request $request, User $user)
    {
        $data = $request->validate([
            'doc_type' => ['required', Rule::in(self::DOC_TYPES)],
            'label'    => 'nullable|max:191',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10 MB
        ]);

        $doc = $this->persistDocument($user, $request->file('document'), $data['doc_type'], $data['label'] ?? null);

        return response()->json(['status' => 200, 'msg' => 'Document uploaded.', 'data' => $doc]);
    }

    /**
     * Move an uploaded file into the employee's 201-file folder and record it.
     * Size/mime/name are read BEFORE move() (temp file is gone afterwards); create()
     * goes through the Auditable trait. Shared by the Documents panel and the
     * Update-Status clearance attachments.
     */
    private function persistDocument(User $user, \Illuminate\Http\UploadedFile $file, string $docType, ?string $label = null, ?string $clearanceKey = null): EmployeeDocument
    {
        $size = $file->getSize();
        $mime = $file->getClientMimeType();
        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'dat'); // already whitelisted by the mimes rule

        $dir = public_path(self::DOC_DIR.'/'.$user->id);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $stored = time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $file->move($dir, $stored);

        $actor = auth()->user();
        return EmployeeDocument::create([
            'user_id'       => $user->id,
            'empID'         => $user->empID,
            'doc_type'      => $docType,
            'clearance_key' => $clearanceKey,
            'label'         => $label ?: $originalName,
            'file_name'     => $stored,
            'file_path'     => self::DOC_DIR.'/'.$user->id.'/'.$stored,
            'original_name' => $originalName,
            'size'          => $size ?: null,
            'mime'          => $mime,
            'uploaded_by'   => $actor ? (trim(($actor->fname ?? '').' '.($actor->lname ?? '')) ?: 'Admin') : 'Admin',
        ]);
    }

    /**
     * Stream an employment document back to the browser as a download.
     * Owner may fetch their own file; managers may fetch anyone's. This closes the
     * IDOR gap — the route lives in the general auth group, not an admin-only one.
     */
    public function downloadDocument(EmployeeDocument $document)
    {
        $u = auth()->user();
        $isOwner   = $u && (int) $u->id === (int) $document->user_id;
        $canManage = $u && ($u->can('admine201') || $u->can('manageemployeedocuments') || (isset($u->role) && (int) $u->role === 1));
        abort_unless($isOwner || $canManage, 403);

        $abs = public_path($document->file_path);
        abort_unless(is_file($abs), 404);

        return response()->download($abs, $document->original_name);
    }

    /** Delete an employment document (DB row + file on disk). Gated by permission. */
    public function deleteDocument(EmployeeDocument $document)
    {
        if ($document->file_path && is_file(public_path($document->file_path))) {
            @unlink(public_path($document->file_path));
        }
        $document->delete();   // instance delete → Auditable

        return response()->json(['status' => 200, 'msg' => 'Document deleted.']);
    }
}
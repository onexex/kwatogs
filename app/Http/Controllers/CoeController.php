<?php

namespace App\Http\Controllers;

use App\Models\CoeRequest;
use App\Models\CoeSignatory;
use App\Models\empDetail;
use App\Services\CoePdfService;
use App\Services\CoeService;
use App\Services\OffboardingClearanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Certificate of Employment module.
 *  - HR admin methods are gated by the `coemanagement` permission.
 *  - Employee "My COE" methods are auth-only (every employee manages their own).
 *  - PDF download is allowed for the owning employee OR a COE manager.
 */
class CoeController extends Controller
{
    /* ─────────────────────────── HR ADMIN ─────────────────────────── */

    public function index(CoeService $service)
    {
        return view('pages.modules.coe', ['d' => $service->dashboard()]);
    }

    /** JSON: all requests with employee names, filterable. */
    public function list(Request $request)
    {
        $q = DB::table('coe_requests as r')
            ->leftJoin('users as u', 'u.empID', '=', 'r.employee_id')
            ->selectRaw("r.id, r.employee_id, r.purpose, r.copies, r.date_needed, r.remarks,
                r.status, r.include_salary, r.certificate_no, r.signatory_name, r.signatory_title,
                r.reviewed_by, r.reviewed_at, r.rejection_reason, r.created_at,
                TRIM(CONCAT(u.lname, ', ', u.fname)) as employee_name");

        if ($request->filled('status')) {
            $q->where('r.status', $request->status);
        }
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $q->where(function ($w) use ($s) {
                $w->where('u.lname', 'like', $s)->orWhere('u.fname', 'like', $s)
                  ->orWhere('r.employee_id', 'like', $s)->orWhere('r.purpose', 'like', $s)
                  ->orWhere('r.certificate_no', 'like', $s);
            });
        }

        return response()->json(['status' => 200, 'data' => $q->orderByDesc('r.created_at')->limit(500)->get()]);
    }

    /** HR approves a request: stamp the chosen signatory, freeze snapshot, assign ref no. */
    public function approve(Request $request, CoeService $service)
    {
        $validator = Validator::make($request->all(), [
            'id'           => 'required|exists:coe_requests,id',
            'signatory_id' => 'required|exists:coe_signatories,id',
            'include_salary' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $coe = CoeRequest::findOrFail($request->id);
        if ($coe->status !== 'pending') {
            return response()->json(['status' => 202, 'msg' => 'This request has already been reviewed.']);
        }

        $signatory = CoeSignatory::findOrFail($request->signatory_id);

        // A certificate is signed by freezing the signatory's signature image at
        // approval time. If the signatory has no usable signature on file, block
        // approval — otherwise we'd mint a blank-signed COE that can't be re-signed
        // later (the freeze is one-time).
        $signatureUri = $signatory->signatureDataUri();
        if (!$signatureUri) {
            return response()->json([
                'status' => 202,
                'msg'    => "The selected signatory ({$signatory->name}) has no signature image on file. "
                          . "Upload a signature under Settings → COE Signatories, then approve.",
            ]);
        }

        $includeSalary = (bool) $request->input('include_salary', false);
        $user = auth()->user();

        $coe->forceFill([
            'status'           => 'approved',
            'include_salary'   => $includeSalary,
            'snapshot'         => $service->buildSnapshot($coe->employee_id, $includeSalary),
            'certificate_no'   => $coe->certificate_no ?: $service->nextCertificateNo(),
            // Freeze the chosen signatory's name/title/signature onto the certificate.
            'signatory_name'   => $signatory->name,
            'signatory_title'  => $signatory->title,
            'signature_data'   => $signatureUri,
            'reviewed_by'      => $user ? (trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'HR')) : 'HR',
            'reviewed_at'      => Carbon::now(),
            'rejection_reason' => null,
        ])->save();   // instance save → Auditable

        return response()->json(['status' => 200, 'msg' => 'COE approved. The employee can now download it.']);
    }

    /** HR rejects a request with a reason. */
    public function reject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:coe_requests,id',
            'reason' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $coe = CoeRequest::findOrFail($request->id);
        if ($coe->status !== 'pending') {
            return response()->json(['status' => 202, 'msg' => 'This request has already been reviewed.']);
        }

        $user = auth()->user();
        $coe->forceFill([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
            'reviewed_by'      => $user ? (trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'HR')) : 'HR',
            'reviewed_at'      => Carbon::now(),
        ])->save();   // instance save → Auditable

        return response()->json(['status' => 200, 'msg' => 'COE request rejected.']);
    }

    /**
     * JSON: separated employees (Resigned / End of Contract) with their offboarding
     * clearance status, for the "Issue COE" picker. Separated staff can't log in to
     * self-serve, so HR issues on their behalf.
     */
    public function separatedEmployees(OffboardingClearanceService $clearance, CoeService $service)
    {
        $rows = empDetail::with('user')
            ->whereIn('empStatus', ['0', '2'])
            ->get()
            ->map(function ($d) use ($clearance, $service) {
                $u = $d->user;
                $blockers = $service->separatedIssueBlockers($d, $clearance);
                return [
                    'empid'     => $d->empID,
                    // ALL CAPS "LASTNAME, FIRSTNAME" for the picker.
                    'name'      => strtoupper($u ? trim(($u->lname ?? '') . ', ' . ($u->fname ?? '')) : (string) $d->empID),
                    'status'    => (string) $d->empStatus === '0' ? 'Resigned' : 'End of Contract',
                    'clearance' => $clearance->statusFor($d),
                    'complete'  => count($blockers) === 0,
                    'missing'   => $blockers,
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return response()->json(['status' => 200, 'data' => $rows]);
    }

    /**
     * HR issues a COE for a separated employee in one step (no pending row).
     * Gated: the employee must be separated AND their offboarding clearance complete.
     */
    public function issue(Request $request, CoeService $service, OffboardingClearanceService $clearance)
    {
        $validator = Validator::make($request->all(), [
            'employee_id'    => 'required|string|exists:emp_details,empID',
            'purpose'        => 'required|string|max:200',
            'copies'         => 'required|integer|min:1|max:20',
            'include_salary' => 'nullable|boolean',
            'signatory_id'   => 'required|exists:coe_signatories,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $detail = empDetail::where('empID', $request->employee_id)->first();
        if (!$detail) {
            return response()->json(['status' => 202, 'msg' => 'Employee record not found.']);
        }
        // Active employees use the self-service flow — this path is for separated staff.
        if ((string) $detail->empStatus === '1') {
            return response()->json(['status' => 202, 'msg' => 'This employee is active; active employees request their own COE.']);
        }
        // Gate: complete certificate facts + not blacklisted + offboarding clearance complete.
        $blockers = $service->separatedIssueBlockers($detail, $clearance);
        if (count($blockers) > 0) {
            return response()->json(['status' => 202, 'msg' => 'Cannot issue COE — outstanding requirements.', 'missing' => $blockers]);
        }

        $signatory = CoeSignatory::findOrFail($request->signatory_id);

        // Same guard as approve(): don't issue a certificate whose signatory has
        // no signature image on file (would produce a blank-signed COE).
        $signatureUri = $signatory->signatureDataUri();
        if (!$signatureUri) {
            return response()->json([
                'status' => 202,
                'msg'    => "The selected signatory ({$signatory->name}) has no signature image on file. "
                          . "Upload a signature under Settings → COE Signatories, then issue.",
            ]);
        }

        $includeSalary = (bool) $request->input('include_salary', false);
        $user = auth()->user();

        CoeRequest::create([
            'employee_id'     => $request->employee_id,
            'purpose'         => $request->purpose,
            'copies'          => $request->copies,
            'status'          => 'approved',
            'include_salary'  => $includeSalary,
            'snapshot'        => $service->buildSnapshot($request->employee_id, $includeSalary),
            'certificate_no'  => $service->nextCertificateNo(),
            'signatory_name'  => $signatory->name,
            'signatory_title' => $signatory->title,
            'signature_data'  => $signatureUri,
            'reviewed_by'     => $user ? (trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'HR')) : 'HR',
            'reviewed_at'     => Carbon::now(),
        ]);   // create → Auditable

        return response()->json(['status' => 200, 'msg' => 'COE issued. You can now download/print it.']);
    }

    /* ─────────────────────────── EMPLOYEE ─────────────────────────── */

    /** Employee view: their own requests + the request form. */
    public function mine(CoeService $service)
    {
        return view('pages.modules.my_coe', [
            'requirements' => $service->requirements(auth()->user()->empID),
        ]);
    }

    /** JSON: the logged-in employee's own requests. */
    public function myList()
    {
        $rows = CoeRequest::where('employee_id', auth()->user()->empID)
            ->orderByDesc('created_at')
            ->get(['id', 'purpose', 'copies', 'date_needed', 'remarks', 'status',
                   'certificate_no', 'rejection_reason', 'reviewed_at', 'created_at']);

        return response()->json(['status' => 200, 'data' => $rows]);
    }

    /** JSON: the eligibility gate for the request button. */
    public function requirements(CoeService $service)
    {
        return response()->json(['status' => 200, 'data' => $service->requirements(auth()->user()->empID)]);
    }

    /** Employee raises a request (server re-checks the gate). */
    public function store(Request $request, CoeService $service)
    {
        $empId = auth()->user()->empID;

        // Re-enforce the eligibility gate server-side — the UI is not the guard.
        $gate = $service->requirements($empId);
        if (!$gate['ok']) {
            return response()->json(['status' => 202, 'msg' => 'You do not meet the requirements to request a COE.', 'missing' => $gate['missing']]);
        }

        $validator = Validator::make($request->all(), [
            'purpose'     => 'required|string|max:200',
            'copies'      => 'required|integer|min:1|max:20',
            'date_needed' => 'nullable|date|after_or_equal:today',
            'remarks'     => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        CoeRequest::create([
            'employee_id' => $empId,
            'purpose'     => $request->purpose,
            'copies'      => $request->copies,
            'date_needed' => $request->date_needed,
            'remarks'     => $request->remarks,
            'status'      => 'pending',
        ]);

        return response()->json(['status' => 200, 'msg' => 'COE request submitted. HR will review it shortly.']);
    }

    /* ─────────────────────────── PDF ─────────────────────────── */

    /**
     * Render the certificate. Allowed for the owning employee or a COE manager.
     * Pass ?preview=1 to display the PDF inline in the browser (for the preview
     * modal); otherwise it downloads as an attachment.
     */
    public function pdf(Request $request, CoeRequest $coe, CoePdfService $pdf)
    {
        $user = auth()->user();
        $isOwner   = $user && $user->empID === $coe->employee_id;
        $isManager = $user && $user->can('coemanagement');

        abort_unless($isOwner || $isManager, 403);
        abort_unless($coe->status === 'approved', 404, 'This certificate is not available for download.');

        $bytes    = $pdf->generate($coe);
        $filename = ($coe->certificate_no ?: 'COE-' . $coe->employee_id) . '.pdf';
        $inline   = $request->boolean('preview');

        return response($bytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '"',
        ]);
    }
}

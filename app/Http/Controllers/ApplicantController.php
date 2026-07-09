<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\department;
use App\Models\position;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Applicant Tracking / Recruitment module (gated by `applicantmanagement`).
 *
 * HR records an applicant with only the initial important data. Applicants sit
 * in the "pool" and are searchable by desired position (e.g. when a Dishwasher
 * role opens, HR searches the pool and contacts a match). When HR hires an
 * applicant, they are sent to the full onboarding form pre-filled with the
 * applicant's data (registration screen), which creates the real employee.
 */
class ApplicantController extends Controller
{
    /** Main screen: stats + reference dropdowns. */
    public function index()
    {
        return view('pages.modules.applicants', [
            'stats'       => $this->stats(),
            'departments' => department::orderBy('dep_name')->get(['id', 'dep_name']),
            'positions'   => position::orderBy('pos_desc')->get(['id', 'pos_desc']),
        ]);
    }

    /** Aggregate counts for the stat cards. */
    private function stats(): array
    {
        $rows = Applicant::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        return [
            'pool'     => (int) ($rows['pool'] ?? 0),
            'hired'    => (int) ($rows['hired'] ?? 0),
            'rejected' => (int) ($rows['rejected'] ?? 0),
            'total'    => (int) $rows->sum(),
        ];
    }

    /** JSON list with filters. Default hides rejected unless explicitly requested. */
    public function list(Request $request)
    {
        $q = DB::table('applicants as a')
            ->leftJoin('departments as d', 'd.id', '=', 'a.department_id')
            ->selectRaw("a.*, d.dep_name as department_name,
                TRIM(CONCAT(a.last_name, ', ', a.first_name)) as full_name");

        if ($request->filled('status')) {
            $q->where('a.status', $request->status);
        } else {
            $q->where('a.status', '!=', 'rejected');
        }

        if ($request->filled('department_id')) {
            $q->where('a.department_id', $request->department_id);
        }

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $q->where(function ($w) use ($s) {
                $w->where('a.first_name', 'like', $s)
                  ->orWhere('a.last_name', 'like', $s)
                  ->orWhere('a.desired_position', 'like', $s)
                  ->orWhere('a.mobile', 'like', $s)
                  ->orWhere('a.email', 'like', $s);
            });
        }

        return response()->json([
            'status' => 200,
            'data'   => $q->orderByDesc('a.created_at')->limit(500)->get(),
        ]);
    }

    /** Create or update an applicant. */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'       => 'required|string|max:80',
            'last_name'        => 'required|string|max:80',
            'middle_name'      => 'nullable|string|max:80',
            'email'            => 'nullable|email|max:120',
            'mobile'           => 'nullable|string|max:20',
            'desired_position' => 'required|string|max:120',
            'department_id'    => 'nullable|integer|exists:departments,id',
            'source'           => 'nullable|string|max:40',
            'rating'           => 'nullable|integer|min:1|max:5',
            'notes'            => 'nullable|string|max:5000',
            'applied_at'       => 'nullable|date',
            'resume'           => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $data = [
            'first_name'       => $request->first_name,
            'last_name'        => $request->last_name,
            'middle_name'      => $request->middle_name,
            'email'            => $request->email,
            'mobile'           => $request->mobile,
            'desired_position' => $request->desired_position,
            'department_id'    => $request->department_id ?: null,
            'source'           => $request->source,
            'rating'           => $request->rating ?: null,
            'notes'            => $request->notes,
            'applied_at'       => $request->applied_at ?: Carbon::today(),
        ];

        if ($request->filled('id')) {
            $applicant = Applicant::findOrFail($request->id);
            $applicant->forceFill($data)->save();
        } else {
            $data['status'] = 'pool';
            $applicant = Applicant::create($data);
        }

        // Resume upload (optional) — mirror the department/employee document convention.
        if ($request->hasFile('resume')) {
            $file = $request->file('resume');
            $ext  = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $name = 'resume_' . $applicant->id . '_' . time() . '.' . $ext;
            $dir  = public_path('docs/applicants/' . $applicant->id);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            // Replace any previous resume file.
            if ($applicant->resume_path && is_file(public_path($applicant->resume_path))) {
                @unlink(public_path($applicant->resume_path));
            }
            $file->move($dir, $name);
            $applicant->forceFill(['resume_path' => 'docs/applicants/' . $applicant->id . '/' . $name])->save();
        }

        return response()->json(['status' => 200, 'msg' => 'Applicant saved.', 'id' => $applicant->id]);
    }

    /** Change an applicant's status (pool <-> rejected). Hiring uses hire() below. */
    public function updateStatus(Request $request)
    {
        $applicant = Applicant::find($request->id);
        if (!$applicant) {
            return response()->json(['status' => 202, 'msg' => 'Applicant not found.']);
        }

        $target = $request->input('status');
        if (!in_array($target, ['pool', 'rejected'], true)) {
            return response()->json(['status' => 202, 'msg' => 'Invalid status.']);
        }
        if ($applicant->status === 'hired') {
            return response()->json(['status' => 202, 'msg' => 'This applicant was already hired.']);
        }

        $applicant->forceFill([
            'status'           => $target,
            'rejection_reason' => $target === 'rejected' ? $request->input('rejection_reason') : null,
            'reviewed_by'      => $this->actorName(),
        ])->save();

        return response()->json(['status' => 200, 'msg' => 'Status updated.']);
    }

    /**
     * Begin the hire flow: send HR to the full onboarding form pre-filled with
     * this applicant's initial data. The real employee is created there; on
     * success registerCtrl::create stamps this applicant as hired.
     */
    public function hire(Applicant $applicant)
    {
        if ($applicant->status === 'hired') {
            return redirect('/pages/modules/applicants')
                ->with('error', 'This applicant was already hired.');
        }

        return redirect('/pages/modules/registration?applicant=' . $applicant->id);
    }

    public function delete(Request $request)
    {
        $applicant = Applicant::find($request->id);
        if (!$applicant) {
            return response()->json(['status' => 202, 'msg' => 'Applicant not found.']);
        }

        // Clean up the resume file + folder (cascade only handles the row).
        if ($applicant->resume_path && is_file(public_path($applicant->resume_path))) {
            @unlink(public_path($applicant->resume_path));
        }
        $dir = public_path('docs/applicants/' . $applicant->id);
        if (is_dir($dir)) {
            @rmdir($dir);
        }

        $applicant->delete();

        return response()->json(['status' => 200, 'msg' => 'Applicant deleted.']);
    }

    private function actorName(): string
    {
        $u = auth()->user();
        return $u ? trim(($u->fname ?? '') . ' ' . ($u->lname ?? '')) ?: 'HR' : 'HR';
    }
}

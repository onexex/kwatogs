<?php

namespace App\Http\Controllers;

use App\Models\TenureProgram;
use App\Models\TenureProgramGrant;
use App\Services\TenureProgramService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Programs Management — tenure-milestone benefits (Workforce menu).
 * CRUD for milestones + benefits, plus eligibility & grant tracking that
 * also feeds the HR Dashboard milestone widget.
 */
class ProgramController extends Controller
{
    public function index()
    {
        return view('pages.modules.programs');
    }

    /** JSON: all milestone programs with their benefits (config table). */
    public function list()
    {
        $programs = TenureProgram::with('benefits')->orderBy('years_required')->get();
        return response()->json(['status' => 200, 'data' => $programs]);
    }

    /** Create or update a milestone program and (re)sync its benefits. */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'                 => 'required|string|max:150',
            'years_required'        => 'required|numeric|min:0|max:99',
            'description'           => 'nullable|string|max:1000',
            'benefits'              => 'array',
            'benefits.*.name'       => 'required_with:benefits|string|max:150',
            'benefits.*.description'=> 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $data = [
            'title'          => $request->title,
            'years_required' => $request->years_required,
            'description'    => $request->description,
            'is_active'      => $request->boolean('is_active', true),
        ];

        DB::beginTransaction();
        try {
            if ($request->filled('id')) {
                $program = TenureProgram::findOrFail($request->id);
                $program->forceFill($data)->save();   // instance save → Auditable diff
            } else {
                $program = TenureProgram::create($data);
            }

            // Benefits are a small fixed set per milestone — simplest correct
            // sync is delete-and-recreate inside the transaction.
            $program->benefits()->delete();
            foreach (($request->benefits ?? []) as $b) {
                if (empty($b['name'])) {
                    continue;
                }
                $program->benefits()->create([
                    'name'        => $b['name'],
                    'description' => $b['description'] ?? null,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 202, 'msg' => 'Error saving program.']);
        }

        return response()->json([
            'status' => 200,
            'msg'    => $request->filled('id') ? 'Program updated.' : 'Program created.',
        ]);
    }

    /** Delete a milestone program (cascades benefits + grants). */
    public function delete(Request $request)
    {
        $program = TenureProgram::find($request->id);
        if (!$program) {
            return response()->json(['status' => 202, 'msg' => 'Program not found.']);
        }
        $program->delete();
        return response()->json(['status' => 200, 'msg' => 'Program deleted.']);
    }

    /** JSON: computed eligibility (reached + upcoming) for the screen. */
    public function eligibility(TenureProgramService $service)
    {
        return response()->json(['status' => 200, 'data' => $service->eligibility()]);
    }

    /** Mark a milestone benefit as granted to an employee. */
    public function grant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'program_id'  => 'required|exists:tenure_programs,id',
            'employee_id' => 'required|string',
            'note'        => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $user  = auth()->user();
        $grant = TenureProgramGrant::firstOrNew([
            'tenure_program_id' => $request->program_id,
            'employee_id'       => $request->employee_id,
        ]);
        $grant->status     = 'granted';
        $grant->granted_at = Carbon::today();
        $grant->granted_by = $user
            ? (trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'User'))
            : null;
        $grant->note = $request->note;
        $grant->save();   // instance save → Auditable

        return response()->json(['status' => 200, 'msg' => 'Benefit marked as granted.']);
    }

    /** Revert a grant back to pending (removes the grant row). */
    public function revoke(Request $request)
    {
        $grant = TenureProgramGrant::where('tenure_program_id', $request->program_id)
            ->where('employee_id', $request->employee_id)
            ->first();

        if ($grant) {
            $grant->delete();   // instance delete → Auditable
        }

        return response()->json(['status' => 200, 'msg' => 'Grant reverted to pending.']);
    }
}

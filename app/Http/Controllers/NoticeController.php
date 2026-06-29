<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Models\SuspensionRecommendation;
use App\Services\NoticeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Notices / Memo module.
 *  - Admin (HR) methods are gated by the `noticemanagement` permission.
 *  - Employee "My Notices" methods are auth-only (every employee sees their own).
 */
class NoticeController extends Controller
{
    /* ─────────────────────────── HR ADMIN ─────────────────────────── */

    public function index(NoticeService $service)
    {
        return view('pages.modules.notices', ['d' => $service->dashboard()]);
    }

    /** JSON: all notices with employee names + escalation count, filterable. */
    public function list(Request $request)
    {
        $q = DB::table('notices as n')
            ->leftJoin('users as u', 'u.empID', '=', 'n.employee_id')
            ->selectRaw("n.*, TRIM(CONCAT(u.lname, ', ', u.fname)) as employee_name");

        if ($request->filled('type')) {
            $q->where('n.type', $request->type);
        }
        if ($request->filled('status')) {
            $q->where('n.status', $request->status);
        }
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $q->where(function ($w) use ($s) {
                $w->where('u.lname', 'like', $s)->orWhere('u.fname', 'like', $s)
                  ->orWhere('n.employee_id', 'like', $s)->orWhere('n.title', 'like', $s);
            });
        }

        return response()->json(['status' => 200, 'data' => $q->orderByDesc('n.issued_at')->orderByDesc('n.id')->limit(500)->get()]);
    }

    /** JSON: active employees for the "issue notice" picker. */
    public function employees()
    {
        $rows = DB::table('emp_details as e')
            ->join('users as u', 'u.empID', '=', 'e.empID')
            ->where('e.empStatus', '1')
            ->selectRaw("e.empID as empid,
                TRIM(CONCAT(u.lname, ', ', u.fname)) as name,
                COALESCE((SELECT dep_name FROM departments WHERE id = e.empDepID), '—') as dept")
            ->orderBy('u.lname')->get();

        return response()->json(['status' => 200, 'data' => $rows]);
    }

    /** Create or update a notice. Re-evaluates escalation for disciplinary ones. */
    public function save(Request $request, NoticeService $service)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|string',
            'type'        => 'required|in:memo,disciplinary',
            'category'    => 'nullable|string|max:100',
            'title'       => 'required|string|max:200',
            'body'        => 'required|string|max:5000',
            'issued_at'   => 'nullable|date',
            'status'      => 'nullable|in:active,void',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $user = auth()->user();
        $data = [
            'employee_id' => $request->employee_id,
            'type'        => $request->type,
            'category'    => $request->type === 'disciplinary' ? $request->category : null,
            'title'       => $request->title,
            'body'        => $request->body,
            'issued_at'   => $request->issued_at ?: Carbon::today()->toDateString(),
            'status'      => $request->status ?: 'active',
        ];

        if ($request->filled('id')) {
            $notice = Notice::findOrFail($request->id);
            $notice->forceFill($data)->save();   // instance save → Auditable
        } else {
            $data['issued_by'] = $user
                ? (trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'HR'))
                : 'HR';
            $data['is_read']   = false;
            $notice = Notice::create($data);
        }

        // Disciplinary changes can push an employee over the suspension threshold.
        if ($notice->type === 'disciplinary') {
            $service->evaluateEscalation($notice->employee_id);
        }

        return response()->json([
            'status' => 200,
            'msg'    => $request->filled('id') ? 'Notice updated.' : 'Notice issued.',
        ]);
    }

    /** Delete a notice. */
    public function delete(Request $request)
    {
        $notice = Notice::find($request->id);
        if (!$notice) {
            return response()->json(['status' => 202, 'msg' => 'Notice not found.']);
        }
        $notice->delete();   // instance delete → Auditable
        return response()->json(['status' => 200, 'msg' => 'Notice deleted.']);
    }

    /** JSON: pending + recently-resolved suspension recommendations. */
    public function recommendations(NoticeService $service)
    {
        return response()->json(['status' => 200, 'data' => $service->dashboard()['pendingRecs']]);
    }

    /** HR resolves a recommendation (dismiss / mark actioned). */
    public function resolveRecommendation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:suspension_recommendations,id',
            'action' => 'required|in:dismissed,actioned',
            'note'   => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $user = auth()->user();
        $rec  = SuspensionRecommendation::findOrFail($request->id);
        $rec->status          = $request->action;
        $rec->resolution_note = $request->note;
        $rec->resolved_by     = $user
            ? (trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'HR'))
            : 'HR';
        $rec->resolved_at = Carbon::now();
        $rec->save();   // instance save → Auditable

        return response()->json(['status' => 200, 'msg' => 'Recommendation ' . $request->action . '.']);
    }

    /* ─────────────────────────── EMPLOYEE ─────────────────────────── */

    /** Employee view of their own notices. Opening it clears the unread badge. */
    public function mine()
    {
        $empId = auth()->user()->empID;

        // Read-receipt: clear unread on open. Bulk update bypasses model events
        // (so it is intentionally not audited — read receipts aren't audit-worthy).
        Notice::where('employee_id', $empId)->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => Carbon::now()]);

        return view('pages.modules.my_notices');
    }

    /** JSON: the logged-in employee's own notices. */
    public function myList()
    {
        $empId = auth()->user()->empID;

        $rows = Notice::where('employee_id', $empId)
            ->where('status', 'active')
            ->orderByDesc('issued_at')->orderByDesc('id')
            ->get(['id', 'type', 'category', 'title', 'body', 'issued_by', 'issued_at', 'read_at']);

        return response()->json(['status' => 200, 'data' => $rows]);
    }
}

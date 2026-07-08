<?php

namespace App\Http\Controllers;

use App\Models\department;
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
        return view('pages.modules.notices', [
            'd'           => $service->dashboard(),
            'departments' => department::orderBy('dep_name')->get(['id', 'dep_name']),
        ]);
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
                UPPER(TRIM(CONCAT(u.lname, ', ', u.fname))) as name,
                COALESCE((SELECT dep_name FROM departments WHERE id = e.empDepID), '—') as dept")
            ->orderBy('u.lname')->orderBy('u.fname')->get();

        return response()->json(['status' => 200, 'data' => $rows]);
    }

    /**
     * Create or update a notice. Re-evaluates escalation for disciplinary ones.
     *
     * `recipient_mode` (default `single`) supports bulk issuance on create:
     * `employees` (picked list), `department` (all actives in the picked
     * departments) or `all` (every active employee). Bulk is memo-only —
     * disciplinary notices feed the suspension-escalation counter and must
     * stay one-employee-at-a-time.
     */
    public function save(Request $request, NoticeService $service)
    {
        $mode = $request->input('recipient_mode', 'single');

        $rules = [
            'recipient_mode' => 'nullable|in:single,employees,department,all',
            'type'           => 'required|in:memo,disciplinary',
            'category'       => 'nullable|string|max:100',
            'title'          => 'required|string|max:200',
            'body'           => 'required|string|max:5000',
            'issued_at'      => 'nullable|date',
            'status'         => 'nullable|in:active,void',
        ];
        if ($mode === 'employees') {
            $rules['employee_ids']   = 'required|array|min:1';
            $rules['employee_ids.*'] = 'string';
        } elseif ($mode === 'department') {
            $rules['department_ids']   = 'required|array|min:1';
            $rules['department_ids.*'] = 'integer|exists:departments,id';
        } elseif ($mode !== 'all') {
            $rules['employee_id'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules, [
            'employee_ids.required'   => 'Select at least one employee.',
            'department_ids.required' => 'Select at least one department.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        if ($mode !== 'single') {
            if ($request->filled('id')) {
                return response()->json(['status' => 201, 'error' => ['recipient_mode' => ['Bulk send is only available when issuing a new notice.']]]);
            }
            if ($request->type !== 'memo') {
                return response()->json(['status' => 201, 'error' => ['recipient_mode' => ['Disciplinary notices must be issued to a single employee.']]]);
            }
        }

        $user     = auth()->user();
        $issuedBy = $user
            ? (trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'HR'))
            : 'HR';

        if ($mode !== 'single') {
            $recipients = $this->resolveRecipients($mode, $request);
            if ($recipients->isEmpty()) {
                return response()->json(['status' => 201, 'error' => ['recipient_mode' => ['No active employees match the selected recipients.']]]);
            }

            $base = [
                'type'      => 'memo',
                'category'  => null,
                'title'     => $request->title,
                'body'      => $request->body,
                'issued_at' => $request->issued_at ?: Carbon::today()->toDateString(),
                'status'    => $request->status ?: 'active',
                'issued_by' => $issuedBy,
                'is_read'   => false,
            ];

            DB::transaction(function () use ($recipients, $base) {
                foreach ($recipients as $empId) {
                    Notice::create($base + ['employee_id' => $empId]);   // instance create → Auditable
                }
            });

            $n = $recipients->count();
            return response()->json([
                'status'     => 200,
                'msg'        => "Memo issued to {$n} employee" . ($n === 1 ? '' : 's') . '.',
                'recipients' => $n,
            ]);
        }

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
            // Once the employee has read it, the notice is frozen — editing it
            // afterwards would silently rewrite something they've already seen.
            if ($notice->is_read) {
                return response()->json(['status' => 202, 'msg' => 'This notice has already been read by the employee and can no longer be edited.']);
            }
            $notice->forceFill($data)->save();   // instance save → Auditable
        } else {
            $data['issued_by'] = $issuedBy;
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

    /**
     * Resolve a bulk send to active employee IDs. Submitted IDs are re-filtered
     * through empStatus='1', so inactive or bogus IDs are silently dropped.
     */
    private function resolveRecipients(string $mode, Request $request): \Illuminate\Support\Collection
    {
        $q = DB::table('emp_details')->where('empStatus', '1');

        if ($mode === 'department') {
            $q->whereIn('empDepID', $request->department_ids);
        } elseif ($mode === 'employees') {
            $q->whereIn('empID', $request->employee_ids);
        }

        return $q->distinct()->pluck('empID')->unique()->values();
    }

    /** Delete a notice. */
    public function delete(Request $request)
    {
        $notice = Notice::find($request->id);
        if (!$notice) {
            return response()->json(['status' => 202, 'msg' => 'Notice not found.']);
        }
        // A notice the employee has already read is frozen (see save()).
        if ($notice->is_read) {
            return response()->json(['status' => 202, 'msg' => 'This notice has already been read by the employee and can no longer be deleted.']);
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

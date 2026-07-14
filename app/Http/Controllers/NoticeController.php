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
            // Scanned/signed memo. Preview-only (streamed inline from storage/, never public).
            'attachment'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            // Notice to Explain (disciplinary only — enforced below).
            'requires_response' => 'nullable|boolean',
            'respond_by'        => 'nullable|date',
            // Explicit acknowledgement of receipt (disciplinary only — enforced below).
            'requires_ack'      => 'nullable|boolean',
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

            // One uploaded signed memo, fanned out to every recipient. Each row gets
            // its own copy so file cleanup on delete stays per-row (no shared file).
            $attachMeta = $request->hasFile('attachment')
                ? $this->persistMemoAttachment($request->file('attachment'))
                : null;

            DB::transaction(function () use ($recipients, $base, $attachMeta) {
                foreach ($recipients as $i => $empId) {
                    $meta = $attachMeta ? ($i === 0 ? $attachMeta : $this->copyMemoAttachment($attachMeta)) : [];
                    Notice::create($base + $meta + ['employee_id' => $empId]);   // instance create → Auditable
                }
            });

            $n = $recipients->count();
            return response()->json([
                'status'     => 200,
                'msg'        => "Memo issued to {$n} employee" . ($n === 1 ? '' : 's') . '.',
                'recipients' => $n,
            ]);
        }

        // NTE and acknowledgement are disciplinary-only; a memo never carries either.
        $requiresResponse = $request->type === 'disciplinary' && $request->boolean('requires_response');
        $requiresAck      = $request->type === 'disciplinary' && $request->boolean('requires_ack');

        $data = [
            'employee_id'       => $request->employee_id,
            'type'              => $request->type,
            'category'          => $request->type === 'disciplinary' ? $request->category : null,
            'title'             => $request->title,
            'body'              => $request->body,
            'issued_at'         => $request->issued_at ?: Carbon::today()->toDateString(),
            'status'            => $request->status ?: 'active',
            'requires_response' => $requiresResponse,
            'respond_by'        => $requiresResponse ? ($request->respond_by ?: null) : null,
            'requires_ack'      => $requiresAck,
        ];

        // Signed memo attachment (memo-only). A newly uploaded file replaces any
        // existing one; disciplinary notices never carry an attachment.
        $newAttach = ($request->type === 'memo' && $request->hasFile('attachment'))
            ? $this->persistMemoAttachment($request->file('attachment'))
            : null;

        if ($request->filled('id')) {
            $notice = Notice::findOrFail($request->id);
            // Once the employee has read it, the notice is frozen — editing it
            // afterwards would silently rewrite something they've already seen.
            if ($notice->is_read) {
                if ($newAttach) {
                    $this->unlinkMemoAttachment($newAttach['attachment_path']);   // discard the just-uploaded file
                }
                return response()->json(['status' => 202, 'msg' => 'This notice has already been read by the employee and can no longer be edited.']);
            }
            if ($newAttach) {
                $this->unlinkMemoAttachment($notice->attachment_path);   // remove the old file on replace
                $data += $newAttach;
            } elseif ($request->type !== 'memo' && $notice->attachment_path) {
                // Switched memo → disciplinary: drop the attachment.
                $this->unlinkMemoAttachment($notice->attachment_path);
                $data += ['attachment_path' => null, 'attachment_name' => null, 'attachment_mime' => null, 'attachment_size' => null];
            }
            $notice->forceFill($data)->save();   // instance save → Auditable
        } else {
            if ($newAttach) {
                $data += $newAttach;
            }
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
        $this->unlinkMemoAttachment($notice->attachment_path);   // file cleanup is the controller's job
        $this->unlinkMemoAttachment($notice->response_doc_path); // NTE evidence file too
        $notice->delete();   // instance delete → Auditable
        return response()->json(['status' => 200, 'msg' => 'Notice deleted.']);
    }

    /* ─────────────────────── Signed-memo attachment ─────────────────────── */

    /**
     * Store an uploaded signed memo under storage/app/notice_memos/<token>/ —
     * OUTSIDE public/, so it is never URL-reachable and can only be served by
     * the gated streamMemo() route. Returns the columns for the notice row.
     */
    private function persistMemoAttachment(\Illuminate\Http\UploadedFile $file): array
    {
        $token = bin2hex(random_bytes(8));
        $ext   = strtolower($file->getClientOriginalExtension() ?: 'dat'); // whitelisted by the mimes rule
        $dir   = storage_path('app/notice_memos/' . $token);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $size = $file->getSize();
        $mime = $file->getClientMimeType();
        $orig = $file->getClientOriginalName();
        $stored = 'memo_' . time() . '.' . $ext;
        $file->move($dir, $stored);

        return [
            'attachment_path' => 'notice_memos/' . $token . '/' . $stored,
            'attachment_name' => $orig,
            'attachment_mime' => $mime,
            'attachment_size' => $size ?: null,
        ];
    }

    /** Copy an already-stored memo into a fresh folder (bulk fan-out — one file per row). */
    private function copyMemoAttachment(array $meta): array
    {
        $src   = storage_path('app/' . $meta['attachment_path']);
        $token = bin2hex(random_bytes(8));
        $dir   = storage_path('app/notice_memos/' . $token);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $base = basename($meta['attachment_path']);
        @copy($src, $dir . '/' . $base);

        return array_merge($meta, ['attachment_path' => 'notice_memos/' . $token . '/' . $base]);
    }

    /** Remove a stored memo file (and its now-empty token folder). No-op if absent. */
    private function unlinkMemoAttachment(?string $relPath): void
    {
        if (!$relPath) {
            return;
        }
        $abs = storage_path('app/' . $relPath);
        if (is_file($abs)) {
            @unlink($abs);
        }
        $dir = dirname($abs);
        if (is_dir($dir) && str_contains(str_replace('\\', '/', $dir), '/notice_memos/') && count(glob($dir . '/*') ?: []) === 0) {
            @rmdir($dir);
        }
    }

    /**
     * Stream a signed memo INLINE for preview only. Owner (the target employee)
     * or an HR manager may view it; nobody else. Served with no-store caching and
     * inline disposition — there is no download link and no public copy on disk.
     */
    public function streamMemo(Notice $notice)
    {
        $u         = auth()->user();
        $isOwner   = $u && (string) $u->empID === (string) $notice->employee_id;
        $canManage = $u && $u->can('noticemanagement');
        abort_unless($isOwner || $canManage, 403);
        abort_unless($notice->attachment_path, 404);

        $abs = storage_path('app/' . $notice->attachment_path);
        abort_unless(is_file($abs), 404);

        return response()->file($abs, [
            'Content-Type'           => $notice->attachment_mime ?: 'application/octet-stream',
            'Content-Disposition'    => 'inline; filename="' . addslashes($notice->attachment_name ?: 'memo') . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, no-store, max-age=0',
        ]);
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
        $u     = auth()->user();
        $empId = $u->empID;

        // Read-receipt: clear unread on open. Bulk update bypasses model events
        // (so it is intentionally not audited — read receipts aren't audit-worthy).
        Notice::where('employee_id', $empId)->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => Carbon::now()]);

        // Stamped across the preview so any screenshot is traceable to the viewer.
        $watermark = trim(($u->fname ?? '') . ' ' . ($u->lname ?? '')) ?: ($u->name ?? 'Employee');
        $watermark .= ' · ' . $empId;

        return view('pages.modules.my_notices', ['watermark' => $watermark]);
    }

    /** JSON: the logged-in employee's own notices. */
    public function myList()
    {
        $empId = auth()->user()->empID;

        $rows = Notice::where('employee_id', $empId)
            ->where('status', 'active')
            ->orderByDesc('issued_at')->orderByDesc('id')
            ->get(['id', 'type', 'category', 'title', 'body', 'issued_by', 'issued_at', 'read_at',
                   'requires_ack', 'acknowledged_at',
                   'attachment_name', 'attachment_mime',                              // NOT attachment_path — never leak the storage path
                   'requires_response', 'respond_by', 'response_body', 'response_at',
                   'response_doc_name', 'response_decision', 'response_review_note', 'response_reviewed_at'])
            ->map(function ($n) {
                // Boolean + basic metadata only; the file streams from a gated route by id.
                $n->has_memo   = $n->attachment_name ? 1 : 0;
                $n->memo_isPdf = $n->attachment_mime ? (stripos($n->attachment_mime, 'pdf') !== false ? 1 : 0) : 0;
                $n->has_response_doc = $n->response_doc_name ? 1 : 0;
                unset($n->attachment_mime);
                return $n;
            });

        return response()->json(['status' => 200, 'data' => $rows]);
    }

    /**
     * Employee submits their written explanation (NTE response) — once.
     * Only the owning employee, only a disciplinary notice that requires a
     * response, only if not already answered. Optional evidence file is stored
     * outside public/ (streamed via streamResponseDoc()).
     */
    public function respond(Request $request, Notice $notice)
    {
        $u = auth()->user();
        abort_unless($u && (string) $u->empID === (string) $notice->employee_id, 403);

        if ($notice->type !== 'disciplinary' || !$notice->requires_response) {
            return response()->json(['status' => 202, 'msg' => 'This notice does not require a written explanation.']);
        }
        if ($notice->response_at) {
            return response()->json(['status' => 202, 'msg' => 'You have already submitted your explanation for this notice.']);
        }

        $validator = Validator::make($request->all(), [
            'response_body' => 'required|string|max:5000',
            'attachment'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], ['response_body.required' => 'Please write your explanation.']);
        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $data = [
            'response_body' => $request->response_body,
            'response_at'   => Carbon::now(),
        ];
        if ($request->hasFile('attachment')) {
            $meta = $this->persistMemoAttachment($request->file('attachment'));   // same storage/token pattern
            $data['response_doc_path'] = $meta['attachment_path'];
            $data['response_doc_name'] = $meta['attachment_name'];
        }

        $notice->forceFill($data)->save();   // instance save → Auditable

        return response()->json(['status' => 200, 'msg' => 'Your explanation has been submitted.']);
    }

    /**
     * Employee explicitly acknowledges RECEIPT of a disciplinary notice — once.
     *
     * Distinct from the passive `read_at` receipt (auto-stamped on page open)
     * and from the NTE `respond()` flow: this is a deliberate one-time act that
     * records timestamp + IP as due-process evidence. It acknowledges receipt,
     * not agreement. Only the owning employee, only an active disciplinary
     * notice, only if not already acknowledged. Goes through the model instance
     * so Auditable records it (unlike read receipts, which are not audited).
     */
    public function acknowledge(Request $request, Notice $notice)
    {
        $u = auth()->user();
        abort_unless($u && (string) $u->empID === (string) $notice->employee_id, 403);

        if ($notice->type !== 'disciplinary' || $notice->status !== 'active' || !$notice->requires_ack) {
            return response()->json(['status' => 202, 'msg' => 'This notice does not require acknowledgement.']);
        }
        if ($notice->acknowledged_at) {
            return response()->json(['status' => 202, 'msg' => 'You have already acknowledged this notice.']);
        }

        $notice->forceFill([
            'acknowledged_at' => Carbon::now(),
            'acknowledged_ip' => $request->ip(),
        ])->save();   // instance save → Auditable

        return response()->json([
            'status'          => 200,
            'msg'             => 'Receipt acknowledged.',
            'acknowledged_at' => $notice->acknowledged_at->toIso8601String(),
        ]);
    }

    /** HR records a decision on a submitted explanation. */
    public function reviewResponse(Request $request, Notice $notice)
    {
        if (!$notice->response_at) {
            return response()->json(['status' => 202, 'msg' => 'The employee has not submitted an explanation yet.']);
        }

        $validator = Validator::make($request->all(), [
            'decision' => 'required|in:accepted,further_action',
            'note'     => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $u = auth()->user();
        $notice->forceFill([
            'response_decision'    => $request->decision,
            'response_review_note' => $request->note,
            'response_reviewed_by' => $u ? (trim(($u->fname ?? '') . ' ' . ($u->lname ?? '')) ?: ($u->name ?? 'HR')) : 'HR',
            'response_reviewed_at' => Carbon::now(),
        ])->save();   // instance save → Auditable

        return response()->json(['status' => 200, 'msg' => 'Decision recorded.']);
    }

    /**
     * Stream an NTE evidence file. Owner (the employee who uploaded it) or an
     * HR manager may view it. Unlike the signed memo this is the employee's own
     * submission, so it is served as a normal inline file.
     */
    public function streamResponseDoc(Notice $notice)
    {
        $u         = auth()->user();
        $isOwner   = $u && (string) $u->empID === (string) $notice->employee_id;
        $canManage = $u && $u->can('noticemanagement');
        abort_unless($isOwner || $canManage, 403);
        abort_unless($notice->response_doc_path, 404);

        $abs = storage_path('app/' . $notice->response_doc_path);
        abort_unless(is_file($abs), 404);

        return response()->file($abs, [
            'Content-Disposition' => 'inline; filename="' . addslashes($notice->response_doc_name ?: 'explanation') . '"',
            'Cache-Control'       => 'private, no-store, max-age=0',
        ]);
    }
}

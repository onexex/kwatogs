<?php

namespace App\Http\Controllers;

use App\Models\HandbookAcknowledgement;
use App\Models\HandbookSection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Employee Handbook module.
 *  - HR admin methods are gated by the `handbookmanagement` permission.
 *  - Employee "My Handbook" methods are auth-only (every employee reads their own).
 *
 * Content model: ordered rich-text sections, each optionally carrying a
 * supporting document (PDF/image). Documents live OUTSIDE public/ under
 * storage/app/handbook_docs/<token>/ and are served inline only by the gated
 * streamAttachment() route — never a URL-reachable copy (same pattern as the
 * signed-memo attachment on notices).
 */
class HandbookController extends Controller
{
    /* ─────────────────────────── HR ADMIN ─────────────────────────── */

    public function index()
    {
        return view('pages.modules.handbook');
    }

    /** JSON: authored sections (published + drafts) + the single-PDF master doc. */
    public function list()
    {
        $rows = HandbookSection::where('is_master', false)
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'title', 'slug', 'body', 'sort_order', 'is_published', 'requires_ack',
                   'attachment_name', 'attachment_mime', 'version', 'updated_by', 'updated_at'])
            ->map(function ($s) {
                $s->has_doc   = $s->attachment_name ? 1 : 0;
                $s->doc_isPdf = $s->attachment_mime ? (stripos($s->attachment_mime, 'pdf') !== false ? 1 : 0) : 0;
                $s->ack_count = HandbookAcknowledgement::where('section_id', $s->id)->count();
                unset($s->attachment_mime);
                return $s;
            });

        $m = HandbookSection::where('is_master', true)->first();
        $master = $m ? [
            'id'              => $m->id,
            'title'           => $m->title,
            'requires_ack'    => (bool) $m->requires_ack,
            'attachment_name' => $m->attachment_name,
            'has_doc'         => $m->attachment_name ? 1 : 0,
            'version'         => $m->version,
            'updated_by'      => $m->updated_by,
            'updated_at'      => $m->updated_at,
            'ack_count'       => HandbookAcknowledgement::where('section_id', $m->id)->count(),
        ] : null;

        return response()->json(['status' => 200, 'data' => $rows, 'master' => $master]);
    }

    /** Create or update a section. Multipart (optional supporting document). */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:200',
            'body'         => 'nullable|string|max:100000',
            'is_published' => 'nullable|boolean',
            'requires_ack' => 'nullable|boolean',
            'attachment'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'remove_doc'   => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $user      = auth()->user();
        $updatedBy = $user
            ? (trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'HR'))
            : 'HR';

        $data = [
            'title'        => $request->title,
            'slug'         => Str::slug($request->title) ?: null,
            'body'         => $request->body,
            'is_published' => $request->boolean('is_published'),
            'requires_ack' => $request->boolean('requires_ack'),
            'updated_by'   => $updatedBy,
        ];

        $newDoc = $request->hasFile('attachment')
            ? $this->persistDoc($request->file('attachment'))
            : null;

        if ($request->filled('id')) {
            $section = HandbookSection::findOrFail($request->id);
            // The single-PDF handbook is edited only through the Handbook Document panel.
            if ($section->is_master) {
                return response()->json(['status' => 202, 'msg' => 'Use the Handbook Document panel to edit the uploaded handbook.']);
            }
            $bodyChanged = $section->body !== $request->body;

            if ($newDoc) {
                $this->unlinkDoc($section->attachment_path);   // replace old file
                $data += $newDoc;
            } elseif ($request->boolean('remove_doc') && $section->attachment_path) {
                $this->unlinkDoc($section->attachment_path);
                $data += ['attachment_path' => null, 'attachment_name' => null, 'attachment_mime' => null, 'attachment_size' => null];
            }

            // Bump the version on a material change so a required re-acknowledgement
            // can be detected (the employee acked an older revision).
            if ($bodyChanged || $newDoc || $request->boolean('remove_doc')) {
                $data['version'] = (int) $section->version + 1;
            }
            $section->forceFill($data)->save();   // instance save → Auditable
        } else {
            if ($newDoc) {
                $data += $newDoc;
            }
            $data['sort_order'] = (int) (HandbookSection::max('sort_order') ?? 0) + 1;
            $data['version']    = 1;
            $section = HandbookSection::create($data);
        }

        return response()->json([
            'status' => 200,
            'msg'    => $request->filled('id') ? 'Section updated.' : 'Section created.',
        ]);
    }

    /** Persist a new ordering. Body: {order: [id, id, …]}. */
    public function reorder(Request $request)
    {
        $ids = (array) $request->input('order', []);
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $i => $id) {
                // Bare update — reordering is not audit-worthy churn.
                HandbookSection::where('id', $id)->update(['sort_order' => $i + 1]);
            }
        });
        return response()->json(['status' => 200, 'msg' => 'Order saved.']);
    }

    /** Delete a section (its acknowledgements cascade). */
    public function delete(Request $request)
    {
        $section = HandbookSection::find($request->id);
        if (!$section) {
            return response()->json(['status' => 202, 'msg' => 'Section not found.']);
        }
        $this->unlinkDoc($section->attachment_path);   // file cleanup is the controller's job
        $section->delete();   // instance delete → Auditable (ack rows cascade at DB)
        return response()->json(['status' => 200, 'msg' => 'Section deleted.']);
    }

    /* ─────────────────── Single-PDF handbook (master doc) ─────────────────── */

    /**
     * Create or replace the one master handbook document (is_master=true).
     * "Single-PDF mode": HR uploads one PDF for the whole handbook. Modeled as a
     * special section so it reuses the stream/ack/version/receipt machinery.
     */
    public function saveMaster(Request $request)
    {
        $master = HandbookSection::where('is_master', true)->first();

        $validator = Validator::make($request->all(), [
            'doc_title'    => 'nullable|string|max:200',
            'requires_ack' => 'nullable|boolean',
            // A file is required only when first creating the master doc.
            'attachment'   => ($master ? 'nullable' : 'required') . '|file|mimes:pdf|max:20480',
        ], ['attachment.required' => 'Please choose the handbook PDF to upload.']);
        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $user      = auth()->user();
        $updatedBy = $user
            ? (trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'HR'))
            : 'HR';

        $newDoc = $request->hasFile('attachment') ? $this->persistDoc($request->file('attachment')) : null;

        $data = [
            'title'        => $request->input('doc_title') ?: 'Employee Handbook',
            'slug'         => 'employee-handbook',
            'body'         => null,
            'is_master'    => true,
            'is_published' => true,
            'requires_ack' => $request->boolean('requires_ack'),
            'sort_order'   => 0,
            'updated_by'   => $updatedBy,
        ];

        if ($master) {
            if ($newDoc) {
                $this->unlinkDoc($master->attachment_path);      // replace the old PDF
                $data += $newDoc;
                $data['version'] = (int) $master->version + 1;   // bump → employees must re-acknowledge
            }
            $master->forceFill($data)->save();   // instance save → Auditable
        } else {
            $data += $newDoc;
            $data['version'] = 1;
            HandbookSection::create($data);
        }

        return response()->json(['status' => 200, 'msg' => $master ? 'Handbook document updated.' : 'Handbook uploaded.']);
    }

    /** Remove the master handbook document (its acknowledgements cascade). */
    public function deleteMaster()
    {
        $master = HandbookSection::where('is_master', true)->first();
        if (!$master) {
            return response()->json(['status' => 202, 'msg' => 'There is no handbook document to remove.']);
        }
        $this->unlinkDoc($master->attachment_path);
        $master->delete();   // instance delete → Auditable (ack rows cascade at DB)
        return response()->json(['status' => 200, 'msg' => 'Handbook document removed.']);
    }

    /**
     * JSON: acknowledgement report for one section — who has read it and who
     * still hasn't (active employees only). Feeds the admin "Read Receipts" panel.
     */
    public function acknowledgements(Request $request)
    {
        $section = HandbookSection::findOrFail($request->input('section_id'));

        $acked = HandbookAcknowledgement::where('section_id', $section->id)
            ->pluck('acknowledged_at', 'employee_id');

        $employees = DB::table('emp_details as e')
            ->join('users as u', 'u.empID', '=', 'e.empID')
            ->where('e.empStatus', '1')
            ->selectRaw("e.empID as empid, UPPER(TRIM(CONCAT(u.lname, ', ', u.fname))) as name")
            ->orderBy('u.lname')->orderBy('u.fname')->get();

        $rows = $employees->map(function ($emp) use ($acked) {
            $at = $acked[$emp->empid] ?? null;
            return ['empid' => $emp->empid, 'name' => $emp->name, 'acknowledged_at' => $at];
        });

        return response()->json([
            'status'  => 200,
            'section' => ['id' => $section->id, 'title' => $section->title, 'requires_ack' => $section->requires_ack],
            'total'   => $employees->count(),
            'acked'   => $acked->count(),
            'data'    => $rows,
        ]);
    }

    /* ─────────────────────── Supporting document ─────────────────────── */

    /** Store a document under storage/app/handbook_docs/<token>/ (outside public/). */
    private function persistDoc(\Illuminate\Http\UploadedFile $file): array
    {
        $token = bin2hex(random_bytes(8));
        $ext   = strtolower($file->getClientOriginalExtension() ?: 'dat'); // whitelisted by mimes rule
        $dir   = storage_path('app/handbook_docs/' . $token);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $size = $file->getSize();
        $mime = $file->getClientMimeType();
        $orig = $file->getClientOriginalName();
        $stored = 'doc_' . time() . '.' . $ext;
        $file->move($dir, $stored);

        return [
            'attachment_path' => 'handbook_docs/' . $token . '/' . $stored,
            'attachment_name' => $orig,
            'attachment_mime' => $mime,
            'attachment_size' => $size ?: null,
        ];
    }

    /** Remove a stored document (and its now-empty token folder). No-op if absent. */
    private function unlinkDoc(?string $relPath): void
    {
        if (!$relPath) {
            return;
        }
        $abs = storage_path('app/' . $relPath);
        if (is_file($abs)) {
            @unlink($abs);
        }
        $dir = dirname($abs);
        if (is_dir($dir) && str_contains(str_replace('\\', '/', $dir), '/handbook_docs/') && count(glob($dir . '/*') ?: []) === 0) {
            @rmdir($dir);
        }
    }

    /**
     * Stream a section's supporting document INLINE. Any authenticated user may
     * view it (the handbook is company-wide), but only through this gated route
     * — there is no public copy on disk.
     */
    public function streamAttachment(HandbookSection $section)
    {
        abort_unless(auth()->check(), 403);
        abort_unless($section->attachment_path, 404);

        $abs = storage_path('app/' . $section->attachment_path);
        abort_unless(is_file($abs), 404);

        return response()->file($abs, [
            'Content-Type'           => $section->attachment_mime ?: 'application/octet-stream',
            'Content-Disposition'    => 'inline; filename="' . addslashes($section->attachment_name ?: 'handbook') . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, no-store, max-age=0',
        ]);
    }

    /* ─────────────────────────── EMPLOYEE ─────────────────────────── */

    /** Employee handbook workspace. */
    public function mine()
    {
        $u = auth()->user();
        $watermark = trim(($u->fname ?? '') . ' ' . ($u->lname ?? '')) ?: ($u->name ?? 'Employee');
        $watermark .= ' · ' . $u->empID;

        return view('pages.modules.my_handbook', ['watermark' => $watermark]);
    }

    /** JSON: published sections + this employee's acknowledgement state. */
    public function myList()
    {
        $empId = auth()->user()->empID;

        $acked = HandbookAcknowledgement::where('employee_id', $empId)
            ->pluck('acked_version', 'section_id');

        $rows = HandbookSection::where('is_published', true)
            ->orderByDesc('is_master')->orderBy('sort_order')->orderBy('id')   // master pinned first
            ->get(['id', 'title', 'body', 'requires_ack', 'is_master', 'attachment_name', 'attachment_mime',
                   'version', 'updated_by', 'updated_at'])
            ->map(function ($s) use ($acked) {
                $s->has_doc   = $s->attachment_name ? 1 : 0;
                $s->doc_isPdf = $s->attachment_mime ? (stripos($s->attachment_mime, 'pdf') !== false ? 1 : 0) : 0;
                $s->is_master = $s->is_master ? 1 : 0;
                // Acknowledged only if the employee acked THIS version (a material
                // edit bumps version → the section reads as needing re-acknowledgement).
                $ackVer = $acked[$s->id] ?? null;
                $s->acknowledged     = ($ackVer !== null && (int) $ackVer >= (int) $s->version) ? 1 : 0;
                $s->needs_reack      = ($ackVer !== null && (int) $ackVer < (int) $s->version) ? 1 : 0;
                unset($s->attachment_mime);
                return $s;
            });

        return response()->json(['status' => 200, 'data' => $rows]);
    }

    /** Employee acknowledges a section (idempotent per employee+section). */
    public function acknowledge(Request $request, HandbookSection $section)
    {
        abort_unless(auth()->check(), 403);
        abort_unless($section->is_published, 404);

        if (!$section->requires_ack) {
            return response()->json(['status' => 202, 'msg' => 'This section does not require acknowledgement.']);
        }

        $empId = auth()->user()->empID;

        HandbookAcknowledgement::updateOrCreate(
            ['employee_id' => $empId, 'section_id' => $section->id],
            ['acked_version' => (int) $section->version, 'ip' => $request->ip(), 'acknowledged_at' => Carbon::now()]
        );

        return response()->json(['status' => 200, 'msg' => 'Acknowledged. Thank you.']);
    }
}

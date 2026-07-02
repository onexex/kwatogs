<?php

namespace App\Http\Controllers;

use App\Models\CoeSignatory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Settings → COE Signatories. HR maintains the authorized signatories (name +
 * title + uploaded e-signature image) that can be stamped on a Certificate of
 * Employment. Gated by `coemanagement` (same as the COE screen). The signature
 * file is stored under public/img/signatories (mirrors the profile-photo
 * convention in registerCtrl).
 */
class CoeSignatoryController extends Controller
{
    private const DIR = 'img/signatories';

    public function index()
    {
        return view('pages.management.coe_signatories');
    }

    /** JSON: all signatories (management table). */
    public function list()
    {
        $rows = CoeSignatory::orderBy('name')->get()->map(fn ($s) => [
            'id'         => $s->id,
            'name'       => $s->name,
            'title'      => $s->title,
            'is_active'  => $s->is_active,
            'signature'  => $s->signatureUrl(),
        ]);

        return response()->json(['status' => 200, 'data' => $rows]);
    }

    /** JSON: active signatories only — feeds the COE approve/issue pickers. */
    public function activeList()
    {
        $rows = CoeSignatory::where('is_active', true)->orderBy('name')->get()->map(fn ($s) => [
            'id'        => $s->id,
            'name'      => $s->name,
            'title'     => $s->title,
            'signature' => $s->signatureUrl(),
        ]);

        return response()->json(['status' => 200, 'data' => $rows]);
    }

    /** Create or update a signatory; image required on create, optional on update. */
    public function save(Request $request)
    {
        $isUpdate = $request->filled('id');

        $validator = Validator::make($request->all(), [
            'id'        => 'nullable|exists:coe_signatories,id',
            'name'      => 'required|string|max:120',
            'title'     => 'nullable|string|max:120',
            'is_active' => 'nullable|boolean',
            'signature' => [$isUpdate ? 'nullable' : 'required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $signatory = $isUpdate ? CoeSignatory::findOrFail($request->id) : new CoeSignatory();
        $signatory->name      = $request->name;
        $signatory->title     = $request->title;
        $signatory->is_active = $request->boolean('is_active', true);

        if ($request->hasFile('signature')) {
            $dir = public_path(self::DIR);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $ext  = $request->file('signature')->getClientOriginalExtension();
            $name = 'sig_' . uniqid() . '.' . $ext;
            $request->file('signature')->move($dir, $name);

            // Remove the previous image on replacement.
            if ($isUpdate && $signatory->signature_path && is_file(public_path($signatory->signature_path))) {
                @unlink(public_path($signatory->signature_path));
            }
            $signatory->signature_path = self::DIR . '/' . $name;
        }

        $signatory->save();   // instance save → Auditable

        return response()->json(['status' => 200, 'msg' => $isUpdate ? 'Signatory updated.' : 'Signatory added.']);
    }

    public function delete(Request $request)
    {
        $signatory = CoeSignatory::find($request->id);
        if (!$signatory) {
            return response()->json(['status' => 202, 'msg' => 'Signatory not found.']);
        }
        if ($signatory->signature_path && is_file(public_path($signatory->signature_path))) {
            @unlink(public_path($signatory->signature_path));
        }
        $signatory->delete();   // instance delete → Auditable

        return response()->json(['status' => 200, 'msg' => 'Signatory deleted.']);
    }
}

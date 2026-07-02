<?php

namespace App\Http\Controllers;

use DB;
use Validator;
use App\Models\department;
use App\Models\DepartmentDocument;
use Illuminate\Http\Request;

class departmentCtrl extends Controller
{
    /** Where department PDF documents are stored, relative to public/. */
    private const DOC_DIR = 'docs/departments';
    /** Where department logos are stored, relative to public/. */
    private const LOGO_DIR = 'img/departments';

    public function create_update(Request $request){
        $values = [
            'dep_name'                   => $request->department,
            'dep_address'                => $request->dep_address,
            'dep_contact_phone'          => $request->dep_contact_phone,
            'dep_email'                  => $request->dep_email,
            'dep_tin'                    => $request->dep_tin,
            'dep_sss_employer_no'        => $request->dep_sss_employer_no,
            'dep_philhealth_employer_no' => $request->dep_philhealth_employer_no,
            'dep_pagibig_employer_no'    => $request->dep_pagibig_employer_no,
            'dep_description'            => $request->dep_description,
        ];
        $validator = Validator::make($request->all(),[
            'department' => 'required|max:191',
            'dep_email'  => 'nullable|email|max:191',
            'logo'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $checkIfExist =  department::where('dep_name',$request->department);
                        if($checkIfExist->count()>0){
                            return response()->json(['status'=>200, 'msg'=>'Department exist!']);
                        }
                    if ($request->hasFile('logo')) {
                        $values['dep_logo_path'] = $this->storeLogo($request);
                    }
                    $insert = department::create($values);
                }else{
                    $record = department::where('id',$request->depID)->first();
                    if ($record && $request->hasFile('logo')) {
                        // Remove the previous logo on replacement.
                        if ($record->dep_logo_path && is_file(public_path(self::LOGO_DIR.'/'.$record->dep_logo_path))) {
                            @unlink(public_path(self::LOGO_DIR.'/'.$record->dep_logo_path));
                        }
                        $values['dep_logo_path'] = $this->storeLogo($request);
                    }
                    $insert = $record ? $record->forceFill($values)->save() : false;
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Department Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Department Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);

            }

        }

    }
    
    // public function getall(Request $request){
    //     $getDepartment = department::latest()->get();
    //     if($getDepartment){
    //         return response()->json(['status'=>200, 'data'=>$getDepartment]);
    //     }
    // }

    public function getall() {
    // Sort by dep_name in descending order (Z to A)
    $departments = department::orderBy('dep_name', 'asc')->get();

    if($departments) {
        return response()->json([
            'status' => 200, 
            'data' => $departments
        ]);
    }
    
    return response()->json([
        'status' => 404, 
        'msg' => 'No departments found.'
    ]);
}

    public function edit(Request $request){
        $getDepartment = department::where('id',$request->depID)->get();
        if($getDepartment){
            return response()->json(['status'=>200, 'data'=>$getDepartment]);
        }
    }
    public function delete(Request $request)
    {
        try {
            // Find the department using the depID from the request
            $record = department::where('id', $request->depID)->first();

            // Clean up document files + logo on disk (DB rows cascade, files do not).
            if ($record) {
                foreach ($record->documents as $doc) {
                    if ($doc->file_path && is_file(public_path($doc->file_path))) {
                        @unlink(public_path($doc->file_path));
                    }
                }
                $docDir = public_path(self::DOC_DIR.'/'.$record->id);
                if (is_dir($docDir)) {
                    @rmdir($docDir);
                }
                if ($record->dep_logo_path && is_file(public_path(self::LOGO_DIR.'/'.$record->dep_logo_path))) {
                    @unlink(public_path(self::LOGO_DIR.'/'.$record->dep_logo_path));
                }
            }

            $delete = $record ? $record->delete() : false;

            if ($delete) {
                return response()->json([
                    'status' => 200, 
                    'msg' => 'Department deleted successfully.'
                ]);
            } else {
                return response()->json([
                    'status' => 202, 
                    'msg' => 'Department not found or already deleted.'
                ]);
            }

        } catch (\Exception $e) {
            // Return the system error so SweetAlert can display it
            return response()->json([
                'status' => 500,
                'msg' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }

    /** Move the uploaded logo into public/img/departments and return its filename. */
    private function storeLogo(Request $request)
    {
        $dir = public_path(self::LOGO_DIR);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $request->file('logo');
        $name = time().'_'.preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $file->move($dir, $name);
        return $name;
    }

    /** JSON: list of documents for a department (Related Documents panel). */
    public function documents(Request $request)
    {
        $docs = DepartmentDocument::where('department_id', $request->depID)
                ->latest()
                ->get();

        return response()->json(['status' => 200, 'data' => $docs]);
    }

    /** Upload a single PDF document to a department. */
    public function uploadDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'depID'    => 'required|exists:departments,id',
            'label'    => 'nullable|max:191',
            'document' => 'required|file|mimes:pdf|max:10240', // 10 MB
        ]);
        if (!$validator->passes()) {
            return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
        }

        $file = $request->file('document');
        $size = $file->getSize();   // capture BEFORE move() — the temp file is gone afterwards
        $originalName = $file->getClientOriginalName();

        $dir = public_path(self::DOC_DIR.'/'.$request->depID);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $stored = time().'_'.bin2hex(random_bytes(4)).'.pdf';
        $file->move($dir, $stored);

        $doc = DepartmentDocument::create([
            'department_id' => $request->depID,
            'label'         => $request->label ?: $originalName,
            'file_name'     => $stored,
            'file_path'     => self::DOC_DIR.'/'.$request->depID.'/'.$stored,
            'original_name' => $originalName,
            'size'          => $size ?: null,
            'mime'          => 'application/pdf',
            'uploaded_by'   => optional($request->user())->name,
        ]);

        return response()->json(['status' => 200, 'msg' => 'Document uploaded.', 'data' => $doc]);
    }

    /** Stream a department document back to the browser as a download. */
    public function downloadDocument(Request $request)
    {
        $doc = DepartmentDocument::findOrFail($request->id);
        $abs = public_path($doc->file_path);
        abort_unless(is_file($abs), 404);

        return response()->download($abs, $doc->original_name);
    }

    /** Delete a department document (DB row + file on disk). */
    public function deleteDocument(Request $request)
    {
        $doc = DepartmentDocument::find($request->id);
        if (!$doc) {
            return response()->json(['status' => 202, 'msg' => 'Document not found.']);
        }
        if ($doc->file_path && is_file(public_path($doc->file_path))) {
            @unlink(public_path($doc->file_path));
        }
        $doc->delete();   // instance delete → Auditable

        return response()->json(['status' => 200, 'msg' => 'Document deleted.']);
    }
}

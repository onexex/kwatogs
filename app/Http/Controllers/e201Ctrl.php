<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\e201;
use Illuminate\Http\Request;

class e201Ctrl extends Controller
{
    //
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(),[
            // 'search'=>'required',

            'name'=>'required',
            'file'=>'required',
        ],[
            'file.required'=>"Filename is required!",
            'file' => 'required|mimes:pdf,doc,docx|max:2048',
            'file.required' => 'File is required!',
            'file.mimes' => 'Only PDF, DOC, and DOCX files are allowed for file!',
            'file.max' => 'File should not exceed 2MB in size!',
        ]);

        if(!$validator->passes()){
            return response()->json(['status'=>201, 'msg'=>"Please see the required fields!", 'error'=>$validator->errors()->toArray()]);
        }else{
            $id = $request->id;

            $name = $request->name;
            $extension = $request->file('file')->getClientOriginalExtension();
            $fileName = $name . '' . $id . '.' . $extension;
            $filePath = $fileName;
            $request->file('file')->move(public_path('file'), $fileName);

            $values= [
                // 'empID'=>$request->selectedspcs,
                'empID'=>$request->selectedempID,

                'path'=> $filePath,
            ];

            $insert = e201::create($values);
            if($insert){
                return response()->json(['status'=>200,'msg'=>"Inserting files succesfully!"]);
                // return response()->json(['status'=>200]);
            }else{
                return response()->json(['status'=>202,'msg'=>"Error saving!"]);
            }
        }
    }

    public function get_empID(Request $request)
    {
        $id=$request->id;
        $getQuery=e201::where('empID',$id)
        ->get();

        return response()->json(['status'=>200,'data'=>$getQuery]);

    }

    public function getall(Request $request)
    {
        $path=asset("file").'/';
        $getA=e201::where('empID',$request->id)
        ->get();
        return response()->json(['stat'=>200,'data'=>$getA,'path'=>$path ]);

        // asset('assets/img/default-logo.png'),
    }

    // public function getfiles(Request $request)
    // {
    //     $id = $request->id;

    //     $getA=e201::where('id',$request->id)
    //     ->get();

    //     return response()->json(['status' => 200, 'data' => $getA]);
    // }

}

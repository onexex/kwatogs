<?php

namespace App\Http\Controllers;

use DB;
use Validator;
use App\Models\department;
use Illuminate\Http\Request;

class departmentCtrl extends Controller
{
    public function create_update(Request $request){
        $values = [
            'dep_name'=>$request->department,
        ];
        $validator = Validator::make($request->all(),[
            'department'=>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $checkIfExist =  department::where('dep_name',$request->department);
                        if($checkIfExist->count()>0){
                            return response()->json(['status'=>200, 'msg'=>'Department exist!']);
                        }
                    $insert = department::create($values);
                }else{
                    $insert = department::where('id',$request->depID)->update($values);
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
            $delete = department::where('id', $request->depID)->delete();

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
}

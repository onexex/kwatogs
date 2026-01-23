<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\classification;
use DB;
use Validator;

class classiticationCtrl extends Controller
{
   public function create_update(Request $request)
    {
        try {
            $values = [
                'class_code' => $request->code,
                'class_desc' => $request->description,
            ];

            $validator = Validator::make($request->all(), [
                'code' => 'required',
                'description' => 'required',
            ]);

            if (!$validator->passes()) {
                return response()->json(['status' => 201, 'error' => $validator->errors()->toArray()]);
            }

            // Action: CREATE
            if ($request->formAction == 1) {
                $checkIfExist = classification::where('class_code', $request->code)->first();
                
                if ($checkIfExist) {
                    return response()->json(['status' => 202, 'msg' => 'Classification code already exists!']);
                }

                $query = classification::create($values);
                $message = 'Classification Created.';
            } 
            // Action: UPDATE
            else {
                // Note: Fixed $request->id to match your JS classID if necessary
                $query = classification::where('id', $request->classID)->update($values);
                $message = 'Classification Updated.';
            }

            if ($query) {
                return response()->json(['status' => 200, 'msg' => $message]);
            } else {
                return response()->json(['status' => 202, 'msg' => 'No changes were made or save failed.']);
            }

        } catch (\Exception $e) {
            // This catches any system/database error and returns the message
            return response()->json([
                'status' => 500, 
                'msg' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }

    public function get_all(Request $request){
        $getClassification = classification::latest()->get();
        if($getClassification){
            return response()->json(['status'=>200, 'data'=>$getClassification]);
        }
    }

    public function edit(Request $request){
        $id=$request->classID;
        $getClassification = classification::where('id', $id)->get();
        return response()->json(['status'=>200, 'data'=>$getClassification]);
    }


    public function delete(Request $request){
        //1 super user
        //2 admin
        //3 normal user

        $user=session()->get('LoggedUserID');
        $role=session()->get('LoggedUserRole');
        $id=$request->id;

        if($role!=1){
            return response()->json(['status'=>200, 'msg'=>'Access Denied. Required superuser access for this request!']);
        }else{
            $delete = classification::where('id', $id)->delete();
            if($delete){
                return response()->json(['status'=>200, 'msg'=>"Classification deleted successfully."]);
            }else{
                return response()->json(['status'=>200, 'msg'=>"Error"]);
            }
        }
    }
}

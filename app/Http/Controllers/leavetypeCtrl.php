<?php

namespace App\Http\Controllers;

use DB;
use Validator;
use App\Models\leavetype;
use Illuminate\Http\Request;

class leavetypeCtrl extends Controller
{
        //1 super user
        //2 admin
        //3 normal user
       
        // $user=session()->get('LoggedUserID');
        // $role=session()->get('LoggedUserRole');
        // $comp=session()->get('LoggedUserComp');
    public function create_update(Request $request){
        $values = [
            'type_leave'=>$request->leave,
        ];
        $validator = Validator::make($request->all(),[
            'leave'=>'required|alpha',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $checkIfExist =  leavetype::where('type_leave',$request->leave);
                        if($checkIfExist->count()>0){
                            return response()->json(['status'=>200, 'msg'=>'Leave Type exist!']);
                        }
                    $insert = leavetype::create($values);
                }else{
                    $insert = leavetype::where('id',$request->leaveTypeID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Leave Type Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Leave Type Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);

            }
        }
    }
    
    public function getall(Request $request){
        $getLeavetype = leavetype::latest()->get();
        if($getLeavetype){
            return response()->json(['status'=>200, 'data'=>$getLeavetype]);
        }
    }
   
    public function edit(Request $request){
        $getLeavetype = leavetype::where('id',$request->leaveTypeID)->get();
        if($getLeavetype){
            return response()->json(['status'=>200, 'data'=>$getLeavetype]);
        }
    }
}

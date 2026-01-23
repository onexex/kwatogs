<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

use DB;
use Validator;

class roleCtrl extends Controller
{
    //1 super user
        //2 admin
        //3 normal user
       
        // $user=session()->get('LoggedUserID');
       
        // $comp=session()->get('LoggedUserComp');
    public function create_update(Request $request){
        $role=session()->get('LoggedUserRole');
        if($role==1){
            $values = [
                'role'=>$request->role,
            ];
            $validator = Validator::make($request->all(),[
                'role'=>'required',
            ]);
            if(!$validator->passes()){
                return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
            }else{
                    if($request->formAction==1){
                        $checkIfExist =  User::where('type_leave',$request->leave);
                            if($checkIfExist->count()>0){
                                return response()->json(['status'=>200, 'msg'=>'Role Type exist!']);
                            }
                        $insert = User::create($values);
                    }else{
                        $insert = User::where('id',$request->userID)->update($values);
                    }
                if($insert){
                    if($request->formAction==1){
                        return response()->json(['status'=>200, 'msg'=>'Role Type Created.']);
                    }else{
                        return response()->json(['status'=>200, 'msg'=>'Role Type Updated.']);
                    }
                }else{
                    return response()->json(['status'=>202, 'msg'=>'Error saving']);
                }
            }
        }else{
            return response()->json(['status'=>202, 'msg'=>'Required Elevated User access.']);
        } 
    }

    public function search(Request $request){
        $getUsers = User::where('lname','like','%'. $request->lastname .'%')
        ->latest()->get();
        if($getUsers->count()>0){
            return response()->json(['status'=>200, 'data'=>$getUsers]);
        }else{
            return response()->json(['status'=>199, ]);

        }
    }

    public function edit(Request $request){
        $getUsers = User::where('id',$request->userID)->get();
        if($getUsers){
            return response()->json(['status'=>200, 'data'=>$getUsers]);
        }
    }
}

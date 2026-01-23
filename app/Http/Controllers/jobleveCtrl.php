<?php

namespace App\Http\Controllers;

use App\Models\joblevel;
use Illuminate\Http\Request;
use DB;
use Validator;

class jobleveCtrl extends Controller
{
    
    public function create_update(Request $request){
        $values = [
            'job_desc'=>$request->job,
        ];
        $validator = Validator::make($request->all(),[
            'job'=>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $checkIfExist =  joblevel::where('job_desc',$request->job);
                        if($checkIfExist->count()>0){
                            return response()->json(['status'=>200, 'msg'=>'Joblevel exist!']);
                        }
                    $insert = joblevel::create($values);
                }else{
                    $insert = joblevel::where('id',$request->jobID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Joblevel Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Joblevel Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);

            }

        }

    }
    
    public function get_all(Request $request){
        $getJobLevel = joblevel::latest()->get();
        if($getJobLevel){
            return response()->json(['status'=>200, 'data'=>$getJobLevel]);
        }
    }

    public function edit(Request $request){
        $getJobLevel = joblevel::where('id',$request->jobID)->get();
        if($getJobLevel){
            return response()->json(['status'=>200, 'data'=>$getJobLevel]);
        }
    }
}

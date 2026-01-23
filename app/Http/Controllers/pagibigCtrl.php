<?php

// JMC 
namespace App\Http\Controllers;

use Validator;
use App\Models\pagibigMdl;
use Illuminate\Http\Request;

class pagibigCtrl extends Controller
{
    public function create_update(Request $request){
        $values = [
            'EE'=>$request->ee,
            'ER'=>$request->er,
        ];
        $validator = Validator::make($request->all(),[
            'ee'=>'required|integer',
            'er'=>'required|integer',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $checkIfExist =  pagibigMdl::where('EE',$request->ee);
                        if($checkIfExist->count()>0){
                            return response()->json(['status'=>200, 'msg'=>'EE exist!']);
                        }
                    $insert = pagibigMdl::create($values);
                }else{
                    $insert = pagibigMdl::where('id',$request->updateID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'SSS Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'SSS Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);

            }

        }

    }
    
    public function getall(Request $request){
        $getAll = pagibigMdl::orderBy('created_at', 'asc')->get();
        if($getAll){
            return response()->json(['status'=>200, 'data'=>$getAll]);
        }
    }

    public function edit(Request $request){
        $getEOVal = pagibigMdl::where('id',$request->updateID)->get();
        if($getEOVal){
            return response()->json(['status'=>200, 'data'=>$getEOVal]);
        }
    }
}

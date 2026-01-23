<?php

// JMC 
namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Models\philhealthModel;

class philhealthCtrl extends Controller
{
    public function create_update(Request $request){
        $values = [
            'phsb'=>$request->PHSB,
            'salaryFrom'=>$request->from,
            'salaryTo'=>$request->to,
            'phee'=>$request->PHEE,
            'pher'=>$request->PHER,
        ];
        $validator = Validator::make($request->all(),[
            'PHSB'=>'required',
            'from'=>'required',
            'to'=>'required',
            'PHEE'=>'required',
            'PHER'=>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    // $checkIfExist =  philhealthModel::where('EE',$request->ee);
                    //     if($checkIfExist->count()>0){
                    //         return response()->json(['status'=>200, 'msg'=>'EE exist!']);
                    //     }
                    $insert = philhealthModel::create($values);
                }else{
                    $insert = philhealthModel::where('id',$request->updateID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Philhealth Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Philhealth Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);

            }

        }

    }
    
    public function getall(Request $request){
        $getAll = philhealthModel::orderBy('created_at', 'asc')->get();
        if($getAll){
            return response()->json(['status'=>200, 'data'=>$getAll]);
        }
    }

    public function edit(Request $request){
        $getEOVal = philhealthModel::where('id',$request->updateID)->get();
        if($getEOVal){
            return response()->json(['status'=>200, 'data'=>$getEOVal]);
        }
    }
}

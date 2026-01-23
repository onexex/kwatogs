<?php

// JMC 
namespace App\Http\Controllers;

use Validator;
use App\Models\EOVModel;
use Illuminate\Http\Request;

class eovalidationCtrl extends Controller
{
    public function create_update(Request $request){
        $values = [
            'before'=>$request->before,
            'after'=>$request->after,
            'tardy'=>$request->tardy,
        ];
        $validator = Validator::make($request->all(),[
            'before'=>'required',
            'after'=>'required',
            'tardy'=>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    // $checkIfExist =  EOVModel::where('type_leave',$request->leave);
                    //     if($checkIfExist->count()>0){
                    //         return response()->json(['status'=>200, 'msg'=>'Leave Type exist!']);
                    //     }
                    $insert = EOVModel::create($values);
                }else{
                    $insert = EOVModel::where('id',$request->updateID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Early Our Validation Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Early Our Validation Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);

            }

        }

    }
    
    public function getall(Request $request){
        $getLeavetype = EOVModel::latest()->get();
        if($getLeavetype){
            return response()->json(['status'=>200, 'data'=>$getLeavetype]);
        }
    }

    public function edit(Request $request){
        $getEOVal = EOVModel::where('id',$request->updateID)->get();
        if($getEOVal){
            return response()->json(['status'=>200, 'data'=>$getEOVal]);
        }
    }
}

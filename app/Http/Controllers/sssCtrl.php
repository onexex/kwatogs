<?php

// JMC 
namespace App\Http\Controllers;

use Validator;
use App\Models\SSSModel;
use Illuminate\Http\Request;

class sssCtrl extends Controller
{
    public function create_update(Request $request){
        $values = [
            'sssc'=>$request->sssc,
            'from'=>$request->salaryfrom,
            'to'=>$request->salaryto,
            'sser'=>$request->sser,
            'ssee'=>$request->ssee,
            'ssec'=>$request->ssec,
        ];
        $validator = Validator::make($request->all(),[
            'sssc'=>'required|integer',
            'salaryfrom'=>'required|integer',
            'salaryto'=>'required',
            'sser'=>'required',
            'ssee'=>'required',
            'ssec'=>'required|integer',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    // $checkIfExist =  EOVModel::where('type_leave',$request->leave);
                    //     if($checkIfExist->count()>0){
                    //         return response()->json(['status'=>200, 'msg'=>'Leave Type exist!']);
                    //     }
                    $insert = SSSModel::create($values);
                }else{
                    $insert = SSSModel::where('id',$request->updateID)->update($values);
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
        $getSSS = SSSModel::orderBy('created_at', 'asc')->get();
        if($getSSS){
            return response()->json(['status'=>200, 'data'=>$getSSS]);
        }
    }

    public function edit(Request $request){
        $getEOVal = SSSModel::where('id',$request->updateID)->get();
        if($getEOVal){
            return response()->json(['status'=>200, 'data'=>$getEOVal]);
        }
    }
}

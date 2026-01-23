<?php

namespace App\Http\Controllers;
use DB;

use Validator;
use App\Models\otfiling;
use Illuminate\Http\Request;

class otfillingCtrl extends Controller
{
    public function create_update(Request $request){
        $values = [
            'comp_id'=>$request->company,
            'filebefore'=>$request->before,
            'fileafter'=>$request->after,
            'no_days_before'=>$request->daysBefore,
            'no_days_after'=>$request->daysAfter,
            'holiday'=>$request->holiday,
            'tardy'=>$request->tardy,
        ];
        $validator = Validator::make($request->all(),[
            'company'=>'required',
            'before'=>'required',
            'after'=>'required',
            'holiday'=>'required',
            'tardy'=>'required',
            'daysBefore'=>'required',
            'daysAfter'=>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $checkIfExist =  otfiling::where('comp_id',$request->company);
                        if($checkIfExist->count()>0){
                            return response()->json(['status'=>200, 'msg'=>'Validation exist!']);
                        }
                    $insert = otfiling::create($values);
                }else{
                    $insert = otfiling::where('id',$request->OTFilID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Validation Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Validation Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);
            }
        }
    }
    
    public function getall(Request $request){
        $getOTFilingMaintenance = otfiling::join('companies','otfilings.comp_id','=','companies.comp_id')
        ->latest('otfilings.created_at')
        ->select('companies.*','otfilings.*','otfilings.id as ids')
        ->get();
        if($getOTFilingMaintenance){
            return response()->json(['status'=>200, 'data'=>$getOTFilingMaintenance]);
        }
    }

    public function edit(Request $request){
        $getOTFilingMaintenance = otfiling::where('id',$request->OTFilID)->get();
        if($getOTFilingMaintenance){
            return response()->json(['status'=>200, 'data'=>$getOTFilingMaintenance]);
        }
    }
}

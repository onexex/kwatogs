<?php

namespace App\Http\Controllers;

use DB;

use Validator;
use Illuminate\Http\Request;
use App\Models\leavevalidationModel;

class leavevalidationCtrl extends Controller
{
    public function create_update(Request $request){
        $values = [
            'compID'=>$request->company,
            'leave_type'=>$request->leave,
            'credits'=>$request->credits,
            'min_leave'=>$request->minimum,
            'no_before_file'=>$request->daysbefore,
            'no_after_file'=>$request->daysafter,
            'file_before'=>$request->filebefore,
            'file_after'=>$request->fileafter,
            'file_halfday'=>$request->halfday,
        ];
        $validator = Validator::make($request->all(),[
            'company'=>'required',
            'leave'=>'required',
            'credits'=>'required',
            'minimum'=>'required',
            'daysbefore'=>'required',
            'daysafter'=>'required',
            'filebefore'=>'required',
            'fileafter'=>'required',
            'halfday'=>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $checkIfExist =  leavevalidationModel::where('leave_type',$request->leave)
                    ->where('compID',$request->company);
                        if($checkIfExist->count()>0){
                            return response()->json(['status'=>200, 'msg'=>'Validation exist!']);
                        }
                    $insert = leavevalidationModel::create($values);
                }else{
                    $insert = leavevalidationModel::where('id',$request->leaveValID)->update($values);
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
        $getLeavevalidationModel = leavevalidationModel::join('companies','leavevalidation_models.compID','=','companies.comp_id')
        ->join('leavetypes','leavevalidation_models.leave_type','=','leavetypes.id')
        ->latest('leavevalidation_models.created_at')
        ->select('companies.*','leavevalidation_models.*','leavevalidation_models.id as ids','leavetypes.*')
        ->get();
        if($getLeavevalidationModel){
            return response()->json(['status'=>200, 'data'=>$getLeavevalidationModel]);
        }
    }

    public function edit(Request $request){
        $getLeavevalidationModel = leavevalidationModel::where('id',$request->leaveValID)->get();
        if($getLeavevalidationModel){
            return response()->json(['status'=>200, 'data'=>$getLeavevalidationModel]);
        }
    }
}

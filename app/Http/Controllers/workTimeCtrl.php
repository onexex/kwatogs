<?php

namespace App\Http\Controllers;

use DB;
use Validator;
use App\Models\worktime;
use Illuminate\Http\Request;

class workTimeCtrl extends Controller
{

    public function create_update(Request $request){
        $values = [
            'wt_timefrom'=>$request->from,
            'wt_timeto'=>$request->to,
            'wt_timecross'=>$request->cross,
        ];
        $validator = Validator::make($request->all(),[
            'from'=>'required',
            'to'=>'required',
            'cross'=>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $insert = worktime::create($values);
                }else{
                    $insert = worktime::where('id',$request->wtID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Worktime Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Worktime Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);

            }

        }
    }

    public function wt_get(Request $request){
        $getWT = worktime::get();

        if($getWT){
            return response()->json(['status'=>200, 'data'=>$getWT]);
        }
    }

    public function wt_edit(Request $request){
        $wtID=$request->wtID;
        $getWT = worktime::where('id', $wtID)->get();
        return response()->json(['status'=>200, 'data'=>$getWT]);


    }

}

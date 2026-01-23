<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\obValidations;
use Validator;


class obValidationsCtrl extends Controller
{
    //
    //getall
    public function getall(Request $request){
        $getA = obValidations::latest('created_at')->get();
            if($getA){
                return response()->json(['status'=>200, 'data'=>$getA]);
            }
    }
    //edit
    public function edit(Request $request){
        $obID=$request->obID;
        $getA = obValidations::where('id', $obID)->get();
        return response()->json(['status'=>200, 'data'=>$getA]);
    }

    //create_update
    public function create_update(Request $request)
    {
        $value =[
            'ob_fBefore' => $request->filebefore,
            'ob_dBefore' => $request->daysbefore,
            'ob_fAfter' => $request->fileafter,
            'ob_dAfter' => $request->daysafter,
        ];
        //validator script
        $validator = Validator::make($request->all(),[
            'filebefore' =>'required',
            'daysbefore' =>'required',
            'fileafter' =>'required',
            'daysafter' =>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }
        else{
            if($request->formAction==1){
                $query = obValidations::create($value);
            }else{
                $query = obValidations::where('id',$request->obID)->update( $value);
            }
            if($query){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'OB Validation Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'OB Validation Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error']);
            }
        }
    }

}

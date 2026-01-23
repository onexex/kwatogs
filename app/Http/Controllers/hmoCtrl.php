<?php

// JMC 
namespace App\Http\Controllers;

use Validator;
use App\Models\HMOModel;
use Illuminate\Http\Request;

class hmoCtrl extends Controller
{
    public function create_update(Request $request)
    {
        $values = [
            'idNo'=>$request->id,
            'hmoName'=>$request->name,
        ];

        $validator = Validator::make($request->all(),[
            'id'=>'required',
            'name'=>'required',
        ]);

        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
            if($request->formAction==1){
                $checkIfExist =  HMOModel::where('idNo',$request->txtID);
                    if($checkIfExist->count()>0){
                        return response()->json(['status'=>200, 'msg'=>'ID Number is already taken!']);
                    }
                    else{
                        $insert = HMOModel::create($values);
                    }
            }else{
                $insert = HMOModel::where('id',$request->updateID)->update($values);
            }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'HMO Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'HMO Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);
            }
        }


    }

    public function getHMO()
    {
        $data = HMOModel::where('hmoStatus', 1)->get();

        return response()->json(['status'=>1, 'data'=>$data]);
    }
    
    public function getData(Request $request)
    {
        $id = $request->id;

        $data = HMOModel::where('hmoStatus', 1)
        ->where('id', $id)
        ->get();

        return response()->json(['status'=>1, 'data'=>$data]);
    }
}

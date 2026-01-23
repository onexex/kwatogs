<?php
namespace App\Http\Controllers;

use App\Models\agencies;

use Illuminate\Http\Request;
use Validator;
use DB;

class agenciesCtrl extends Controller
{
    //save and update agencies
    public function create_update(Request $request){
        $values = [
            'ag_name'=>$request->agency,
            'ag_status'=>$request->status,
        ];
        $validator = Validator::make($request->all(),[
            'agency'=>'required',
            'status'=>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $insert = agencies::create($values);
                }else{
                    $insert = agencies::where('id',$request->agID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Agency Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Agency Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error']);
            }
        }
    }

    public function getall(Request $request){
        $getA = agencies::get();
            if($getA){
                return response()->json(['status'=>200, 'data'=>$getA]);
            }
    }

    public function edit(Request $request){
        $agID=$request->agID;
        $getA = agencies::where('id', $agID)->get();
        return response()->json(['status'=>200, 'data'=>$getA]);
    }


}

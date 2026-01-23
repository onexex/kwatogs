<?php
// JMC 
namespace App\Http\Controllers;

use Validator;
use App\Models\employeeStatusModel;
use Illuminate\Http\Request;

class empStatCtrl extends Controller
{
    public function create_update(Request $request)
    {
        $values = [
            'empStatName'=>$request->employeestatus,
        ];

        $validator = Validator::make($request->all(),[
            'employeestatus'=>'required'
        ]);

        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
            if($request->formAction==1){
                $checkIfExist =  employeeStatusModel::where('empStatName',$request->txtEmployeeStatus);
                    if($checkIfExist->count()>0){
                        return response()->json(['status'=>200, 'msg'=>'Employee Status Name is already taken!']);
                    }
                    else{
                        $insert = employeeStatusModel::create($values);
                    }
            }else{
                $insert = employeeStatusModel::where('id',$request->updateID)->update($values);
            }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Employee Status Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Employee Status Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);
            }
        }


    }

    public function getEmployeeStatus()
    {
        $data = employeeStatusModel::get();

        return response()->json(['status'=>1, 'data'=>$data]);
    }
    
    public function getData(Request $request)
    {
        $id = $request->id;

        $data = employeeStatusModel::where('id', $id)
        ->get();

        return response()->json(['status'=>1, 'data'=>$data]);
    }
}

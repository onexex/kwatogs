<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\User;
use App\Models\silModel;
use Illuminate\Http\Request;

class silCtrl extends Controller
{
    public function getusers()
    {
        $result = User::get();
        return response()->json(['status'=>1, 'data'=>$result]);
    }

    public function getall(Request $request)
    {
        $result = silModel::join('users', 'silloan.silEmpID', '=', 'users.empID')
        ->select('users.*', 'silloan.*', 'silloan.silStatus as loanStatus')
        ->get();
        return response()->json(['status'=>1, 'data'=>$result]);
    }

    public function create_update(Request $request){
        $values = [
            'silEmpID'=>$request->employee,
            'silAmount'=>$request->loan,
            'silType'=>$request->type,
            'silStatus'=>$request->status,
            'silDate'=>$request->now,
        ];
        $validator = Validator::make($request->all(),[
            'employee'=>'required',
            'loan'=>'required',
            'type'=>'required',
            'status'=>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    // $checkIfExist =  EOVModel::where('type_leave',$request->leave);
                    //     if($checkIfExist->count()>0){
                    //         return response()->json(['status'=>200, 'msg'=>'Leave Type exist!']);
                    //     }
                    $insert = silModel::create($values);
                }else{
                    $insert = silModel::where('id',$request->updateID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'SIL Loan Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'SIL Loan Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);

            }

        }

    }

    public function edit(Request $request){
        $getsil = silModel::where('id',$request->updateID)->get();
        if($getsil){
            return response()->json(['status'=>200, 'data'=>$getsil]);
        }
    }
}

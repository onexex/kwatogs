<?php

namespace App\Http\Controllers;
use Validator;

use Illuminate\Http\Request;
use App\Models\parentalSettings;

class parentalSettingsCtrl extends Controller
{
    //

    //getall
    public function getall(Request $request){
        // $getA = parentalSettings::get();
        //     if($getA){
        //         return response()->json(['status'=>200, 'data'=>$getA]);
        //     }

        $getA=parentalSettings::join('users as u','parental_settings.prtl_empID','=','u.empID')
        ->select("u.*",'parental_settings.*')
        ->get();

        return response()->json(['status'=>200, 'data'=>$getA]);

    }
    //edit
    public function edit(Request $request){
        $id=$request->pID;
        $getUM = parentalSettings::where('id', $id)->get();
        return response()->json(['status'=>200, 'data'=>$getUM]);
    }

    //create_update
    public function create_update(Request $request){
        $values = [
            'prtl_nameFam'=>$request->family,
            'prtl_empID'=>$request->selectedEmpList,
            'prtl_birthday'=>$request->birthday,
        ];
        $validator = Validator::make($request->all(),[
            'family'=>'required',
            'employee'=>'required',
            'birthday'=>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $checkIfExist =  parentalSettings::where('prtl_nameFam',$request->family);
                        if($checkIfExist->count()>0){
                            return response()->json(['status'=>200, 'msg'=>'This family member was already exist!']);
                        }
                    $insert = parentalSettings::create($values);
                }else{
                    $insert = parentalSettings::where('id',$request->pID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Family details created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Family details updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);
            }
        }
    }

    public function delete_record (Request $request)
    {
        $query=parentalSettings::where('id',$request->id)->delete();
        if($query){
            return response()->json(['stat'=>200,'msg'=>"Record has been successfully Deleted! "]);
        }
        else
        {
            return response()->json(['stat'=>202,'msg'=>"Error Deleting file! ",'x'=>$request->id]);
        }
    }

}

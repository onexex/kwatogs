<?php

namespace App\Http\Controllers;

use DB;
use Validator;
use App\Models\relationship;
use Illuminate\Http\Request;

class relationshipCtrl extends Controller
{
    public function create_update(Request $request){
        $values = [
            'relation'=>$request->relationship,
        ];
        $validator = Validator::make($request->all(),[
            'relationship'=>'required|alpha',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $checkIfExist =  relationship::where('relation',$request->relationship);
                        if($checkIfExist->count()>0){
                            return response()->json(['status'=>200, 'msg'=>'Relationship exist!']);
                        }
                    $insert = relationship::create($values);
                }else{
                    $insert = relationship::where('id',$request->relID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Relationship Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Relationship Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);

            }

        }

    }
    
    public function getall(Request $request){
        $getRelationship = relationship::latest()->get();
        if($getRelationship){
            return response()->json(['status'=>200, 'data'=>$getRelationship]);
        }
    }

    public function edit(Request $request){
        $getRelationship = relationship::where('id',$request->relID)->get();
        if($getRelationship){
            return response()->json(['status'=>200, 'data'=>$getRelationship]);
        }
    }
}

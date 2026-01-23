<?php

namespace App\Http\Controllers;

use App\Models\position;
use Illuminate\Http\Request;
use DB;
use Validator;

class positionCtrl extends Controller
{
    public function create_update(Request $request){
        $values = [
            'pos_desc'=>$request->position,
            'pos_jl'=>$request->job,
            'pos_jl_desc'=>$request->jobdesc,
        ];
        $validator = Validator::make($request->all(),[
            'position'=>'required',
            'job'=>'required',
        ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $checkIfExist =  position::where('pos_desc',$request->position);
                        if($checkIfExist->count()>0){
                            return response()->json(['status'=>200, 'msg'=>'Position exist!']);
                        }
                    $insert = position::create($values);
                }else{
                    $insert = position::where('id',$request->posID)->update($values);
                }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Position Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Position Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);

            }

        }

    }
    
public function get_all(Request $request)
{
    // Fetches positions ordered alphabetically by description (A to Z)
    $getPosition = position::orderBy('pos_desc', 'asc')->get();

    if ($getPosition) {
        return response()->json([
            'status' => 200, 
            'data' => $getPosition
        ]);
    }

    return response()->json([
        'status' => 404, 
        'msg' => 'No positions found.'
    ]);
}

    public function edit(Request $request){
        $getPosition = position::where('id',$request->posID)->get();
        if($getPosition){
            return response()->json(['status'=>200, 'data'=>$getPosition]);
        }
    }

    public function delete(Request $request)
    {
        try {
            // Find and delete the position using the posID passed from Axios
            $delete = position::where('id', $request->posID)->delete();

            if ($delete) {
                return response()->json([
                    'status' => 200, 
                    'msg' => 'Position has been successfully removed.'
                ]);
            } else {
                // Case where ID exists but delete fails, or ID was already gone
                return response()->json([
                    'status' => 202, 
                    'msg' => 'Position not found or already deleted.'
                ]);
            }

        } catch (\Exception $e) {
            // Catches database constraints (e.g., if position is linked to an employee)
            return response()->json([
                'status' => 500,
                'msg' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }
}

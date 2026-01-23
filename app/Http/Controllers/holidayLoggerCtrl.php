<?php

// JMC 
namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Models\holidayLoggerModel;

class holidayLoggerCtrl extends Controller
{
    public function create_update(Request $request)
    {
        $values = [
            'date'=>$request->date,
            'description'=>$request->description,
            'type'=>$request->type
        ];

        $validator = Validator::make($request->all(),[
            'date'=>'required',
            'description'=>'required',
            'type'=>'required',
        ]);

        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
            //     $insert = holidayLoggerModel::create($values);
                
            // if($insert){
            //     return response()->json(['status'=>200, 'msg'=>'Holiday Logger Created.']);
            // }else{
            //     return response()->json(['status'=>202, 'msg'=>'Error saving']);
            // }
            if($request->formAction==1){
                // $checkIfExist =  philhealthModel::where('EE',$request->ee);
                //     if($checkIfExist->count()>0){
                //         return response()->json(['status'=>200, 'msg'=>'EE exist!']);
                //     }
                $insert = holidayLoggerModel::create($values);
            }else{
                $insert = holidayLoggerModel::where('id',$request->updateID)->update($values);
            }
            if($insert){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Holiday Logger Created.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Holiday Logger Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);

            }
        }


    }

   public function getall(Request $request)
{
    // Get the current year
    $currentYear = date('Y');

    // Filter by the 'date' column (or whichever column stores your holiday date)
    $getAll = holidayLoggerModel::whereYear('date', $currentYear)
                ->orderBy('date', 'asc') // Usually better to sort by the actual holiday date
                ->get();

    if ($getAll) {
        return response()->json([
            'status' => 200, 
            'data' => $getAll,
            'year' => $currentYear // Optional: helpful for the frontend to know the year
        ]);
    }

    return response()->json(['status' => 404, 'message' => 'No data found']);
}

    public function edit(Request $request){
        $getEOVal = holidayLoggerModel::where('id',$request->updateID)->get();
        if($getEOVal){
            return response()->json(['status'=>200, 'data'=>$getEOVal]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\archive;
use App\Models\User;

use Illuminate\Http\Request;

// use DB;
use Validator;

class archiveCtrl extends Controller
{
    //search
    public function search(Request $request){
        $archiveID=$request->archiveID;
        $getA = archive::join('positions as a', 'archive.pos','=', 'a.id')
        ->select('archive.*','archive.id as ids','a.id','a.pos_desc')
        ->where('lname','like','%'. $request->search .'%')
        ->latest('archive.created_at')
        ->get();

        if($getA->count()>0){
            return response()->json(['status'=>200, 'data'=>$getA]);
        }else{
            return response()->json(['status'=>199, ]);

        }
    }

     //edit
     public function edit(Request $request){
        $archiveID=$request->archiveID;
        // dd($archiveID);
        $getB = archive::where('id',$archiveID)->get();
        return response()->json(['status'=>200, 'data'=>$getB]);
    }

    //create_update
    public function create_update(Request $request){
        // dd(session()->get('LoggedUserComp'));
        $value =[
            'fname'=>$request->fname,
            'lname'=>$request->lname,
            'pos'=>$request->position,
            'empDatesFrom'=>$request->datefrom,
            'empDatesTo'=>$request->dateto,
            'empStatus'=>$request->status,
            'clearance'=>$request->clearance,
            'reasonForLeaving'=>$request->reason,
            'derogatoryRecords'=>$request->derogatory,
            'salary'=>$request->salary,
            'pendingResign'=>$request->resignation,
            'addRemarks'=>$request->remarks,
            'verifiedBy'=>$request->verify,
         ];
         $validator = Validator::make($request->all(),[
            'fname'=>'required',
            'lname'=>'required',
            'selectedPos'=>'required',
            'datefrom'=>'required',
            'dateto'=>'required',
            'status'=>'required',
            'clearance'=>'required',
            'reason'=>'required',
            'salary'=>'required',
            'resignation'=>'required',
            'verify'=>'required'
         ]);
        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
                if($request->formAction==1){
                    $query = archive::create($value);
                }else{
                    $query = archive::where('id',$request->archiveID)->update($value);
                }
            if($query){
                if($request->formAction==1){
                    return response()->json(['status'=>200, 'msg'=>'Employee Registered.']);
                }else{
                    return response()->json(['status'=>200, 'msg'=>'Emplyoyee Updated.']);
                }
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error']);
            }
        }
    }


}

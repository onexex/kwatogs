<?php

namespace App\Http\Controllers;

use DB;
use Validator;
use Carbon\Carbon;
use App\Models\earlyout;
use Illuminate\Http\Request;
use App\Models\homeAttendance;

class earlyoutCtrl extends Controller
{
    public function submit(Request $request)
    {
        $current_date_time = Carbon::now()->toDateTimeString();
        $now = Carbon::now();
        $dateShort = $now->format('Y-m-d');
        $wSched1=0;
        $wSched2=0;
        
       
        
        try{
       
        $validator = Validator::make($request->all(),[
            'txtPurposeRem'=>'required',
        ],[
            'txtPurposeRem.required'=>"Please specify your purpose.",]
        );

        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
            //get validation 
            //get lilo data
            $getLilo = homeAttendance::where('empID',session()->get('LoggedUserEmpID'))
            ->where('wsFrom','=', $dateShort )
            ->latest('created_at')
            ->first();

            if($getLilo){
                //validate if already logout
                if($getLilo->timeOut != NULL){
                     return response()->json(['status'=>202, 'msg'=>'Your login period for today has already closed; action denied!']);
                }else{
                    //validate late
                    if($getLilo->timeOut == NULL){
                        $wSched1= $getLilo->wsched . ' ' . $getLilo->wsFrom ;
                        // $wSched2=0;
                        if(strtotime($getLilo->timeIn) > strtotime($wSched1)){
                            return response()->json(['status'=>202, 'msg'=>'Todays access has been prohibited due to your tardiness.']);
                        }
                    }
                }
                
            }else{
                return response()->json(['status'=>202, 'msg'=>'No login today; action denied!']);
            }
            $values = [
                'purpose'=>$request->txtPurposeRem,
                'empID'=>session()->get('LoggedUserEmpID'),
                'isID'=>session()->get('LoggedISID'),
                'liloID'=> $getLilo->id,
                'isRem'=>'',
                'isUpdate'=>$current_date_time, 
                'hrUpdate'=>$current_date_time,
                'status'=>'0',
            ];
            #validate 
            $insert = earlyout::create($values);
            
            if($insert){
                return response()->json(['status'=>200, 'msg'=>'Applied for earlyout. Please hold off on logging out until your imeddiate has approve your filing.']);
            }else{
                return response()->json(['status'=>202, 'msg'=>'Error saving']);
            }
        }


          
        }catch (\Exception $e) {
          
            return response()->json(['status'=>202, 'msg'=>$e->getMessage(),]);
        }

    }

   
}

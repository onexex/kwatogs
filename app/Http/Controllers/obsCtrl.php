<?php

namespace App\Http\Controllers;

use DB;
use Validator;
use Carbon\Carbon;
use App\Models\obs;
use App\Models\obshbd;
use App\Models\empDetail;
use Illuminate\Http\Request;
use App\Models\effectivityDate;



class obsCtrl extends Controller
{
    //
    public function get_details(Request $request){
        $now = Carbon::now();
        $dateToday= $now->format('Y-m-d');

        $getA=DB::table('emp_details as a')
        ->join('users as b','a.empID', '=', 'b.empID')
        ->join('departments as c', 'a.empDepID', '=', 'c.id')
        ->join('companies as d', 'a.empCompID', '=', 'd.comp_id')
        ->join('positions as e', 'a.empPos', '=', 'e.id')

        ->select('a.*', 'a.empISID','b.lname','b.fname','c.dep_name','d.comp_name','e.pos_desc')
        ->where('a.empID', session()->get('LoggedUserEmpID'))
        ->get();
        return response()->json(['status'=>200, 'data'=>$getA]);
    }

    //Create
    public function create_obt(Request $request)
    {
        try {
            $validator = Validator::make($request->all(),[
                'personnel'=>'required',
                'company'=>'required',
                'department'=>'required',
                'designation'=>'required',
                'dateFil'=>'required',
                'dateFrom'=>'required',
                'dateTo'=>'required',
                'itineraryF'=>'required',
                'itineraryT'=>'required',
                'departure'=>'required',
                'return'=>'required',
                'purposeT'=>'required',
            ],[
                'personnel.required'=>"Personnel name is required!",
                'company.required'=>"Company name is required!",
                'department.required'=>"Department is required!",
                'designation.required'=>"Designation is required!",
                'dateFil.required'=>"Date Filling is required!",
                'dateFrom.required'=>"Date is required!",
                'dateTo.required'=>"Date is required!",
                'itineraryF.required'=>"This is required!",
                'itineraryT.required'=>"This field is required!",
                'departure.required'=>"Time is required!",
                'return.required'=>"Time is required!",
                'purposeT.required'=>"Purpose is required!",
            ]);

            if(!$validator->passes()){
                return response()->json(['status'=>201, 'msg'=>"Please check the required field!", 'error'=>$validator->errors()->toArray()]);
            }

            // $now = Carbon::now();
            $DateTimeNow = Carbon::now()->toDateTimeString();
            $day_desc=date("l");
            $dateFrom=$request->dateFrom;
            $dateTo=$request->dateTo;


            $getEmpSched=DB::table('schedules as a')
            -> join('effectivity_dates as b', 'a.edID','=','b.id')
            -> join('worktimes as c', 'a.wtID','=','c.id')
            -> where('a.empID',session()->get('LoggedUserEmpID'))
            -> where('a.days', $day_desc)
            -> where('a.wtID', '!=','0')
            ->whereRaw('? between dFrom and dTo', [$dateFrom])
            ->orWhereRaw('? between dFrom and dTo', [$dateTo])
            ->take(1)
            ->get();

            if($dateFrom > $dateTo ){
                return response()->json(['status'=>201,'msg'=>"Invalid OB date!"]);
            }

            $valDuplicateReg=effectivityDate::where('empID',session()->get('LoggedUserEmpID'))
            ->get();

                if($valDuplicateReg->count()>0){
                    $valDuplicate=DB::table('effectivity_dates')
                    ->whereRaw('? between dFrom and dTo', [$dateFrom])
                    ->orWhereRaw('? between dFrom and dTo', [$dateTo])
                    ->get();

                    $valID=0;
                    $iffound=0;

                    // validate the employee ID
                    foreach( $valDuplicate as $row){
                            $valID=$row->empID;

                            if($valID==session()->get('LoggedUserEmpID')){
                                $iffound=1;
                            }
                        }
//////CONTINUATION///////////
                    // manually validate empid
                    if($valDuplicate->count()<0){
                        if($iffound==1){
                            return response()->json(['status'=>201,'msg'=>"Schedule Date Already Exist!1"]);
                        }else{
                            //do nothing
                        }
                    }else{
                        //validate the startdate and enddate between the dFrom
                        $valDuplicate2=effectivityDate::whereBetween('dFrom', [$dateFrom, $dateTo])
                        ->orWhereBetween('dTo', [$dateFrom, $dateTo])
                        ->where('empID',session()->get('LoggedUserEmpID'))

                        ->get();
                            if($valDuplicate2->count()>0){
                                return response()->json(['status'=>201,'msg'=>"Schedule Date Already Exist!2"]);
                            }
                        }
                }
                if($getEmpSched->count()>0){
                    $now = Carbon::now();
                    $dateToday= $now->format('Y-m-d');

                    $TimeDep=$request->departure;
                    $TimeRet=$request->return;
                    $duration= ((strtotime($TimeRet) - strtotime($TimeDep))/60)/60;

                    //LOGIif may sched

                        // $msgDesc="You have successfully Login to CENAR!";
                        $value =[
                            'empID' =>session()->get('LoggedUserEmpID'),
                            'empISID' =>session()->get('LoggedISID'),
                            'obFD' => $dateToday,
                            'obDateFrom' => $request->dateFrom,
                            'obDateTo' => $request->dateTo,
                            'obIFrom' => $request->itineraryF,
                            'obITo' => $request->itineraryT,
                            'obTFrom' => $request->departure,
                            'obTTo' => $request->return,
                            'obPurpose' => $request->purposeT,

                            'obCAAmt' => $request->amount,
                            'obCAPurpose' => $request->purposeCA,

                            'obDuration' => $duration,
                            'obStatus' => "1",
                            // 'obISReason' => "",
                            // 'obHRReason' => "",
                            // 'obType' => "",
                        ];
                        $insert = obs::create($value);

                        if($insert){
                            $IDinserted= $insert->obID;
                            $value2 =[
                                'obID' => $IDinserted,
                                'empID' =>session()->get('LoggedUserEmpID'),
                                'empISID' =>session()->get('LoggedISID'),
                                'obFD' => $dateToday,
                                'obDateFrom' => $request->dateFrom,
                                'obDateTo' => $request->dateTo,
                                'obIFrom' => $request->itineraryF,
                                'obITo' => $request->itineraryT,
                                'obTFrom' => $request->departure,
                                'obTTo' => $request->return,
                                'obPurpose' => $request->purposeT,

                                'obCAAmt' => $request->amount,
                                'obCAPurpose' => $request->purposeCA,

                                'obDuration' => $duration,
                                'obStatus' => "1",
                            ];
                            $insert2= obshbd::create($value2);

                            return response()->json(['status'=>200,'msg'=>"Successfully submitted your OB!"]);
                        }else{
                            return response()->json(['status'=>201 ,'msg'=>"Error! Please try again later!"]);
                        }

                }else{
                    return response()->json(['status'=>201,'msg'=>"Oops, we couldn't find your Schedule!"]);
                }
            }catch(Exception $e) {
            // echo 'Message: ' .$e->getMessage();
            return response()->json(['status'=>200 ,'data'=>$e->getMessage()]);
        }
        // $now = Carbon::now();
        // $dateToday= $now->format('Y-m-d');

        // $TimeDep=$request->departure;
        // $TimeRet=$request->return;
        // $duration= ((strtotime($TimeRet) - strtotime($TimeDep))/60)/60;


        // $validator = Validator::make($request->all(),[
        //     'personnel'=>'required',
        //     'company'=>'required',
        //     'department'=>'required',
        //     'designation'=>'required',
        //     'dateFil'=>'required',
        //     'dateFrom'=>'required',
        //     'dateTo'=>'required',
        //     'itineraryF'=>'required',
        //     'itineraryT'=>'required',
        //     'departure'=>'required',
        //     'return'=>'required',
        //     'purposeT'=>'required',
        // ],[
        //     'personnel.required'=>"Personnel name is required!",
        //     'company.required'=>"Company name is required!",
        //     'department.required'=>"Department is required!",
        //     'designation.required'=>"Designation is required!",
        //     'dateFil.required'=>"Date Filling is required!",
        //     'dateFrom.required'=>"Date is required!",
        //     'dateTo.required'=>"Date is required!",
        //     'itineraryF.required'=>"This is required!",
        //     'itineraryT.required'=>"This field is required!",
        //     'departure.required'=>"Time is required!",
        //     'return.required'=>"Time is required!",
        //     'purposeT.required'=>"Purpose is required!",
        // ]);

        // if(!$validator->passes()){
        //     return response()->json(['status'=>201, 'msg'=>"Please check the required field!", 'error'=>$validator->errors()->toArray()]);
        // }
        // else{

        // $dateFrom=$request->dateFrom;
        // $dateTo=$request->dateTo;

        // if($dateFrom > $dateTo ){
        //     return response()->json(['status'=>201,'msg'=>"Invalid date!"]);
        // }else{

        //     $value =[
        //         'empID' =>session()->get('LoggedUserEmpID'),
        //         'empISID' =>session()->get('LoggedISID'),
        //         'obFD' => $dateToday,
        //         'obDateFrom' => $request->dateFrom,
        //         'obDateTo' => $request->dateTo,
        //         'obIFrom' => $request->itineraryF,
        //         'obITo' => $request->itineraryT,
        //         'obTFrom' => $request->departure,
        //         'obTTo' => $request->return,
        //         'obPurpose' => $request->purposeT,

        //         'obCAAmt' => $request->amount,
        //         'obCAPurpose' => $request->purposeCA,

        //         'obDuration' => $duration,
        //         'obStatus' => "1",
        //         // 'obISReason' => "",
        //         // 'obHRReason' => "",
        //         // 'obType' => "",
        //     ];

        //     // $insert = obs::create($value);

        //     // $IDinserted= $insert->obID;
        //     // $value2 =[
        //     //     'obID' => $IDinserted,
        //     //     'empID' =>session()->get('LoggedUserEmpID'),
        //     //     'empISID' =>session()->get('LoggedISID'),
        //     //     'obFD' => $dateToday,
        //     //     'obDateFrom' => $request->dateFrom,
        //     //     'obDateTo' => $request->dateTo,
        //     //     'obIFrom' => $request->itineraryF,
        //     //     'obITo' => $request->itineraryT,
        //     //     'obTFrom' => $request->departure,
        //     //     'obTTo' => $request->return,
        //     //     'obPurpose' => $request->purposeT,

        //     //     'obCAAmt' => $request->amount,
        //     //     'obCAPurpose' => $request->purposeCA,

        //     //     'obDuration' => $duration,
        //     //     'obStatus' => "1",
        //     // ];
        //     // $insertM = obshbd::create($value2);

        //     // $valSchedule=effectivityDate::where('empID',session()->get('LoggedUserEmpID'))
        //     // ->get();

        //     //     if($valSchedule->count()>0){
        //     //         $valSched=DB::table('effectivity_dates')
        //     //         ->whereRaw('? between dFrom and dTo', [$dateFrom])
        //     //         ->orWhereRaw('? between dFrom and dTo', [$dateTo])
        //     //         ->get();

        //     //         $valID=0;
        //     //         $iffound=0;

        //     //         //validate the employee ID
        //     //         foreach( $valSched as $row){
        //     //                 $valID=$row->empID;

        //     //                 if($valID==$request->session()->get('LoggedUserEmpID')){
        //     //                     $iffound=0;
        //     //                 }
        //     //                    //manually validate empid
        //     //             if($valDuplicate->count()>0){
        //     //                 if($iffound==1){

        //     //                     return response()->json(['status'=>200,'msg'=>"Successfully submitted your OB!"]);
        //     //                 }
        //     //             }else{
        //     //                 return response()->json(['stat'=>199,'msg'=>"You don't have schedule"]);
        //     //             }
        //     //         }
        //     //     }







        //         // $insert = obs::create($value);
        //     // $IDinserted= $insert->obID;
        //     if($insert){
        //         return response()->json(['status'=>200,'msg'=>"Successfully submitted your OB!"]);
        //     }

        //     }
        // }
    }

    //getall
    public function getall(Request $request){
        $getA = obs::latest('created_at')->get();
            if($getA){
                return response()->json(['status'=>200, 'data'=>$getA]);
            }
    }



}

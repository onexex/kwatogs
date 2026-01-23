<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\User;

use App\Models\worktime;
use App\Models\schedules;
use Illuminate\Http\Request;
use App\Models\effectivityDate;
use DB;


class empSchedulerCtrl extends Controller
{
    //Create Employee Sched
    public function create_update(Request $request)
    {
        //validator script
        $validator = Validator::make($request->all(),[
            'employee' =>'required',
            'dateFrom' =>'required',
            'dateTo' =>'required',
            'monday' =>'required',
            'tuesday' =>'required',
            'wednesday' =>'required',
            'thursday' =>'required',
            'friday' =>'required',
            'saturday' =>'required',
            'sunday' =>'required',
        ]);

        if(!$validator->passes()){
            return response()->json(['stat'=>201, 'error'=>$validator->errors()->toArray()]);
        }
        else{
            //DATE
            // ($resultScheds->wt_timefrom)
            $startdate =$request->dateFrom;
            $enddate =$request->dateTo;

            $value =[
                'dFrom' => $request->dateFrom,
                'dTo' => $request->dateTo,
                'empID' => $request->employee,

            ];

            $valDuplicateReg=effectivityDate::where('empID',$request->employee)
            ->get();

                // validate the date dateFrom between the dFrom and dTo
                if($valDuplicateReg->count()>0){
                    $valDuplicate=DB::table('effectivity_dates')
                    ->whereRaw('? between dFrom and dTo', [$startdate])
                    ->orWhereRaw('? between dFrom and dTo', [$enddate])
                    ->get();

                    $valID=0;
                    $iffound=0;

                    //validate the employee ID
                    foreach( $valDuplicate as $row){
                            $valID=$row->empID;

                            if($valID==$request->employee){
                                $iffound=1;
                            }
                        }

                       //manually validate empid
                    if($valDuplicate->count()>0){
                        if($iffound==1){
                             return response()->json(['stat'=>199,'msg'=>"Schedule Date Already Exist!"]);
                        }else{
                            //do nothing
                        }
                    }else{
                        //validate the startdate and enddate between the dFrom
                        $valDuplicate2=effectivityDate::whereBetween('dFrom', [$startdate, $enddate])
                        ->orWhereBetween('dTo', [$startdate, $enddate])
                        ->where('empID',[$request->employee])

                        ->get();
                            if($valDuplicate2->count()>0){
                                return response()->json(['stat'=>199,'msg'=>"Schedule Date Already Exist!"]);
                            }
                        }
                }

            $insert = effectivityDate::create($value);
            $IDinserted= $insert->id;

            //MONDAY
            $valueM =[
                'empID' => $request->employee,
                'days'=> "Monday",
                'wtID' => $request->monday,
                'edID' => $IDinserted
            ];
            $insertM = schedules::create($valueM);

            //TUESDAY
            $valueTUE =[
                'empID' => $request->employee,
                'days'=> "Tuesday",
                'wtID' => $request->tuesday,
                'edID' => $IDinserted
            ];
            $insertTUE = schedules::create($valueTUE);

            //WEDNESDAY
            $valueWED =[
                'empID' => $request->employee,
                'days'=> "Wednesday",
                'wtID' => $request->wednesday,
                'edID' => $IDinserted
            ];
            $insertWED = schedules::create($valueWED);

            //THURSDAY
            $valueTHU =[
                'empID' => $request->employee,
                'days'=> "Thursday",
                'wtID' => $request->thursday,
                'edID' => $IDinserted
            ];
            $insertTHU = schedules::create($valueTHU);

            //FRIDAY
            $valueFRI =[
                'empID' => $request->employee,
                'days'=> "Friday",
                'wtID' => $request->friday,
                'edID' => $IDinserted
            ];
            $insertFRI = schedules::create($valueFRI);


            //SATURDAY
            $valueSAT =[
                'empID' => $request->employee,
                'days'=> "Saturday",
                'wtID' => $request->saturday,
                'edID' => $IDinserted
            ];
            $insertSAT = schedules::create($valueSAT);

            //SUNDAY
            $valueSUN =[
                'empID' => $request->employee,
                'days'=> "Sunday",
                'wtID' => $request->sunday,
                'edID' => $IDinserted
            ];
            $insertSUN = schedules::create($valueSUN);

            if($insert){
                return response()->json(['stat'=>200,'msg'=>"Successfully saved!"]);
            }else{
                return response()->json(['stat'=>202]);
            }
        }
    }

    public function getall(Request $request){

        $getSchedule = effectivityDate::join('schedules as s', 'effectivity_dates.id','=', 's.edID')
        ->join('users', 's.empID','=', 'users.empID')
        ->select('s.empID','effectivity_dates.dFrom','effectivity_dates.dTo','effectivity_dates.id','users.lname','users.fname')
        ->groupBy('s.empID','effectivity_dates.dFrom','effectivity_dates.dTo','effectivity_dates.id','users.lname','users.fname')
        ->latest('effectivity_dates.id')

        ->get();
        if($getSchedule){
            return response()->json(['status'=>200, 'data'=>$getSchedule]);
        }
    }

    public function getall_time(Request $request){
        $getSchedTime=schedules::where('edID',$request->empSID)
        ->join('worktimes as wt','schedules.wtID','=','wt.id')
        ->select('wt.*','schedules.*','schedules.id as ids')
        ->get();

        //  $getSchedTime=$getSchedTime->map(function ($item) {
        //     return [
        //         'ids'    => $item->ids,
        //         'days'    => $item->days,
        //         'wt_timefrom'  => \Carbon\Carbon::parse($item->wt_timefrom)->format('g:i A'),
        //         'wt_timeto' => \Carbon\Carbon::parse($item->wt_timeto)->format('g:i A'),
        //     ];
        // });

        return response()->json(['stat'=>200,'dataT'=>$getSchedTime]);
    }

    //edit_time
    public function edit_time(Request $request){
        $empSID=$request->empSID;
        $getA = schedules::where('id', $empSID)->get();
        return response()->json(['status'=>200, 'data'=>$getA]);
    }

    //create_update
     public function update_time(Request $request)
     {
         //validator script
         $validatorU = Validator::make($request->all(),[
            'wtime' =>'required',
         ]);

         if(!$validatorU->passes()){
             return response()->json(['stat'=>201, 'error'=>$validatorU->errors()->toArray()]);
         }
         else{
             $value =[
                 'wtID' => $request->wtime,
             ];
             $query=schedules::where('id',$request->empSID)->update($value);
             // $IDinserted= $query->id;
             if($query){
                 return response()->json(['stat'=>200]);
             }else{
                 return response()->json(['stat'=>202]);
             }
         }
    }

    //edit date
    public function edit_date(Request $request){
        $empSID=$request->empSID;
        $getA = effectivityDate::where('id', $empSID)->get();
        return response()->json(['status'=>200, 'data'=>$getA]);
    }

    //updatedate
    public function update_date(Request $request){
        $validatorU = Validator::make($request->all(),[
            'dateFromU'=>'required',
            'dateToU'=>'required',
            ]);

         //validate yung return result ng validator
        if(!$validatorU->passes()){
            return response()->json(['stat'=>201, 'error'=>$validatorU->errors()->toArray()]);
        }
        else{
            $valueU =[
                'dFrom' => $request->dateFromU,
                'dTo' => $request->dateToU,
            ];
            $myScedule=[];
            $exitOnTrue=0;
            $iffound=0;

                $valDuplicateReg=effectivityDate::join('schedules', 'effectivity_dates.id', '=', 'schedules.edID')
                ->where('effectivity_dates.id',$request->id)
                ->first();

                // validate the date dateFrom between the dFrom and dTo
                    $valDuplicateU=DB::table('effectivity_dates')
                    ->whereRaw('? between dFrom and dTo', [$request->dateFromU])
                    ->orwhereRaw('? between dFrom and dTo', [$request->dateToU])
                    ->get();

                    //loop the emp to get the selected emp
                    foreach($valDuplicateU as $row){

                        if($row->empID== $valDuplicateReg->empID){
                            $myScedule[]=$row;
                            // $exitOnTrue=1;
                        }
                    }

                    foreach($myScedule as $rows){
                        if($rows->id==$request->id){
                            $iffound=1;
                        }
                    }
                    // return response()->json(['stat'=>199,'msg'=>$myScedule]);
                    if($iffound>0){
                        if(count($myScedule)>1){
                            return response()->json(['stat'=>199,'msg'=>"Schedule Date Already Exist!" ]);
                        }else{
                            $query=effectivityDate::where('id',$request->id)->update($valueU);
                            if($query){
                                return response()->json(['stat'=>200]);
                            }
                        }

                    }else{
                        if(count($myScedule)>0){
                             return response()->json(['stat'=>199,'msg'=>"Schedule Date Already Exist!" ]);

                        }else{
                            $query=effectivityDate::where('id',$request->id)->update($valueU);
                            if($query){
                                return response()->json(['stat'=>200]);
                            }
                        }
                    }

        }
    }

    public function search(Request $request){

        $getSchedule = effectivityDate::join('schedules as s', 'effectivity_dates.id','=', 's.edID')
        ->join('users', 's.empID','=', 'users.empID')
        ->select('s.empID','effectivity_dates.dFrom','effectivity_dates.dTo','effectivity_dates.id','users.lname','users.fname')
        ->groupBy('s.empID','effectivity_dates.dFrom','effectivity_dates.dTo','effectivity_dates.id','users.lname','users.fname')
        ->where('users.lname','like','%'. $request->searchEmp .'%')
        ->orWhere('users.fname', 'like', '%' . $request->searchEmp . '%')
        ->get();
        if($getSchedule){
            return response()->json(['status'=>200, 'data'=>$getSchedule]);
        }
    }


}

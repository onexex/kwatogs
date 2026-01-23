<?php

namespace App\Http\Controllers;
use DB;
use Auth;

use Validator;
use Carbon\Carbon;
use App\Models\homeDar;
use App\Models\schedules;
use Illuminate\Http\Request;
use App\Models\homeAttendance;
use App\Models\effectivityDate;
use App\Models\liloValidations;


class homeAttendanceCtrl extends Controller
{
    //button login or logout
    public function log_attendance(Request $request){
        try {
            $now = Carbon::now();
            $dateLogs = $now->format('Y-m-d');
            $desc=0;
            $btnval=0;

            $getDate = homeAttendance::where('wsFrom', $dateLogs)
            ->where('empID',session()->get('LoggedUserEmpID'))
            ->latest('created_at')
            ->first();

            if($getDate){
                if($getDate->timeOut== 0){
                    $desc="LOGOUT TO CENAR";
                    $btnval=1;
                }else{
                    $desc="LOGIN TO CENAR";
                    $btnval=2;
                }
            }else{
                $desc="LOGIN TO CENAR";
                $btnval=2;
            }

            return response()->json(['status'=>200, 'data'=>$desc, 'btnval'=>$btnval]);

        }catch(Exception $e) {
        //// echo 'Message: ' .$e->getMessage();
        return response()->json(['status'=>201 ,'data'=>$e->getMessage()]);
        }

    }

    public function getall_attendance(Request $request){

        $today = date("Y-m-d");
        $Effectivity = new Carbon($today);

        $DFrom=$request->DFrom;
        $DTo=$request->DTo;

        $uid = Auth::id();
        $queryLogs=0;
        $holiday=0;
        $leave=0;
        $outputStatus='';


        // $day=$DTo->date('%W');
        $statusDesc=0;
        $statusNo=0;
        // return response()->json(['status'=>200, 'msg'=>$DTo]);

        while($DFrom <= $DTo) {
            $day_desc=date("l",strtotime($DFrom));
            $getSched=DB::table('schedules as a')
            -> join('effectivity_dates as b', 'a.edID','=','b.id')
            -> join('worktimes as c', 'a.wtID','=','c.id')
            -> where('a.empID',session()->get('LoggedUserEmpID'))
            -> where('a.days', $day_desc)
            -> where('a.wtID', '!=','0')
            -> whereRaw('? between dFrom and dTo', [$DFrom])
            ->take(1)
            ->get();

                $holiday = DB::table('holiday_logger')
                ->select([DB::raw("(SELECT 'HD') as st ,id as a,date as b,description as c,description as d,description as e,created_at as f")])
                // ->where('id', '=', $uid)
                // ->latest()
                ->where('date','=', $DFrom);

                $holidayValidate = DB::table('holiday_logger')
                ->where('date','=', $DFrom)
                ->get();


                $queryLogs=DB::table('home_attendance')
                ->select([DB::raw("(SELECT 'Cenar') as st ,durationTime as a,wsched as b,wsched2 as c,timeIn as d,timeOut as e,created_at as f")])
                ->where('wsFrom','=', $DFrom)
                ->where('empID',session()->get('LoggedUserEmpID'))

                ->unionAll($holiday)
                // ->latest()
                // ->latest('created_at')
                ->orderBy('f', 'asc')
                ->get();

                $rowCountLogs=$queryLogs->count();
                $rowCountSched=$getSched->count();
                // return response()->json(['status'=>200, 'data'=>$rowCountLogs]);
                //count kung may
                if($rowCountLogs <= 0){
                    if($rowCountSched <= 0){
                        $statusDesc= "Rest Day";
                        $statusNo=0;
                    }else{
                        if($holidayValidate->count() > 0){
                            $statusDesc=$holidayValidate->descriptcion;
                            $statusNo=1;
                        }else{
                            $statusDesc="No Attendance";
                            $statusNo=2;
                        }
                    }
                    //RESTDAY
                    if($statusNo<=0){
                        $outputStatus.='
                            <tr class="No A">
                                <td>' . date("F j, Y", strtotime($DFrom)) .'</td>
                                <td>' . date("l", strtotime($DFrom)) .'</td>
                                <td>Rest Day</td>
                                <td>-</td>
                                <td>-</td>
                                <td>'. $statusDesc .'</td>
                                <td>0.00</td>
                            </tr>
                        ';
                    //HOLIDAY
                    }if($statusNo==1){
                        $outputStatus.='
                            <tr class="No A">
                                <td>' . date("F j, Y", strtotime($DFrom)) .'</td>
                                <td>' . date("l", strtotime($DFrom)) .'</td>
                                <td>Holiday</td>
                                <td>-</td>
                                <td>-</td>
                                <td>' . $row->e .'</td>
                                <td>0.00</td>
                            </tr>
                        ';
                    //NO ATTENDANCE
                    }if($statusNo==2){
                        $outputStatus.='
                            <tr class="No A">
                                <td>' . date("F j, Y", strtotime($DFrom)) .'</td>
                                <td>' . date("l", strtotime($DFrom)) .'</td>
                                <td>No Attendace</td>
                                <td>-</td>
                                <td>-</td>
                                <td>'. $statusDesc .'</td>
                                <td>0.00</td>
                            </tr>
                        ';
                    }
                    //table
                }else{
                    foreach ( $queryLogs as $row ){
                        //MAY ATTENDANCE
                        if($row->st=='Cenar'){

                            $timeoutDesc=0;
                            if($row->e<=0){
                                $timeoutDesc="-";
                            }else{
                                $timeoutDesc= date("g:i:s A", strtotime($row->e));
                            }
                            $outputStatus.='
                            <tr class="">
                                <td>' . date("F j, Y", strtotime($DFrom)) .'</td>
                                <td>' . date("l", strtotime($DFrom)) .'</td>
                                <td>' . date("g:i A", strtotime($row->b)) . ' - ' . date("g:i A", strtotime($row->c)) .'</td>
                                <td>' . date("g:i:s A", strtotime($row->d)) .'</td>
                                <td>'. $timeoutDesc .'</td>

                                <td>On Site</td>
                                <td>' . number_format($row->a, 2, '.', ' ') .'</td>
                            </tr>
                            ';
                        }
                        //HOLIDAY
                        if($row->st=='HD'){

                            $outputStatus.='
                                <tr class="No A">
                                    <td>' . date("F j, Y", strtotime($DFrom)) .'</td>
                                    <td>' . date("l", strtotime($DFrom)) .'</td>
                                    <td>Holiday</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>' . $row->e .'</td>
                                    <td>0.00</td>
                                </tr>
                            ';
                        }
                    }
                }
            //plus days
            $DFrom=date('Y-m-d', strtotime($DFrom . ' + 1 days'));
        }

        return response()->json(['status'=>200, 'data'=>$outputStatus]);

    }

    //check kung may sched ka or wala
    public function create_attendance(Request $request){
        // dd(session()->get('LoggedUserComp'));
        try {
            // $now = Carbon::now();
            $DateTimeNow = Carbon::now()->toDateTimeString();

            $day_desc=date("l");
            $datefrom = date("Y-m-d");
            $today = date("Y-m-d");
            $dateto =  date("Y-m-d");
            $Effectivity = new Carbon($today);
            $dateNow = new Carbon($today);

            $sched=0;
            $nminlack=0;
            $schedStatus=0;
            $ManagearsPos=1;

            $getEmpSched=DB::table('schedules as a')
            -> join('effectivity_dates as b', 'a.edID','=','b.id')
            -> join('worktimes as c', 'a.wtID','=','c.id')
            -> where('a.empID',session()->get('LoggedUserEmpID'))
            -> where('a.days', $day_desc)
            -> where('a.wtID', '!=','0')
            -> whereRaw('? between dFrom and dTo', [$Effectivity])
            ->take(1)
            ->get();

                if($getEmpSched->count()){
                    //LOGIif may sched
                    $schedStatus=1;

                    foreach ($getEmpSched as $items) {
                        $tFrom=$items->wt_timefrom;
                        $tTo=$items->wt_timeto;
                        $tCross=$items->wt_timecross;
                    }
                        $todayDT = strtotime(date("Y-m-d h:i"));
                        $wfromtime = strtotime($today. ' ' .$tFrom);


                        if($todayDT > $wfromtime){
                            $nminlack = (time() - $wfromtime) / 60;

                        }else{
                            $nminlack=0;
                        }
                        //validate graceperiod
                        // if ($nminlack <= $gp){
                        //     $nminlack = 0;
                        // }

                    //validate if schedule crosses date
                    if($tCross==1){
                        $dateto = date('Y-m-d', strtotime($dateto ." + 1 days"));
                    }
                    $getDateS = homeAttendance::where('empID',session()->get('LoggedUserEmpID'))
                    ->latest('created_at')
                    ->first();

                    if($getDateS){
                        if($getDateS->timeOut== 0){

                            $empWDay=11;
                            $timeIn = $getDateS->timeIn;
                            $wsFrom = $getDateS->wsFrom . ' ' . $getDateS->wsched;
                            //concat .''.
                            $wsTo = $getDateS->wsTo . ' ' . $getDateS->wsched2;

                            $timeOut = $DateTimeNow;
                            $XtimeOut=  $timeOut;

                                //check kung sakto, sobra o kulang ba sa wsFrom
                            if($timeIn > $wsFrom){
                                    //check kung nagout ba ng sakto,sobra o kulang?
                                if($timeOut >= $wsTo ){
                                    //kung nagout ng sakto or sobra
                                    $duration= ((strtotime($wsTo) - strtotime($timeIn))/60)/60;
                                }
                                else{
                                    //nagout ng kulang False
                                    $duration= ((strtotime($timeOut) - strtotime($timeIn))/60)/60;
                                }
                            }else{
                                //hindi late
                                if(time() >= $wsTo){
                                    $duration= ((strtotime($wsTo) - strtotime($timeIn))/60)/60;
                                }
                                else{
                                    $duration= ((strtotime($timeOut) - strtotime($wsFrom))/60)/60;
                                }
                            }

                            if($duration > $empWDay){
                                $duration=11;
                            }

                            if(((( strtotime($timeOut) - strtotime($wsTo) )/60)/60)>=5) {
                                $duration= 0;
                                $XtimeOut=$timeIn;
                            }

                            If(session()->get('LoggedUserPos') == 1){

                                $getManagersPos = liloValidations::where('empCompID', session()->get('LoggedUserComp'))
                                ->first();


                                if($getManagersPos->count()){

                                    if($getManagersPos->managersOverride == 1){

                                        If($duration >= 7.5){
                                            $duration=11;
                                        }
                                    }
                                }
                            }
                            $valueU =[
                                'timeOut' => $timeOut,
                                'durationTime' => $duration,
                            ];
                            $queryU=homeAttendance::where('empID',session()->get('LoggedUserEmpID'))
                                ->where('id', $getDateS->id)
                                ->update($valueU);
                            if($queryU){
                                $logoutDar =[
                                    'empID' => $request->session()->get('LoggedUserEmpID'),
                                    'empActivity'=>"Logout to Cenar",
                                    'DarDateTime'=>$DateTimeNow,
                                ];
                                $insertDarLi= homeDar::create($logoutDar);

                                return response()->json(['stat'=>200,'msg'=>"You have Succesfully LOGOUT to CENAR!"]);
                            }
                        }else{
                            $msgDesc="You have successfully Login to CENAR!";
                            $value =[
                                'empID' => $request->session()->get('LoggedUserEmpID'),
                                'wsched'=> $tFrom,
                                'wsched2' => $tTo,
                                'wsFrom' => $datefrom,
                                'wsTo' => $dateto,
                                'timeIn' => $DateTimeNow,

                                'minsLack' => $nminlack,
                                'minsLack2' => '0',
                            ];
                            $insertM = homeAttendance::create($value);

                            if($insertM){
                                $loginDar =[
                                    'empID' => $request->session()->get('LoggedUserEmpID'),
                                    'empActivity'=>"Login to Cenar",
                                    'DarDateTime'=>$DateTimeNow,
                                ];
                                $insertDarLi= homeDar::create($loginDar);

                                return response()->json(['stat'=>200,'msg'=>"You have Succesfully LOGIN to CENAR"]);
                            }else{
                                return response()->json(['stat'=>201 ,'msg'=>"Error! Please try again later!"]);
                            }
                        }
                    }
                    else
                    {
                        // $msgDesc="You have successfully Login to CENAR!";
                        $value =[
                            'empID' => $request->session()->get('LoggedUserEmpID'),
                            'wsched'=> $tFrom,
                            'wsched2' => $tTo,
                            'wsFrom' => $datefrom,
                            'wsTo' => $dateto,
                            'timeIn' => $DateTimeNow,

                            'minsLack' => $nminlack,
                            'minsLack2' => '0',
                            // 'dateTimeInput' => time(),
                            // 'durationTime' => $dateNow,
                        ];
                        $insertM = homeAttendance::create($value);

                        if($insertM){
                            $loginDar =[
                                'empID' => $request->session()->get('LoggedUserEmpID'),
                                'empActivity'=>"Login to Cenar",
                                'DarDateTime'=>$DateTimeNow,
                            ];
                            $insertDarLi= homeDar::create($loginDar);

                            return response()->json(['stat'=>200,'msg'=>"You have Succesfully LOGIN to CENAR"]);
                        }else{
                            return response()->json(['stat'=>201 ,'msg'=>"Error! Please try again later!"]);
                        }
                    }
                }else{
                    return response()->json(['stat'=>201,'msg'=>"Oops, we couldn't find your Schedule for Today!"]);
                }
            }catch(Exception $e) {
            // echo 'Message: ' .$e->getMessage();
            return response()->json(['status'=>200 ,'data'=>$e->getMessage()]);
        }
    }

    //filterAttendance
    public function filter_attendance (Request $request){
        $start = new Carbon($request->DFrom);
        $end = new Carbon($request->DTo.' + 1 days');

        $today = date("Y-m-d");
        $Effectivity = new Carbon($today);

        $DFrom=$request->DFrom;
        $DTo=$request->DTo;

        $uid = Auth::id();
        $queryLogs=0;
        $holiday=0;
        $leave=0;
        $outputStatus='';


        // $day=$DTo->date('%W');
        $statusDesc=0;
        $statusNo=0;
        // return response()->json(['status'=>200, 'msg'=>$DTo]);

        while($DFrom <= $DTo) {
            $day_desc=date("l",strtotime($DFrom));
            $getSched=DB::table('schedules as a')
            -> join('effectivity_dates as b', 'a.edID','=','b.id')
            -> join('worktimes as c', 'a.wtID','=','c.id')
            -> where('a.empID',session()->get('LoggedUserEmpID'))
            -> where('a.days', $day_desc)
            -> where('a.wtID', '!=','0')
            -> whereRaw('? between dFrom and dTo', [$DFrom])
            // ->whereBetween ('created_at', [$start, $end])

            ->take(1)
            ->get();

                $holiday = DB::table('holiday_logger')
                ->select([DB::raw("(SELECT 'HD') as st ,id as a,date as b,description as c,description as d,description as e")])
                // ->where('id', '=', $uid)
                ->where('date','=', $DFrom)
                ->whereBetween ('holiday_logger.created_at', [$start, $end]);


                $holidayValidate = DB::table('holiday_logger')
                ->where('date','=', $DFrom)
                ->whereBetween ('holiday_logger.created_at', [$start, $end])

                ->get();


                $queryLogs=DB::table('home_attendance')
                ->select([DB::raw("(SELECT 'Cenar') as st ,durationTime as a,wsched as b,wsched2 as c,timeIn as d,timeOut as e")])
                ->where('wsFrom','=', $DFrom)
                ->where('empID',session()->get('LoggedUserEmpID'))
                ->whereBetween ('home_attendance.created_at', [$start, $end])
                ->unionAll($holiday)
                ->get();

                $rowCountLogs=$queryLogs->count();
                $rowCountSched=$getSched->count();
                // return response()->json(['status'=>200, 'data'=>$rowCountLogs]);
                //count kung may
                if($rowCountLogs <= 0){
                    if($rowCountSched <= 0){
                        $statusDesc= "Rest Day";
                        $statusNo=0;
                    }else{
                        if($holidayValidate->count() > 0){
                            $statusDesc=$holidayValidate->descriptcion;
                            $statusNo=1;
                        }else{
                            $statusDesc="No Attendance";
                            $statusNo=2;
                        }
                    }
                    //RESTDAY
                    if($statusNo<=0){
                        $outputStatus.='
                            <tr class="No A">
                                <td>' . date("F j, Y", strtotime($DFrom)) .'</td>
                                <td>' . date("l", strtotime($DFrom)) .'</td>
                                <td>Rest Day</td>
                                <td>-</td>
                                <td>-</td>
                                <td>'. $statusDesc .'</td>
                                <td>0.00</td>
                            </tr>
                        ';
                    //HOLIDAY
                    }if($statusNo==1){
                        $outputStatus.='
                            <tr class="No A">
                                <td>' . date("F j, Y", strtotime($DFrom)) .'</td>
                                <td>' . date("l", strtotime($DFrom)) .'</td>
                                <td>Holiday</td>
                                <td>-</td>
                                <td>-</td>
                                <td>' . $row->e .'</td>
                                <td>0.00</td>
                            </tr>
                        ';
                    //NO ATTENDANCE
                    }if($statusNo==2){
                        $outputStatus.='
                            <tr class="No A">
                                <td>' . date("F j, Y", strtotime($DFrom)) .'</td>
                                <td>' . date("l", strtotime($DFrom)) .'</td>
                                <td>No Attendance</td>
                                <td>-</td>
                                <td>-</td>
                                <td>'. $statusDesc .'</td>
                                <td>0.00</td>
                            </tr>
                        ';
                    }
                    //table

                }else{
                    foreach ( $queryLogs as $row ){
                        //MAY ATTENDANCE
                        if($row->st=='Cenar'){

                            $outputStatus.='
                            <tr class="">
                                <td>' . date("F j, Y", strtotime($DFrom)) .'</td>
                                <td>' . date("l", strtotime($DFrom)) .'</td>
                                <td>' . date("g:i A", strtotime($row->b)) . ' - ' . date("g:i A", strtotime($row->c)) .'</td>
                                <td>' . date("g:i:s A", strtotime($row->d)) .'</td>
                                <td>' . date("g:i:s A", strtotime($row->e)) .'</td>
                                <td>On Site</td>
                                <td>' . number_format($row->a, 2, '.', ' ') .'</td>
                            </tr>
                            ';
                        }
                        //HOLIDAY
                        if($row->st=='HD'){

                            $outputStatus.='
                                <tr class="No A">
                                    <td>' . date("F j, Y", strtotime($DFrom)) .'</td>
                                    <td>' . date("l", strtotime($DFrom)) .'</td>
                                    <td>Holiday</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>' . $row->e .'</td>
                                    <td>0.00</td>
                                </tr>
                            ';
                        }
                    }
                }
            //plus days
            $DFrom=date('Y-m-d', strtotime($DFrom . ' + 1 days'));
        }

        return response()->json(['status'=>200, 'data'=>$outputStatus]);
    }
}

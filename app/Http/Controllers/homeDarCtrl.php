<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Models\homeDar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class homeDarCtrl extends Controller
{
    //insertdar
    public function create_dar(Request $request)
    {
        $now = Carbon::now();
        // $activitydatetime = $now->format('Y-m-d h:i A');

        $validator = Validator::make($request->all(),[
            'dar' =>'required',
        ]);

        if(!$validator->passes()){
            return response()->json(['stat'=>201, 'msg'=>"Please enter your activity!", 'error'=>$validator->errors()->toArray()]);
        }
        else{
            $value =[
                'empID' =>session()->get('LoggedUserEmpID'),
                'empActivity' => $request->dar,
                'DarDateTime' => $now,
            ];
            $insert = homeDar::create($value);

            if($insert){
                return response()->json(['stat'=>200,'msg'=>"Successfully added to your DAR!"]);
            }
        }
    }

    //getdar
    public function getall_dar(Request $request){
        $now = Carbon::now();

        $activitydate= $now->format('Y-m-d');
        $activitytime= $now->format('h:i:s A');

        $getDar =  homeDar::selectRaw('empActivity as a,DATE_FORMAT(DarDateTime,"%a, %M %d, %Y") as b,DATE_FORMAT(DarDateTime, "%h:%i:%s %p") as c')
        ->where('empID',session()->get('LoggedUserEmpID'))
            ->latest('created_at')
            ->get();

        if($getDar){
            return response()->json(['status'=>200, 'data'=>$getDar]);
        }
    }

    //filterdar
    public function filter_dar (Request $request){
    $start = new Carbon($request->dFrom);
    $end = new Carbon($request->dTo.' + 1 days');

    $query = homeDar::selectRaw('created_at,empActivity as a,DATE_FORMAT(DarDateTime,"%a, %M %d, %Y") as b,DATE_FORMAT(DarDateTime, "%h:%i:%s %p") as c')
        ->where('empID',session()->get('LoggedUserEmpID'))
        ->whereBetween('created_at', [$start, $end])
        ->latest('created_at')
        ->get();

      if($query){
        return response()->json(['status'=>200, 'data'=>$query]);
        }
    }

}

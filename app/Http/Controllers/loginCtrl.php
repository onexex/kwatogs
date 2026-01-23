<?php

namespace App\Http\Controllers;
use DB;
use Validator;
use Carbon\Carbon;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class loginCtrl extends Controller
{
    public function loginSystem(Request $request){

        $current_date_time = Carbon::now()->toDateTimeString();
        $validator = Validator::make($request->all(),[
            'username'=>'required|max:50|email',
            'password'=>'required|max:500',
        ]);

        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }else{
            $userinfo =  User::select('users.*', 'emp_details.empPos', 'emp_details.empISID','emp_details.empCompID', 'emp_details.empDepID' )
                ->where('email','=',$request->username)
                ->leftjoin('emp_details','users.empID','=','emp_details.empID')
                ->first();
            // dd($userinfo->empCompID);
            if(!$userinfo){
                return response()->json(['status'=>202,'msg'=>'Sorry We do not recognize your email!']);
            }else{
                if (Hash::check($request->password,$userinfo->password)){

                    if ($userinfo) {
                        Auth::login($userinfo);
                    }
                    $request->session()->put('LoggedUserID', $userinfo->id);
                    $request->session()->put('LoggedUserRole', $userinfo->role);
                    $request->session()->put('LoggedUserDep', $userinfo->empDepID);
                    $request->session()->put('LoggedUserPos', $userinfo->empPos);
                    $request->session()->put('LoggedUserEmpID', $userinfo->empID);
                    $request->session()->put('LoggedUserComp', $userinfo->empCompID);
                    $request->session()->put('LoggedISID', $userinfo->empISID);
                    $request->session()->put('loggedEmployee', $userinfo->fname . ' ' .$userinfo->lname );

                    $userAccess = DB::table('access')
                    ->where('empID', '=', $userinfo->empID)
                    ->get();

                    foreach ($userAccess as $row)
                    {
                        if ($row->home==1){
                            $request->session()->put('home', $row->home);
                        }
                        if ($row->settings==1){
                            $request->session()->put('settings', $row->settings);
                        }

                        if ($row->rpt_attend==1){
                            $request->session()->put('rpt_attend', $row->rpt_attend);
                        }
                    }

                    return response()->json(['status'=>200]);
                }else{
                    return response()->json(['status'=>202,"msg"=>'Incorect Username or Password!']);
                }
            }
        }
    }

    function logoutSystem(Request $request){
          session()->flush();
           return redirect('/auth/login');

        // if(session()->has('LoggedUserID')){
        //    session()->pull('LoggedUserID');
        //    session()->pull('LoggedUserRole');
        //    return redirect('/auth/login');
        // }
    }
}

<?php

namespace App\Http\Controllers;
use DB;
use Validator;
use Carbon\Carbon;

use App\Models\AllowedIp;
use App\Models\IpAccessLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class loginCtrl extends Controller
{
    /**
     * Generic message used for both "unknown email" and "wrong password"
     * cases so brute-force attempts can't be used to enumerate valid accounts.
     */
    private const INVALID_CREDENTIALS_MSG = 'Incorrect email or password!';

    /**
     * Spatie role that always bypasses IP restriction with no extra config.
     */
    private const ADMIN_ROLE = 'admin';

    /**
     * Permission that any other role can be granted to also bypass IP restriction.
     * Assign it via Settings -> User Roles.
     */
    private const BYPASS_PERMISSION = 'bypass_ip_restriction';

    public function loginSystem(Request $request){

        // ── Honeypot check: hidden field should always be empty for humans ──
        if (!empty($request->input('website'))) {
            // Silently respond as if credentials were invalid, without
            // revealing that a bot trap was triggered.
            return response()->json(['status'=>202,'msg'=>self::INVALID_CREDENTIALS_MSG]);
        }

        $current_date_time = Carbon::now()->toDateTimeString();
        $validator = Validator::make($request->all(),[
            // Accepts either a short login username OR an email address — the
            // email rule was removed so long emails are no longer rejected here.
            'username'=>'required|max:50',
            'password'=>'required|max:500',
        ]);

        if(!$validator->passes()){
            return response()->json(['status'=>201, 'error'=>$validator->errors()->toArray()]);
        }

        // ── Brute-force lockout: throttle by email + IP ──────────────
        $throttleKey = Str::lower($request->username).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'status' => 429,
                'msg'    => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // Detect whether the typed value is an email or a username and query the
        // matching column. Existing accounts without a username still resolve via
        // email, so nobody is locked out by the new login field.
        $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $userinfo =  User::select('users.*', 'emp_details.empPos', 'emp_details.empISID','emp_details.empCompID', 'emp_details.empDepID', 'emp_details.empStatus' )
            ->where("users.$loginField",'=',$request->username)
            ->leftjoin('emp_details','users.empID','=','emp_details.empID')
            ->first();

        if(!$userinfo || !Hash::check($request->password, $userinfo->password)){
            // Same response & message whether the email exists or not.
            RateLimiter::hit($throttleKey, 60); // 1 minute decay per failed attempt
            \App\Models\AuditLog::record('login-failed', 'User', null, [
                'email' => ['from' => '', 'to' => $request->username],
            ]);
            return response()->json(['status'=>202,'msg'=>self::INVALID_CREDENTIALS_MSG]);
        }

        // Successful login: clear throttle attempts for this key
        RateLimiter::clear($throttleKey);

        // Regenerate the session ID to prevent session fixation attacks
        $request->session()->regenerate();

        Auth::login($userinfo);

        $request->session()->put('LoggedUserID', $userinfo->id);
        $request->session()->put('LoggedUserRole', $userinfo->role);
        $request->session()->put('LoggedUserDep', $userinfo->empDepID);
        $request->session()->put('LoggedUserPos', $userinfo->empPos);
        $request->session()->put('LoggedUserEmpID', $userinfo->empID);
        $request->session()->put('LoggedUserComp', $userinfo->empCompID);
        $request->session()->put('LoggedISID', $userinfo->empISID);
        $request->session()->put('loggedEmployee', $userinfo->fname . ' ' .$userinfo->lname );
        // One-shot flag: show the HR Attention Center welcome banner once, on the first page
        // after login (the partial reads then forgets it). Fresh each login (session regenerated).
        $request->session()->put('hr_attention_greet', true);

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

        // ── IP Restriction ────────────────────────────────────────────────────────
        // Admin role → always bypass. Other roles → bypass only if they have the
        // bypass_ip_restriction permission. Everyone else is checked against the allowlist.
        $needsIpCheck = ! $userinfo->hasRole(self::ADMIN_ROLE)
                     && ! $userinfo->hasPermissionTo(self::BYPASS_PERMISSION);

        if ($needsIpCheck) {
            if (! AllowedIp::isAllowed($request->ip())) {
                // Record the blocked login attempt before tearing down
                IpAccessLog::record('blocked', 'login', $request->ip(), $userinfo);

                // Tear down: undo Auth::login() and clear the session
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'status' => 403,
                    'msg'    => 'Access Denied. Your current IP address is not authorized.',
                ]);
            }
        }

        // ── Separated-employee block ──────────────────────────────────────────
        // Separated employees (empStatus 0=Resigned, 2=End of Contract) must not
        // access the system, for data security. The legacy super admin (role==1)
        // and the spatie 'admin' role are exempt to avoid locking out admins —
        // same exemption rule as Maintenance Mode.
        $isExemptFromStatusBlock = ((int) ($userinfo->role ?? 0) === 1)
            || $userinfo->hasRole(self::ADMIN_ROLE);

        if (!$isExemptFromStatusBlock && (string) ($userinfo->empStatus ?? '1') !== '1') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'status' => 403,
                'msg'    => 'Your account is inactive. Please contact HR.',
            ]);
        }

        // ── Record successful login ───────────────────────────────────────────
        IpAccessLog::record('allowed', 'login', $request->ip(), $userinfo);

        return response()->json(['status'=>200]);
    }

    function logoutSystem(Request $request){
        // Invalidate the session and rotate the CSRF token on logout
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/auth/login');
    }
}

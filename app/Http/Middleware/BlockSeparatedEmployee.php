<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Blocks separated employees from accessing the system, for data security.
 *
 * A separated employee is one whose emp_details.empStatus is not '1'
 * (0=Resigned, 2=End of Contract). The login flow already rejects them at
 * sign-in (loginCtrl@loginSystem); this middleware catches anyone who was
 * separated WHILE already logged in — on their next request they are logged
 * out and bounced to the login screen.
 *
 * Exemptions mirror Maintenance Mode: the legacy super admin (users.role == 1)
 * and the spatie 'admin' role are never blocked, so an admin account that isn't
 * marked Employed can't be locked out.
 */
class BlockSeparatedEmployee
{
    private const ADMIN_ROLE = 'admin';

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Unauthenticated requests are handled by the auth layer, not here.
        if (! $user) {
            return $next($request);
        }

        if ($this->isExempt($user)) {
            return $next($request);
        }

        try {
            $status = (string) (optional($user->empDetail)->empStatus ?? '1');
        } catch (Throwable $e) {
            // Never let a DB hiccup hard-down the app — fail open.
            return $next($request);
        }

        if ($status !== '1') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status'   => 403,
                    'msg'      => 'Your account is inactive. Please contact HR.',
                    'redirect' => url('/auth/login'),
                ], 403);
            }

            return redirect('/auth/login')->with('fail', 'Your account is inactive. Please contact HR.');
        }

        return $next($request);
    }

    private function isExempt($user): bool
    {
        if ((int) ($user->role ?? 0) === 1) {
            return true;
        }

        try {
            return $user->hasRole(self::ADMIN_ROLE);
        } catch (Throwable $e) {
            return false;
        }
    }
}

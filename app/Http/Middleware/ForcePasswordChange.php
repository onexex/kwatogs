<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces a user flagged with `must_change_password` to set a new password
 * before they can use the rest of the app.
 *
 * The flag is set in bulk by a one-time migration (security stretch — everyone
 * re-sets their password once). Once the user changes it, the flag is cleared
 * and this middleware steps aside.
 *
 * The forced-change screen + its POST endpoint and logout are exempt, otherwise
 * the user would be trapped (unable to even reach the change form or sign out).
 * AJAX/JSON requests get a 423 with a `redirect` URL instead of a swallowed 302.
 */
class ForcePasswordChange
{
    private const EXEMPT_PATHS = [
        'force-password-change',
        'force-password-change/update',
        'logoutSystem',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->must_change_password && ! $request->is(...self::EXEMPT_PATHS)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status'               => 423,
                    'must_change_password' => true,
                    'redirect'             => url('/force-password-change'),
                    'msg'                  => 'You must change your password before continuing.',
                ], 423);
            }

            return redirect('/force-password-change');
        }

        return $next($request);
    }
}

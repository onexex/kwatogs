<?php

namespace App\Http\Middleware;

use App\Models\AllowedIp;
use App\Models\IpAccessLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckEmployeeIp
{
    /**
     * Spatie role that always bypasses IP restriction with no extra config.
     */
    private const ADMIN_ROLE = 'admin';

    /**
     * Permission that any other role can be granted to also bypass IP restriction.
     * Assign it via Settings -> User Roles.
     */
    private const BYPASS_PERMISSION = 'bypass_ip_restriction';

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        // Admin role always bypasses — no configuration needed
        if ($user->hasRole(self::ADMIN_ROLE)) {
            return $next($request);
        }

        // Any other role with the bypass permission also skips the check
        if ($user->hasPermissionTo(self::BYPASS_PERMISSION)) {
            return $next($request);
        }

        // Everyone else: enforce the IP allowlist
        $clientIp = $request->ip();

        if (! AllowedIp::isAllowed($clientIp)) {
            IpAccessLog::record('blocked', 'access', $clientIp, $user);
            return $this->deny($request, $clientIp);
        }

        return $next($request);
    }

    private function deny(Request $request, string $ip): Response
    {
        $message = 'Access Denied. Your current IP address is not authorized.';

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 403,
                'message' => $message,
                'ip'      => $ip,
            ], 403);
        }

        return redirect('/auth/login')->with('ip_denied', $message);
    }
}

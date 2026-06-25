<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Locks affected users out of the app while maintenance mode is on.
 *
 * Two flavours, chosen on the Settings -> Maintenance Mode screen:
 *   - GLOBAL: every non-exempt user is locked out.
 *   - DEPARTMENT: only employees whose department is selected are locked out.
 *
 * Exemptions are permission-driven (assign via Settings -> User Roles):
 *   - 'maintenancebypass' : keep using the system while maintenance is on.
 *   - 'maintenancemode'   : manage the screen — must always get in to turn it off.
 *   - the legacy super-admin (users.role == 1) and the spatie 'admin' role.
 *
 * The blocked user is NOT logged out — once maintenance ends they continue
 * where they left off.
 */
class CheckMaintenanceMode
{
    private const ADMIN_ROLE         = 'admin';
    private const BYPASS_PERMISSION  = 'maintenancebypass';
    private const MANAGE_PERMISSION  = 'maintenancemode';

    public function handle(Request $request, Closure $next): Response
    {
        // Never let a missing table / DB hiccup take the whole app down.
        try {
            $setting = MaintenanceSetting::current();
        } catch (Throwable $e) {
            return $next($request);
        }

        if (! $setting->isCurrentlyActive()) {
            return $next($request);
        }

        $user = Auth::user();

        // Unauthenticated requests are handled by the auth layer, not here.
        if (! $user) {
            return $next($request);
        }

        if ($this->isExempt($user)) {
            return $next($request);
        }

        // Department-scoped maintenance only affects matching employees.
        $departmentId = optional($user->empDetail)->empDepID;

        if (! $setting->appliesToDepartment($departmentId)) {
            return $next($request);
        }

        return $this->lockout($request, $setting);
    }

    private function isExempt($user): bool
    {
        // Legacy super admin flag.
        if ((int) ($user->role ?? 0) === 1) {
            return true;
        }

        try {
            if ($user->hasRole(self::ADMIN_ROLE)) {
                return true;
            }
        } catch (Throwable $e) {
            // Roles not resolvable — fall through to permission checks.
        }

        return $user->can(self::BYPASS_PERMISSION) || $user->can(self::MANAGE_PERMISSION);
    }

    private function lockout(Request $request, MaintenanceSetting $setting): Response
    {
        $message = $setting->message
            ?: 'The system is temporarily unavailable while we perform scheduled maintenance.';

        $retryAfter = $setting->ends_at ? max(0, now()->diffInSeconds($setting->ends_at, false)) : null;

        if ($request->expectsJson() || $request->ajax()) {
            $payload = response()->json([
                'status'  => 503,
                'message' => $message,
                'maintenance' => true,
                'ends_at' => $setting->ends_at?->toIso8601String(),
            ], 503);

            if ($retryAfter) {
                $payload->headers->set('Retry-After', (string) $retryAfter);
            }

            return $payload;
        }

        $response = response()->view('errors.maintenance_active', [
            'message' => $message,
            'endsAt'  => $setting->ends_at,
        ], 503);

        if ($retryAfter) {
            $response->headers->set('Retry-After', (string) $retryAfter);
        }

        return $response;
    }
}

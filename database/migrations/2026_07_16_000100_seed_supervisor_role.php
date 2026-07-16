<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Seeds the `supervisor` role and grants it `adminovertime`, so a supervisor
 * can file overtime on behalf of their team from the Apply Employee Overtime
 * screen.
 *
 * The role is deliberately NOT granted `approveovertime`: AdminOvertimeController
 * files as FOR APPROVAL for anyone who cannot approve overtime, so the request
 * lands in Pending Overtime Requests instead of being pre-approved. Granting
 * `approveovertime` to this role would let supervisors self-approve their own
 * filings.
 *
 * NOTE — role-name collision: the admin-ish matcher used by earlier permission
 * seeds (`name like '%super%'`, meant for "super admin") also matches
 * "supervisor". Future seeds must match `%super admin%`/`%superadmin%` instead,
 * or they will silently grant this role admin-level permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'adminovertime', 'guard_name' => 'web']);
            $role->givePermissionTo($perm);
            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        try {
            \Spatie\Permission\Models\Role::where('name', 'supervisor')->delete();
            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }
};

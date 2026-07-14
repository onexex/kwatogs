<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Seeds the `handbookmanagement` permission (HR side of the Employee Handbook)
 * and grants it to admin-level roles, mirroring the summarylogs/errorlogs
 * roll-out. The employee-facing "Handbook" screen is permission-less.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'handbookmanagement', 'guard_name' => 'web']);
            \Spatie\Permission\Models\Role::where(function ($q) {
                $q->whereHas('permissions', fn ($p) => $p->where('name', 'accessrights'))
                  ->orWhere('name', 'like', '%admin%')->orWhere('name', 'like', '%super%');
            })->get()->each(fn ($r) => $r->givePermissionTo($perm));
            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        try {
            \Spatie\Permission\Models\Permission::where('name', 'handbookmanagement')->delete();
            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }
};

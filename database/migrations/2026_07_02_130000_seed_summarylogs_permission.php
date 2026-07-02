<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Summary Logs Management needs no new table — attendance_summaries and
     * attendance_deductions already exist. This migration only seeds the
     * `summarylogs` permission and grants it to admin-level roles, mirroring
     * how the errorlogs permission was rolled out.
     */
    public function up(): void
    {
        try {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'summarylogs', 'guard_name' => 'web']);
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
            \Spatie\Permission\Models\Permission::where('name', 'summarylogs')->delete();
            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }
};

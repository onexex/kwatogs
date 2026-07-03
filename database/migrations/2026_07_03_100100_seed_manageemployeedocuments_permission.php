<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Employment Documents on the E-201 Personnel Viewer are gated by a dedicated
     * `manageemployeedocuments` permission (upload/delete only — viewing/downloading
     * needs just E-201 access). This migration seeds that permission and grants it to
     * admin-level roles, mirroring how the summarylogs/errorlogs permissions were rolled out.
     */
    public function up(): void
    {
        try {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manageemployeedocuments', 'guard_name' => 'web']);
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
            \Spatie\Permission\Models\Permission::where('name', 'manageemployeedocuments')->delete();
            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }
};

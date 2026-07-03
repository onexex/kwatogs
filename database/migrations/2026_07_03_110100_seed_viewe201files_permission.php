<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * `viewe201files` lets a role see the (read-only) Employment Documents section on
     * the employee's own E-201 page (pages/modules/201.blade.php) — independent of the
     * `e201` permission that grants the page itself, so HR can decide which staff may
     * self-view their 201-file documents. Seeded to admin-level roles here (summarylogs
     * pattern); HR assigns it to employee roles in Settings → User Roles.
     */
    public function up(): void
    {
        try {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'viewe201files', 'guard_name' => 'web']);
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
            \Spatie\Permission\Models\Permission::where('name', 'viewe201files')->delete();
            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }
};

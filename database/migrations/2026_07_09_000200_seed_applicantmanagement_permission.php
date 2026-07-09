<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Seed the `applicantmanagement` spatie permission and grant it to admin-level
 * roles (same pattern as the summarylogs / manageemployeedocuments seeds).
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => 'applicantmanagement', 'guard_name' => 'web']
            );

            \Spatie\Permission\Models\Role::where(function ($q) {
                $q->whereHas('permissions', fn ($p) => $p->where('name', 'accessrights'))
                  ->orWhere('name', 'like', '%admin%')
                  ->orWhere('name', 'like', '%super%');
            })->get()->each(fn ($r) => $r->givePermissionTo($perm));

            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
            // Permission may already exist / roles table not ready — fail silently.
        }
    }

    public function down(): void
    {
        try {
            \Spatie\Permission\Models\Permission::where('name', 'applicantmanagement')->delete();
            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }
};

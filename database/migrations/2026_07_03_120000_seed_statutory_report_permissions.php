<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Tier-1 statutory / government-compliance reports need no new tables — they
     * aggregate existing `payrolls` rows. This migration only seeds the four
     * report permission keys and grants them to admin-level roles, mirroring the
     * summarylogs / errorlogs rollout pattern.
     */
    private array $keys = ['birreport', 'sssreport', 'philhealthreport', 'pagibigreport'];

    public function up(): void
    {
        try {
            $roles = \Spatie\Permission\Models\Role::where(function ($q) {
                $q->whereHas('permissions', fn ($p) => $p->where('name', 'accessrights'))
                  ->orWhere('name', 'like', '%admin%')
                  ->orWhere('name', 'like', '%super%');
            })->get();

            foreach ($this->keys as $key) {
                $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $key, 'guard_name' => 'web']);
                $roles->each(fn ($r) => $r->givePermissionTo($perm));
            }

            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        try {
            \Spatie\Permission\Models\Permission::whereIn('name', $this->keys)->delete();
            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }
};

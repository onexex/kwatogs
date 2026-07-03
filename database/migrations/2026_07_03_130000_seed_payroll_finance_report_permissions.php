<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Tier-2 core payroll/finance reports (Payroll Register, Payroll Journal,
     * Loan Ledger) aggregate existing tables — no new schema. Seeds the three
     * report permission keys and grants them to admin-level roles, mirroring the
     * statutory-report / summarylogs rollout.
     */
    private array $keys = ['payrollregister', 'payrolljournal', 'loanledger'];

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

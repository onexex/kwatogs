<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_approvals', function (Blueprint $table) {
            $table->id();
            $table->date('pay_date')->unique();      // a pay date with a row = approved/final
            $table->string('approved_by')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        // Permissions
        try {
            $approve = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'approvepayroll', 'guard_name' => 'web']);
            $override = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'regeneratepayroll', 'guard_name' => 'web']);

            // approvepayroll -> roles that already manage payroll
            \Spatie\Permission\Models\Role::whereHas('permissions', function ($q) {
                $q->where('name', 'payroll');
            })->get()->each(fn ($r) => $r->givePermissionTo($approve));

            // regeneratepayroll (override) -> admin-type roles (have access-rights mgmt or named admin/super)
            \Spatie\Permission\Models\Role::where(function ($q) {
                $q->whereHas('permissions', function ($p) {
                    $p->where('name', 'accessrights');
                })->orWhere('name', 'like', '%admin%')->orWhere('name', 'like', '%super%');
            })->get()->each(fn ($r) => $r->givePermissionTo($override));

            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
            // skip if permission tables not present
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_approvals');
        try {
            \Spatie\Permission\Models\Permission::whereIn('name', ['approvepayroll', 'regeneratepayroll'])->delete();
        } catch (\Throwable $e) {
        }
    }
};

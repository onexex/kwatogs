<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) HR pay adjustments (one-time, per pay date)
        Schema::create('pay_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');
            $table->date('pay_date');                 // payroll run this applies to
            $table->string('label');                  // e.g. "Unpaid OT (May cutoff)"
            $table->enum('kind', ['addition', 'deduction']);
            // gross = taxed (added before tax/contributions) ; net = after-tax (take-home)
            $table->enum('apply_to', ['gross', 'net'])->default('net');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('remarks')->nullable();    // authorization / reason
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'pay_date']);
        });

        // 2) Visibility column on payrolls (legacy adjustment_amount was on payroll_details only)
        if (!Schema::hasColumn('payrolls', 'adjustment_amount')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->decimal('adjustment_amount', 12, 2)->default(0)->after('allowances');
            });
        }

        // 3) Permission for the new page, granted to roles that already manage Loans & Charges
        try {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => 'payadjustments', 'guard_name' => 'web']
            );
            $roles = \Spatie\Permission\Models\Role::whereHas('permissions', function ($q) {
                $q->whereIn('name', ['loanmanagement', 'payroll']);
            })->get();
            foreach ($roles as $role) {
                $role->givePermissionTo($perm);
            }
            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
            // permission tables not present / different setup — skip silently
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pay_adjustments');

        if (Schema::hasColumn('payrolls', 'adjustment_amount')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->dropColumn('adjustment_amount');
            });
        }

        try {
            \Spatie\Permission\Models\Permission::where('name', 'payadjustments')->delete();
        } catch (\Throwable $e) {
        }
    }
};

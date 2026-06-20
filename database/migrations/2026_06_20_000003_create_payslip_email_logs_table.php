<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_email_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->char('employee_id', 20);
            $table->date('pay_date');
            $table->string('email_to')->nullable();
            $table->string('status', 20)->default('queued'); // queued | sent | failed
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('mail_integration_setting_id')->nullable();
            $table->string('sent_by')->nullable(); // user name, or 'system' for auto-send
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['pay_date', 'employee_id']);
            $table->index('payroll_id');
            $table->index('status');
        });

        // Self-register the permission, mirroring the audit_logs / mail_integration_settings migrations.
        try {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'payslipemail', 'guard_name' => 'web']);
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
        Schema::dropIfExists('payslip_email_logs');
        try {
            \Spatie\Permission\Models\Permission::where('name', 'payslipemail')->delete();
        } catch (\Throwable $e) {
        }
    }
};

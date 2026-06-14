<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_requests', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->index();
            $table->date('request_date');
            $table->time('old_sched_in')->nullable();
            $table->time('old_sched_out')->nullable();
            $table->time('new_sched_in');
            $table->time('new_sched_out');
            $table->string('reason')->nullable();
            $table->string('status')->default('FORAPPROVAL'); // FORAPPROVAL | APPROVED | DISAPPROVED | CANCELED
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->string('disapproved_remarks')->nullable();
            $table->boolean('applied')->default(false);
            $table->timestamps();
        });

        // Permissions
        try {
            $create = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'createschedulechange', 'guard_name' => 'web']);
            $approve = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'approveschedulechange', 'guard_name' => 'web']);

            // file -> same audience as leave application (employees)
            \Spatie\Permission\Models\Role::whereHas('permissions', fn ($q) => $q->where('name', 'leaveapplication'))
                ->get()->each(fn ($r) => $r->givePermissionTo($create));
            // approve -> same audience as pending leave requests (HR approvers)
            \Spatie\Permission\Models\Role::whereHas('permissions', fn ($q) => $q->where('name', 'pendingleaverequests'))
                ->get()->each(fn ($r) => $r->givePermissionTo($approve));

            app()['cache']->forget('spatie.permission.cache');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_requests');
        try {
            \Spatie\Permission\Models\Permission::whereIn('name', ['createschedulechange', 'approveschedulechange'])->delete();
        } catch (\Throwable $e) {
        }
    }
};

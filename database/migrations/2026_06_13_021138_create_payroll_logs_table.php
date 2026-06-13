<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id')->nullable();
            $table->string('employee_id');
            $table->string('employee_name')->nullable();
            $table->string('department_id')->nullable();
            $table->string('department_name')->nullable();
            $table->string('classification')->nullable();
            $table->date('pay_date');
            $table->date('payroll_start_date')->nullable();
            $table->date('payroll_end_date')->nullable();
            $table->decimal('gross_pay', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->decimal('pay_rec', 12, 2)->default(0);
            $table->json('breakdown')->nullable(); // full per-employee computation trace
            $table->timestamps();

            $table->index(['pay_date', 'employee_id']);
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_logs');
    }
};

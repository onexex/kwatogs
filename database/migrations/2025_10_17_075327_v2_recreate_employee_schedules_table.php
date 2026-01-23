<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('employee_schedules');
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            // link to employee
            $table->foreignId('employee_id');
            // schedule details
            $table->date('sched_start_date');
            $table->time('sched_in');
            $table->date('sched_end_date');
            $table->time('sched_out');
            $table->string('shift_type')->nullable();  
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
    }
};


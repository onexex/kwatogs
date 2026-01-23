<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id');
            $table->date('attendance_date');
            $table->decimal('total_hours', 5, 2)->default(0);
            $table->integer('mins_late')->default(0);
            $table->integer('mins_undertime')->default(0);
            $table->integer('mins_night_diff')->default(0);
            $table->string('status')->default('present'); // present | ob | leave | absent
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
    }
};



<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop the old table if it exists
        Schema::dropIfExists('home_attendances');

        Schema::create('home_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id');
            $table->foreignId('employee_id');
            $table->date('attendance_date');
            $table->timestamp('time_in')->nullable();
            $table->timestamp('time_out')->nullable();
            $table->decimal('duration_hours', 5, 2)->nullable();
            $table->decimal('night_diff_hours', 5, 2)->nullable();
            $table->string('status')->default('present'); // present | ob | leave | absent
            $table->string('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_attendances');
    }
};

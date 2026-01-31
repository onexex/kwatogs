<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_deductions', function (Blueprint $table) {
            $table->id();
            // Link to the summary record
            $table->foreignId('attendance_summary_id');

            $table->integer('deduction_minutes')->default(0);
            $table->string('reason')->nullable();
            
            // Helpful for auditing: who logged this?
            $table->unsignedBigInteger('added_by')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_deductions');
    }
};
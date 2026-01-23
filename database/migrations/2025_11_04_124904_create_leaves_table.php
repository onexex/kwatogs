<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();

            // Use string for employee_id
            $table->string('employee_id')->index();

            // Date range
            $table->date('start_date');
            $table->date('end_date');

            // Leave details
            $table->string('leave_type')->nullable(); // e.g. Vacation, Sick, Emergency
            $table->decimal('total_hrs', 5, 2)->default(0); // can handle half-day, etc.
            $table->string('reason')->nullable();

            // Approval
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->string('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();

            // Remarks or comments
            $table->text('remarks')->nullable();

            $table->timestamps();

            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};

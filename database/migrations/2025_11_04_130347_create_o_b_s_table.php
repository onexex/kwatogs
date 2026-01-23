<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obs', function (Blueprint $table) {
            $table->id();

            // Use string for employee_id
            $table->string('employee_id')->index();

            // OB date range
            $table->date('start_date');
            $table->date('end_date');

            // OB details
            $table->string('destination')->nullable(); // OB location
            $table->string('purpose')->nullable();
            $table->decimal('total_hrs', 5, 2)->default(0);

            // Approval
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->string('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();

            // Remarks
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obs');
    }
};

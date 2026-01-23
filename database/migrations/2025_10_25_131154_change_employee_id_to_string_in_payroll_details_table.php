<?php

 use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payroll_details', function (Blueprint $table) {

            // Drop foreign key if exists
            // $table->dropForeign(['employee_id']);

            // Change column type
            $table->string('employee_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_details', function (Blueprint $table) {
            // Convert it back to integer (unsigned)
            $table->unsignedBigInteger('employee_id')->change();

            // Re-add FK only if needed
            // $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }
};

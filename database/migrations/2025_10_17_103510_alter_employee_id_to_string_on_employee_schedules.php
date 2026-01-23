<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('payroll_details', function (Blueprint $table) {
        // Change employee_id from foreignId (integer) to string
        $table->string('employee_id')->change();
    });
}

public function down(): void
{
    Schema::table('payroll_details', function (Blueprint $table) {
        // Revert back to foreignId (unsigned big integer)
        $table->foreignId('employee_id')->change();
    });
}

};

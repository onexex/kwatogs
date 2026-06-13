<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('label')->nullable();                 // e.g. "1st Cut-off"
            $table->unsignedTinyInteger('pay_day')->nullable();  // e.g. 15 (null when end-of-month)
            $table->boolean('pay_end_of_month')->default(false); // true => pay date is the last day of month
            $table->unsignedTinyInteger('cutoff_from_day');      // e.g. 26
            $table->boolean('cutoff_from_prev_month')->default(false); // true => from-day is the previous month
            $table->unsignedTinyInteger('cutoff_to_day');        // e.g. 10
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};

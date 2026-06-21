<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Store the holiday benefit per day so the Payroll Detail Report can show a
     * dedicated "Holiday Pay" line for the date (e.g. a present employee on a
     * regular holiday gets both a "Present" row and a "Holiday Pay" row).
     */
    public function up(): void
    {
        Schema::table('payroll_details', function (Blueprint $table) {
            // 'Regular' | 'Special' | null (no holiday benefit granted that day)
            $table->string('holiday_type', 20)->nullable()->after('logsType');
            $table->decimal('holiday_pay', 10, 2)->default(0)->after('holiday_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_details', function (Blueprint $table) {
            $table->dropColumn(['holiday_type', 'holiday_pay']);
        });
    }
};

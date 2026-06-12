<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'abs_ut_deduction')) {
                // Combined Absent + Tardy(late) + Undertime deduction shown in the
                // "Abs/Trd/Ut" column of the payroll table.
                $table->decimal('abs_ut_deduction', 12, 2)->default(0)->after('undertime_deduction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'abs_ut_deduction')) {
                $table->dropColumn('abs_ut_deduction');
            }
        });
    }
};

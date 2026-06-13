<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('cash_advance', 12, 2)->default(0)->after('company_loan');
            $table->decimal('other_deduction', 12, 2)->default(0)->after('cash_advance');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['cash_advance', 'other_deduction']);
        });
    }
};

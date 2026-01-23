<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
          

            // Government contributions (Employer Share)
            $table->decimal('sss_employer', 10, 2)->after('pagibig_contribution')->default(0);
            $table->decimal('philhealth_employer', 10, 2)->after('sss_employer')->default(0);
            $table->decimal('pagibig_employer', 10, 2)->after('philhealth_employer')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([ 'sss_employer', 'philhealth_employer', 'pagibig_employer']);
        });
    }
};

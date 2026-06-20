<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-employee government-dues enrolment toggles.
     * Default 1 (enrolled) so existing payroll behaviour is preserved —
     * every current employee stays subject to SSS / PhilHealth / Pag-IBIG
     * until an admin switches them off on the Government Dues screen.
     */
    public function up(): void
    {
        Schema::table('emp_details', function (Blueprint $table) {
            $table->boolean('sss_enabled')->default(true)->after('empClassification');
            $table->boolean('philhealth_enabled')->default(true)->after('sss_enabled');
            $table->boolean('pagibig_enabled')->default(true)->after('philhealth_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('emp_details', function (Blueprint $table) {
            $table->dropColumn(['sss_enabled', 'philhealth_enabled', 'pagibig_enabled']);
        });
    }
};

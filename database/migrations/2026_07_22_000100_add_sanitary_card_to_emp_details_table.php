<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Health / Sanitary Card (a PH occupational health permit that must be renewed
 * periodically) — captured on the E-201 record like the passport (number + expiry
 * date). Nullable; surfaced read-only on the dossier and self-view, and drives the
 * renewal alerts (HR Dashboard / attention bell / employee live banner).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emp_details', function (Blueprint $table) {
            $table->string('empSanitaryCardNo')->nullable()->after('empUMID');
            $table->date('empSanitaryCardExpDate')->nullable()->after('empSanitaryCardNo');
        });
    }

    public function down(): void
    {
        Schema::table('emp_details', function (Blueprint $table) {
            $table->dropColumn([
                'empSanitaryCardNo',
                'empSanitaryCardExpDate',
            ]);
        });
    }
};

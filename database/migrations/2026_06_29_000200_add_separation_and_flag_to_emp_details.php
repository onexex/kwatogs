<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee separation + flagging module (managed from the E-201 Personnel Viewer).
 *
 * Two distinct layers:
 *  - Employment exit (Resigned / End of Contract) keeps using emp_details.empStatus
 *    ('1'=Employed, '0'=Resigned, '2'=End of Contract) — these columns just add the
 *    reason / date / frozen years-rendered around that exit.
 *  - Flags (Blacklisted / Red Flag) are independent of employment state: a flag can
 *    mark a still-active employee and does NOT change empStatus or payroll eligibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emp_details', function (Blueprint $table) {
            // Employment-exit metadata
            $table->text('separation_reason')->nullable()->after('empDateResigned');
            $table->date('separation_date')->nullable()->after('separation_reason');
            $table->decimal('years_rendered', 5, 2)->nullable()->after('separation_date');

            // Independent flag layer
            $table->string('flag_status', 20)->nullable()->after('years_rendered'); // null | redflag | blacklist
            $table->text('flag_reason')->nullable()->after('flag_status');
            $table->date('flagged_at')->nullable()->after('flag_reason');
            $table->string('flagged_by', 150)->nullable()->after('flagged_at');
        });
    }

    public function down(): void
    {
        Schema::table('emp_details', function (Blueprint $table) {
            $table->dropColumn([
                'separation_reason',
                'separation_date',
                'years_rendered',
                'flag_status',
                'flag_reason',
                'flagged_at',
                'flagged_by',
            ]);
        });
    }
};

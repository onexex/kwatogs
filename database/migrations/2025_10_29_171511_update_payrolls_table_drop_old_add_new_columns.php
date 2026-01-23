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
            // 🗑️ Drop old columns if they exist
            if (Schema::hasColumn('payrolls', 'night_diff_hours')) {
                $table->dropColumn([
                    'night_diff_hours',
                    'undertime_minutes',
                    'late_minutes',
                    'other_earnings'
                ]);
            }

            // ➕ Add new columns
            $table->decimal('overBreakDeduction', 10, 2)->after('undertime_deduction')->default(0);
            $table->decimal('outPassDeduction', 10, 2)->after('overBreakDeduction')->default(0);

             
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Remove the newly added columns
            $table->dropColumn([
                'overBreakDeduction',
                'outPassDeduction',
                
            ]);

            // Recreate dropped columns (optional rollback safety)
            $table->decimal('night_diff_hours', 8, 2)->nullable(); 
            $table->integer('undertime_minutes')->nullable();
            $table->integer('late_minutes')->nullable();
            $table->decimal('other_earnings', 10, 2)->nullable();
        });
    }
};

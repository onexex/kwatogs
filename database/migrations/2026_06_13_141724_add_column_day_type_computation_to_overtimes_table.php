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
        Schema::table('overtimes', function (Blueprint $table) {
            $table->string('day_type')->after('purpose')->default('rest_day');
            $table->decimal('day_type_computation', 10, 2)->after('day_type')->default(0);
            $table->decimal('hourly_rate', 10, 6)->after('day_type_computation')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn('day_type');
            $table->dropColumn('day_type_computation');
            $table->dropColumn('hourly_rate');
        });
    }
};
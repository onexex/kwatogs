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
        Schema::table('holiday_logger', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('type')->constrained('departments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('holiday_logger', function (Blueprint $table) {
            $table->dropColumn('department_id');
        });
    }
};

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
        Schema::table('lilo_validations', function (Blueprint $table) {
            $table->boolean('no_logout_has_deduction')->default(false);
            $table->integer('minute_deduction')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lilo_validations', function (Blueprint $table) {
            $table->dropColumn('no_logout_has_deduction');
            $table->dropColumn('minute_deduction');
        });
    }
};

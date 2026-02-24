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
        Schema::table('leave_credit_allocations', function (Blueprint $table) {
            $table->decimal('balance', 8, 2)->after('credits_allocated')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_credit_allocations', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};

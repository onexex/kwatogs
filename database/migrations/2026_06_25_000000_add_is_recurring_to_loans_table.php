<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Marks a charge as a continuous monthly deduction (e.g. rent):
            // deducted in full every month, no diminishing balance, never auto-"paid".
            $table->boolean('is_recurring')->default(false)->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('is_recurring');
        });
    }
};

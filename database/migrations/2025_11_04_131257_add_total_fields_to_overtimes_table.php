<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->decimal('total_hrs', 5, 2)->default(0)->after('time_out');
            $table->decimal('total_pay', 10, 2)->default(0)->after('total_hrs');
        });
    }

    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn(['total_hrs', 'total_pay']);
        });
    }
};

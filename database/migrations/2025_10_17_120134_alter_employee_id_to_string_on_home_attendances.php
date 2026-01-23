<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make sure doctrine/dbal is installed to modify column types
        Schema::table('home_attendances', function (Blueprint $table) {
            $table->string('employee_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('home_attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->change();
        });
    }
};

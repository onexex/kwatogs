<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emp_infos', function (Blueprint $table) {
            // Babaguhin natin ang column para maging N/A ang default
            $table->string('empReligion')->nullable()->default('N/A')->change();
        });
    }

    public function down(): void
    {
        Schema::table('emp_infos', function (Blueprint $table) {
            // Ibalik sa dati (kung sakaling kailangan i-rollback)
            $table->string('empReligion')->nullable(false)->default(null)->change();
        });
    }
};

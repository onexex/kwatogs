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
        Schema::table('leavevalidation_models', function (Blueprint $table) {
            $table->boolean('pre_allocated')->default(0)->comment('Leave credits are already added to the employee balance early.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leavevalidation_models', function (Blueprint $table) {
            $table->dropColumn('pre_allocated');    
        });
    }
};

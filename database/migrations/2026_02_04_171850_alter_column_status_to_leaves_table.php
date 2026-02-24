<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('status', 50)->default('FORAPPROVAL')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
                Schema::table('leaves', function (Blueprint $table) {
                $table->string('status_new')->default('Pending');
            });

            DB::statement('UPDATE leaves SET status_new = status');

            Schema::table('leaves', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            Schema::table('leaves', function (Blueprint $table) {
                $table->renameColumn('status_new', 'status');
            });
        });
    }
};

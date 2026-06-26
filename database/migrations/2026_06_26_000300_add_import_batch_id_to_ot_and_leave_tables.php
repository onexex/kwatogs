<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Tag the overtime + leave tables so their imports can be rolled back as a unit. */
    public function up(): void
    {
        foreach (['overtimes', 'leaves', 'leave_details'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('import_batch_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['overtimes', 'leaves', 'leave_details'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('import_batch_id');
            });
        }
    }
};

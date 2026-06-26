<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tag the three tables the attendance import writes to with the batch that
     * created the row, so an import can be pulled up and rolled back as a unit.
     */
    public function up(): void
    {
        foreach (['attendance_summaries', 'home_attendances', 'employee_schedules'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('import_batch_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['attendance_summaries', 'home_attendances', 'employee_schedules'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('import_batch_id');
            });
        }
    }
};

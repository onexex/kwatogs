<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Superseded by the unified `import_batches` table. The attendance-only batch table
 * was introduced earlier the same day and never carried production data, so this just
 * drops it. attendance_summaries / home_attendances / employee_schedules keep their
 * `import_batch_id` column — it now points at `import_batches`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('attendance_import_batches');
    }

    public function down(): void
    {
        Schema::create('attendance_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->integer('row_count')->default(0);
            $table->integer('inserted')->default(0);
            $table->integer('updated')->default(0);
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->timestamps();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->integer('row_count')->default(0);
            $table->integer('inserted')->default(0);
            $table->integer('updated')->default(0);
            $table->date('date_from')->nullable(); // earliest attendance_date in the batch
            $table->date('date_to')->nullable();   // latest attendance_date in the batch
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_import_batches');
    }
};

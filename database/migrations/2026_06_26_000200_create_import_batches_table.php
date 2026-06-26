<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified import-batch log shared by the attendance / overtime / leave imports.
 * One row per successful import; every record an import creates is tagged with the
 * batch id so the whole import can be listed and rolled back together.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('module')->index(); // attendance | overtime | leave
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

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};

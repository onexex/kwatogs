<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Related PDF documents attached to a department (the company): BIR registration,
 * business permits, contracts, etc. Files live under public/docs/departments/<id>/;
 * this table holds the metadata. cascadeOnDelete removes rows when a department is
 * deleted — the controller is responsible for unlinking the physical files.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->string('label')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('original_name');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime', 100)->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_documents');
    }
};

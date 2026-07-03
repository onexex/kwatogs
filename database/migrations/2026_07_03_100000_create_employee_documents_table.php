<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employment documents attached to an employee (their "201 file"): signed contracts,
 * government IDs, certificates, clearances, resumes, etc. Files live under
 * public/docs/employees/<user_id>/; this table holds the metadata. cascadeOnDelete
 * removes rows when a user is deleted — the controller is responsible for unlinking
 * the physical files. Keyed on user_id (users.id) because the E-201 admin action
 * routes are User-model-bound; empID is stored denormalized for readability only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('empID')->nullable();
            $table->string('doc_type')->nullable();
            $table->string('label')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('original_name');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime', 100)->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};

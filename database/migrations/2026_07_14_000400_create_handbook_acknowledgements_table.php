<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Read/acknowledgement receipts for handbook sections. One row per
 * (employee, section) — the row itself (with acknowledged_at + version + ip)
 * is the compliance evidence, so it deliberately does NOT use Auditable
 * (like notice read-receipts, these would otherwise flood the audit trail).
 * `acked_version` records WHICH revision of the section the employee agreed to,
 * so HR can require a fresh acknowledgement after a material edit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handbook_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 50);
            $table->foreignId('section_id')->constrained('handbook_sections')->cascadeOnDelete();
            $table->unsignedInteger('acked_version')->default(1);
            $table->string('ip', 45)->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'section_id']);
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handbook_acknowledgements');
    }
};

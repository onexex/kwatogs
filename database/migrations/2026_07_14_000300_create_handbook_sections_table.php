<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee Handbook — ordered sections authored by HR and read by employees
 * in a two-pane workspace. Each section carries a rich-text body and an
 * OPTIONAL supporting document (PDF/image) streamed from outside public/
 * (like the signed-memo attachment on notices), never a URL-reachable copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handbook_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 220)->nullable();
            $table->longText('body')->nullable();          // rich HTML authored by HR
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true); // drafts hidden from employees
            $table->boolean('requires_ack')->default(false); // employee must acknowledge

            // Optional supporting document (stored under storage/app/handbook_docs/<token>/).
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();

            $table->unsignedInteger('version')->default(1); // bumped on body/doc edit
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handbook_sections');
    }
};

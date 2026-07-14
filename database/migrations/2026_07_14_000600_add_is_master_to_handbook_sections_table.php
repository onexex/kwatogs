<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Single-PDF handbook" mode. The whole handbook can be one uploaded document
 * instead of (or alongside) authored sections. It is modeled as ONE special
 * section flagged is_master=true, so it reuses the existing attachment stream,
 * acknowledgement, versioning and read-receipt plumbing. Employees see it
 * pinned to the top of the handbook workspace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('handbook_sections', function (Blueprint $table) {
            $table->boolean('is_master')->default(false)->after('requires_ack');
            $table->index('is_master');
        });
    }

    public function down(): void
    {
        Schema::table('handbook_sections', function (Blueprint $table) {
            $table->dropIndex(['is_master']);
            $table->dropColumn('is_master');
        });
    }
};

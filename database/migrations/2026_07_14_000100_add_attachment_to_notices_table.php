<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signed-memo attachment for the Notices module.
 *
 * A memo can carry a scanned/signed document (PDF or image) that the employee
 * may PREVIEW but not download. The file lives OUTSIDE public/ (under
 * storage/app/notice_memos/…) and is streamed only through a permission-gated
 * route — there is no URL-reachable copy — so the columns store a
 * storage-relative path, not a public asset path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('body');   // relative to storage/app
            $table->string('attachment_name')->nullable()->after('attachment_path'); // original filename
            $table->string('attachment_mime')->nullable()->after('attachment_name');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size']);
        });
    }
};

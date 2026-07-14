<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notice to Explain (NTE) for the Notices module.
 *
 * A disciplinary notice may REQUIRE the employee to submit a written
 * explanation by a deadline HR sets per notice (`respond_by`). The employee
 * submits `response_body` (+ an optional evidence file, stored outside public/
 * like the signed-memo attachment) once; it locks with `response_at`. HR then
 * records a decision (`response_decision` + note + reviewer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            // HR sets these when issuing a disciplinary notice.
            $table->boolean('requires_response')->default(false)->after('status');
            $table->date('respond_by')->nullable()->after('requires_response');

            // Employee's explanation.
            $table->text('response_body')->nullable()->after('respond_by');
            $table->string('response_doc_path')->nullable()->after('response_body');   // relative to storage/app
            $table->string('response_doc_name')->nullable()->after('response_doc_path');
            $table->timestamp('response_at')->nullable()->after('response_doc_name');

            // HR review of the explanation.
            $table->string('response_decision')->nullable()->after('response_at');       // accepted | further_action
            $table->text('response_review_note')->nullable()->after('response_decision');
            $table->string('response_reviewed_by')->nullable()->after('response_review_note');
            $table->timestamp('response_reviewed_at')->nullable()->after('response_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn([
                'requires_response', 'respond_by',
                'response_body', 'response_doc_path', 'response_doc_name', 'response_at',
                'response_decision', 'response_review_note', 'response_reviewed_by', 'response_reviewed_at',
            ]);
        });
    }
};

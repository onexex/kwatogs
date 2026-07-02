<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_requests', function (Blueprint $table) {
            // Employee-requested break window (Kuya Kwatogs). Nullable so existing rows
            // and any legacy break-less request stay valid.
            $table->time('new_break_start')->nullable()->after('new_sched_out');
            $table->time('new_break_end')->nullable()->after('new_break_start');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_requests', function (Blueprint $table) {
            $table->dropColumn(['new_break_start', 'new_break_end']);
        });
    }
};

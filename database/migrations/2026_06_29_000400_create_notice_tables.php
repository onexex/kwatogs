<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notices / Memo module.
 *
 * HR issues notices to an employee. A notice is either a "memo" (informational)
 * or "disciplinary" (a violation that counts toward the suspension threshold).
 * Employees view their own notices; the unread count drives a topbar badge.
 *
 * Accumulating active disciplinary notices triggers escalation: a warning at
 * 3, and an auto suspension recommendation at 4+ (NoticeService thresholds).
 * Recommendations are persisted so HR can review/dismiss/action them — the
 * employment status is never changed automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->index();        // target emp_details.empID / users.empID
            $table->string('type')->default('memo');       // memo | disciplinary
            $table->string('category')->nullable();        // disciplinary reason class (Tardiness, Misconduct, …)
            $table->string('title');
            $table->text('body');
            $table->string('issued_by')->nullable();       // HR user name
            $table->date('issued_at');
            $table->string('status')->default('active');    // active | void  (void = excluded from counts)
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('suspension_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->index();
            $table->text('reason');
            $table->unsignedInteger('notice_count')->default(0);
            $table->string('status')->default('pending');   // pending | dismissed | actioned
            $table->string('recommended_by')->nullable();
            $table->timestamp('recommended_at')->nullable();
            $table->string('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspension_recommendations');
        Schema::dropIfExists('notices');
    }
};

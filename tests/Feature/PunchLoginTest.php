<?php

namespace Tests\Feature;

use App\Models\AttendanceSummary;
use App\Models\EmployeeSchedule;
use App\Models\homeAttendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * DB-backed coverage for the punch "login" entry point
 * homeAttendance::logTimeIn() / logTimeOut().
 *
 * Runs against the MySQL test database (see .env.testing -> dbdash_test) and uses
 * DatabaseTransactions so every test rolls back — it never migrates and never leaves
 * rows behind. The clock is frozen with Carbon::setTestNow() so schedule-window
 * matching is deterministic.
 */
class PunchLoginTest extends TestCase
{
    use DatabaseTransactions;

    private string $emp = 'TEST-EMP-001';

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // unfreeze
        parent::tearDown();
    }

    private function schedule(array $overrides = []): EmployeeSchedule
    {
        return EmployeeSchedule::create(array_merge([
            'employee_id'      => $this->emp,
            'sched_start_date' => '2026-06-16',
            'sched_in'         => '08:00:00',
            'sched_out'        => '17:00:00',
            'sched_end_date'   => '2026-06-16',
        ], $overrides));
    }

    // ── Time-in window matching ─────────────────────────────────────────────

    public function test_time_in_inside_window_creates_punch(): void
    {
        $sched = $this->schedule();
        Carbon::setTestNow(Carbon::parse('2026-06-16 08:30:00')); // inside 07:00–17:00

        $punch = homeAttendance::logTimeIn($this->emp);

        $this->assertNotNull($punch->id);
        $this->assertSame($sched->id, $punch->schedule_id);
        $this->assertSame('2026-06-16', Carbon::parse($punch->attendance_date)->toDateString());
        $this->assertSame('present', $punch->status);
        $this->assertNull($punch->time_out);
    }

    public function test_time_in_before_window_is_rejected(): void
    {
        $this->schedule();
        Carbon::setTestNow(Carbon::parse('2026-06-16 06:00:00')); // before the 1h buffer (07:00)

        $this->expectExceptionMessage('No active schedule found');
        homeAttendance::logTimeIn($this->emp);
    }

    /** Overnight shift (22:00→06:00) on D1 must be matchable just after midnight on D2. */
    public function test_overnight_shift_matched_from_yesterday(): void
    {
        $sched = $this->schedule([
            'sched_start_date' => '2026-06-16',
            'sched_in'         => '22:00:00',
            'sched_out'        => '06:00:00',
            'sched_end_date'   => '2026-06-17',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-06-17 00:30:00')); // 12:30 AM, still inside the shift

        $punch = homeAttendance::logTimeIn($this->emp);

        $this->assertSame($sched->id, $punch->schedule_id);
        // Work day is the shift START date, not the calendar date of the punch.
        $this->assertSame('2026-06-16', Carbon::parse($punch->attendance_date)->toDateString());
    }

    // ── Lunch-break restriction (fix #2: shift-relative anchoring) ───────────

    /**
     * Post-midnight lunch break (02:00–03:00) on an overnight shift matched from
     * yesterday. Anchoring to "today" or to the bare start date would misfire; the
     * shift-relative anchor correctly blocks a punch at 02:30.
     */
    public function test_time_in_during_post_midnight_break_is_blocked(): void
    {
        $this->schedule([
            'sched_start_date' => '2026-06-16',
            'sched_in'         => '22:00:00',
            'sched_out'        => '06:00:00',
            'sched_end_date'   => '2026-06-17',
            'break_start'      => '02:00:00',
            'break_end'        => '03:00:00',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-06-17 02:30:00')); // mid-break

        $this->expectExceptionMessage('still on lunch break');
        homeAttendance::logTimeIn($this->emp);
    }

    // ── Duplicate / stale open-punch handling (fixes #4, #5) ────────────────

    public function test_second_punch_while_open_is_blocked(): void
    {
        $this->schedule();
        Carbon::setTestNow(Carbon::parse('2026-06-16 08:30:00'));
        homeAttendance::logTimeIn($this->emp); // first punch (open)

        Carbon::setTestNow(Carbon::parse('2026-06-16 10:00:00')); // 1.5h later, still open
        $this->expectExceptionMessage('active punch');
        homeAttendance::logTimeIn($this->emp);
    }

    /**
     * A stale (>16h) open punch must be auto-closed (with its summary reconciled)
     * and a fresh punch created, instead of orphaning the old one.
     */
    public function test_stale_open_punch_is_auto_closed_and_summarized(): void
    {
        $oldSched = $this->schedule(); // 2026-06-16 08:00–17:00
        // Simulate a punch the employee forgot to close.
        $stale = homeAttendance::create([
            'employee_id'     => $this->emp,
            'schedule_id'     => $oldSched->id,
            'attendance_date' => '2026-06-16',
            'time_in'         => '2026-06-16 08:00:00',
            'status'          => 'present',
        ]);

        // Next day's schedule + a punch ~24h later.
        $newSched = $this->schedule([
            'sched_start_date' => '2026-06-17',
            'sched_end_date'   => '2026-06-17',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-06-17 08:30:00'));

        $newPunch = homeAttendance::logTimeIn($this->emp);

        $stale->refresh();
        $this->assertNotNull($stale->time_out, 'stale punch should be closed');
        $this->assertSame('Auto-closed (Missed logout)', $stale->remarks);
        // Summary for the stale day was rolled up (fix #5).
        $this->assertDatabaseHas('attendance_summaries', [
            'employee_id'     => $this->emp,
            'attendance_date' => '2026-06-16',
        ]);
        // And a genuinely new open punch exists for the new day.
        $this->assertSame($newSched->id, $newPunch->schedule_id);
        $this->assertNull($newPunch->time_out);
    }

    // ── Full night-shift login → logout, with ND (ties everything together) ──

    public function test_overnight_login_then_logout_computes_duration_and_nd(): void
    {
        $this->schedule([
            'sched_start_date' => '2026-06-16',
            'sched_in'         => '22:00:00',
            'sched_out'        => '06:00:00',
            'sched_end_date'   => '2026-06-17',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-16 22:00:00'));
        $punch = homeAttendance::logTimeIn($this->emp);

        Carbon::setTestNow(Carbon::parse('2026-06-17 06:00:00'));
        $punch->logTimeOut();
        $punch->refresh();

        // 22:00→06:00 = 8h rendered, entire window is night differential.
        $this->assertEquals(8.0, (float) $punch->duration_hours);
        $this->assertEquals(8.0, (float) $punch->night_diff_hours);

        $summary = AttendanceSummary::where('employee_id', $this->emp)
            ->whereDate('attendance_date', '2026-06-16')->first();
        $this->assertNotNull($summary);
        $this->assertEquals(8.0, (float) $summary->total_hours);
        $this->assertSame('present', $summary->status);
    }
}

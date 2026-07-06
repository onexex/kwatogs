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

    /**
     * A REJECTED punch-in must NOT close a prior day's dangling missed logout. The older-day
     * auto-close now runs only once the punch-in is about to commit (after the schedule + break
     * checks pass). So a time-in that fails for "no active schedule" leaves the old record open;
     * the next SUCCESSFUL time-in is what closes it. (Guards the deferred-cleanup reorder.)
     */
    public function test_rejected_timein_does_not_close_prior_missed_logout(): void
    {
        // Dangling open punch from an older day (employee forgot to log out).
        $oldSched = $this->schedule([
            'sched_start_date' => '2026-06-14',
            'sched_end_date'   => '2026-06-14',
        ]); // 08:00–17:00
        $stale = homeAttendance::create([
            'employee_id'     => $this->emp,
            'schedule_id'     => $oldSched->id,
            'attendance_date' => '2026-06-14',
            'time_in'         => '2026-06-14 08:00:00',
            'status'          => 'present',
        ]);

        // No schedule exists for today (06-16) or yesterday (06-15) yet → time-in is rejected.
        Carbon::setTestNow(Carbon::parse('2026-06-16 09:00:00'));

        try {
            homeAttendance::logTimeIn($this->emp);
            $this->fail('Expected time-in to be rejected (no active schedule).');
        } catch (\Exception $e) {
            $this->assertStringContainsString('No active schedule', $e->getMessage());
        }

        // The rejected attempt must not have touched the prior day's dangling punch.
        $stale->refresh();
        $this->assertNull($stale->time_out, 'a rejected punch-in must not close a prior missed logout');
        $this->assertStringNotContainsString('Missed logout', (string) $stale->remarks);

        // Assign today's schedule → the punch-in now SUCCEEDS and only then closes the old one.
        $todaySched = $this->schedule([
            'sched_start_date' => '2026-06-16',
            'sched_end_date'   => '2026-06-16',
        ]);
        $newPunch = homeAttendance::logTimeIn($this->emp);

        $stale->refresh();
        $this->assertNotNull($stale->time_out, 'a successful punch-in closes the prior missed logout');
        $this->assertSame('Auto-closed (Missed logout)', $stale->remarks);
        $this->assertSame($todaySched->id, $newPunch->schedule_id, 'a fresh punch was created for today');
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

    /**
     * Early logout on a wide-window shift must NOT charge the unpaid break as undertime.
     * 6AM–6PM with a 4h break (11:00–15:00) nets 8h. Clocking out at 10:00 works 4h; the
     * uncovered gap 10:00→18:00 is 8h, but 4h of that is the break — so real undertime = 4h,
     * not 8h. (Regression guard for the break-excluded late/undertime fix.)
     */
    public function test_early_logout_excludes_break_from_undertime(): void
    {
        $this->schedule([
            'sched_in'    => '06:00:00',
            'sched_out'   => '18:00:00',
            'break_start' => '11:00:00',
            'break_end'   => '15:00:00',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-16 06:00:00'));
        $punch = homeAttendance::logTimeIn($this->emp);

        Carbon::setTestNow(Carbon::parse('2026-06-16 10:00:00'));
        $punch->logTimeOut();

        $summary = AttendanceSummary::where('employee_id', $this->emp)
            ->whereDate('attendance_date', '2026-06-16')->first();
        $this->assertNotNull($summary);
        $this->assertEquals(4.0, (float) $summary->total_hours, 'worked 06:00–10:00 = 4h');
        $this->assertSame(0, (int) $summary->mins_late, 'on-time in → no late');
        // Gap 10:00→18:00 = 8h, minus the 4h break inside it = 4h undertime (not 8h).
        $this->assertSame(240, (int) $summary->mins_undertime, 'break excluded from undertime');
    }

    /**
     * Night diff for a post-midnight clock-in. On a 22:00–06:00 shift, an employee who
     * clocks in late at 00:00 works 00:00–06:00 (6h − 1h break = 5h), ALL inside the
     * 10PM–6AM window → 5h night diff. Regression guard: the night-window loop must look
     * back one day, else this early-morning slice scores 0 ND.
     */
    public function test_post_midnight_login_gets_night_diff(): void
    {
        $this->schedule([
            'sched_in'       => '22:00:00',
            'sched_out'      => '06:00:00',
            'sched_end_date' => '2026-06-17',
            'break_start'    => '02:00:00',
            'break_end'      => '03:00:00',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-17 00:00:00'));
        $punch = homeAttendance::logTimeIn($this->emp);

        Carbon::setTestNow(Carbon::parse('2026-06-17 06:00:00'));
        $punch->logTimeOut();
        $punch->refresh();

        $this->assertEquals(5.0, (float) $punch->night_diff_hours, '00:00–06:00 less 1h break = 5h ND');

        $summary = AttendanceSummary::where('employee_id', $this->emp)
            ->whereDate('attendance_date', '2026-06-16')->first();
        $this->assertNotNull($summary);
        $this->assertSame(300, (int) $summary->mins_night_diff, '5h night diff (was 0 before fix)');
    }

    /**
     * #1 — Overnight break taken as a gap must count as break, not Outpass. On a 22:00–06:00
     * shift with a 02:00–03:00 break, clocking out 02:00 and back in 03:00 is the scheduled
     * break. Before the fix the over-break window wasn't shifted to the next day, so this
     * 60-min gap was misclassified as out-pass.
     */
    public function test_overnight_break_gap_is_not_outpass(): void
    {
        $sched = $this->schedule([
            'sched_in'       => '22:00:00',
            'sched_out'      => '06:00:00',
            'sched_end_date' => '2026-06-17',
            'break_start'    => '02:00:00',
            'break_end'      => '03:00:00',
        ]);
        homeAttendance::create([
            'employee_id' => $this->emp, 'schedule_id' => $sched->id, 'attendance_date' => '2026-06-16',
            'time_in' => '2026-06-16 22:00:00', 'time_out' => '2026-06-17 02:00:00', 'duration_hours' => 4, 'status' => 'present',
        ]);
        $last = homeAttendance::create([
            'employee_id' => $this->emp, 'schedule_id' => $sched->id, 'attendance_date' => '2026-06-16',
            'time_in' => '2026-06-17 03:00:00', 'time_out' => '2026-06-17 06:00:00', 'duration_hours' => 3, 'status' => 'present',
        ]);
        $last->updateDailySummary();

        $summary = AttendanceSummary::where('employee_id', $this->emp)->whereDate('attendance_date', '2026-06-16')->first();
        $this->assertSame(0, (int) $summary->outpass_minutes, 'scheduled overnight break must not be outpass');
        $this->assertSame(0, (int) $summary->over_break_minutes, '60m break is under the 180m cap');
    }

    /**
     * #2 — The summary must use the PUNCH's schedule, not the date-latest one. A range
     * schedule (the punch's) and a later single-day schedule both cover the day; late must be
     * computed against the punch's shift.
     */
    public function test_summary_uses_punch_schedule_not_date_latest(): void
    {
        $punchSched = EmployeeSchedule::create([
            'employee_id' => $this->emp, 'sched_start_date' => '2026-06-10', 'sched_end_date' => '2026-06-20',
            'sched_in' => '08:00:00', 'sched_out' => '17:00:00', 'break_start' => '12:00:00', 'break_end' => '13:00:00',
        ]);
        EmployeeSchedule::create([ // later start date → would win the date-based fallback
            'employee_id' => $this->emp, 'sched_start_date' => '2026-06-16', 'sched_end_date' => '2026-06-16',
            'sched_in' => '06:00:00', 'sched_out' => '15:00:00', 'break_start' => '10:00:00', 'break_end' => '11:00:00',
        ]);
        $punch = homeAttendance::create([
            'employee_id' => $this->emp, 'schedule_id' => $punchSched->id, 'attendance_date' => '2026-06-16',
            'time_in' => '2026-06-16 08:30:00', 'time_out' => '2026-06-16 17:00:00', 'duration_hours' => 8, 'status' => 'present',
        ]);
        $punch->updateDailySummary();

        $summary = AttendanceSummary::where('employee_id', $this->emp)->whereDate('attendance_date', '2026-06-16')->first();
        // Against the punch's 08:00 shift: 30m late. Against the 06:00 shift it would be 150m.
        $this->assertSame(30, (int) $summary->mins_late, 'late computed against the punch schedule');
    }

    /**
     * #4 — A day with a still-open punch is not final, so no undertime is charged (and a null
     * time_out is never parsed as "now"). Before the fix undertime was measured to the closed
     * morning punch.
     */
    public function test_open_punch_does_not_accrue_undertime(): void
    {
        $sched = $this->schedule(['break_start' => '12:00:00', 'break_end' => '13:00:00']); // 08:00–17:00
        homeAttendance::create([
            'employee_id' => $this->emp, 'schedule_id' => $sched->id, 'attendance_date' => '2026-06-16',
            'time_in' => '2026-06-16 08:00:00', 'time_out' => '2026-06-16 12:00:00', 'duration_hours' => 4, 'status' => 'present',
        ]);
        $open = homeAttendance::create([ // still clocked in
            'employee_id' => $this->emp, 'schedule_id' => $sched->id, 'attendance_date' => '2026-06-16',
            'time_in' => '2026-06-16 13:00:00', 'time_out' => null, 'status' => 'present',
        ]);
        $open->updateDailySummary();

        $summary = AttendanceSummary::where('employee_id', $this->emp)->whereDate('attendance_date', '2026-06-16')->first();
        $this->assertSame(0, (int) $summary->mins_undertime, 'no undertime while a punch is still open');
    }

    /**
     * #6 — When two shift windows both contain "now", time-in picks the one that started most
     * recently (latest schedIn), not whichever the DB returned first.
     */
    public function test_time_in_picks_latest_starting_overlapping_shift(): void
    {
        $early = EmployeeSchedule::create([ // created first; DB-order winner before the fix
            'employee_id' => $this->emp, 'sched_start_date' => '2026-06-16', 'sched_end_date' => '2026-06-16',
            'sched_in' => '06:00:00', 'sched_out' => '15:00:00', 'break_start' => '12:00:00', 'break_end' => '13:00:00',
        ]);
        $later = EmployeeSchedule::create([
            'employee_id' => $this->emp, 'sched_start_date' => '2026-06-16', 'sched_end_date' => '2026-06-16',
            'sched_in' => '08:00:00', 'sched_out' => '17:00:00', 'break_start' => '12:00:00', 'break_end' => '13:00:00',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-06-16 09:00:00')); // inside both windows

        $punch = homeAttendance::logTimeIn($this->emp);

        $this->assertSame($later->id, $punch->schedule_id, 'should match the 08:00 shift, not the 06:00 one');
    }

    // ── Zero-worked-hours day is a full absence, not undertime/late ──────────

    /**
     * A day that nets 0 paid hours must charge NO undertime. Punching 05:37→05:38 (1 min,
     * entirely BEFORE a 06:00–15:00 shift) produces 0 worked hours — a full absence. Before
     * the fix, undertime was measured 05:38→15:00 (less break) ≈ 501 min and stacked on top
     * of the full-day absence deduction (double-charge). Now it stays 0.
     */
    public function test_zero_worked_hours_before_shift_charges_no_undertime_or_late(): void
    {
        $sched = $this->schedule([
            'sched_in'    => '06:00:00',
            'sched_out'   => '15:00:00',
            'break_start' => '12:00:00',
            'break_end'   => '13:00:00',
        ]);
        homeAttendance::create([
            'employee_id' => $this->emp, 'schedule_id' => $sched->id, 'attendance_date' => '2026-06-16',
            'time_in' => '2026-06-16 05:37:00', 'time_out' => '2026-06-16 05:38:00',
            'duration_hours' => 0, 'status' => 'present',
        ])->updateDailySummary();

        $summary = AttendanceSummary::where('employee_id', $this->emp)
            ->whereDate('attendance_date', '2026-06-16')->first();
        $this->assertNotNull($summary);
        $this->assertEquals(0.0, (float) $summary->total_hours, 'punches fell entirely before the shift');
        $this->assertSame(0, (int) $summary->mins_undertime, 'no undertime on a 0-hour absence (was ~501)');
        $this->assertSame(0, (int) $summary->mins_late, 'no late on a 0-hour absence');
        $this->assertSame('absent', $summary->status);
    }

    /**
     * Mirror case for LATE: a brief punch landing entirely AFTER the shift (in 15:30,
     * out 15:31 on a 06:00–15:00 shift) also nets 0 worked hours. Late would otherwise be
     * ~510 min and double-charge the same absent day — it must stay 0.
     */
    public function test_zero_worked_hours_after_shift_charges_no_late(): void
    {
        $sched = $this->schedule([
            'sched_in'    => '06:00:00',
            'sched_out'   => '15:00:00',
            'break_start' => '12:00:00',
            'break_end'   => '13:00:00',
        ]);
        homeAttendance::create([
            'employee_id' => $this->emp, 'schedule_id' => $sched->id, 'attendance_date' => '2026-06-16',
            'time_in' => '2026-06-16 15:30:00', 'time_out' => '2026-06-16 15:31:00',
            'duration_hours' => 0, 'status' => 'present',
        ])->updateDailySummary();

        $summary = AttendanceSummary::where('employee_id', $this->emp)
            ->whereDate('attendance_date', '2026-06-16')->first();
        $this->assertNotNull($summary);
        $this->assertEquals(0.0, (float) $summary->total_hours);
        $this->assertSame(0, (int) $summary->mins_late, 'no late on a 0-hour absence (was ~510)');
        $this->assertSame(0, (int) $summary->mins_undertime);
        $this->assertSame('absent', $summary->status);
    }

    // ── Missed / next-day logout parity (0 paid hours) ──────────────────────

    /**
     * Missed logout closed via the manual next-day Time-OUT must earn 0 paid hours,
     * matching the auto-close-on-next-time-in path (autoCloseMissedLogout). Day shift
     * 08:00–17:00, in Day 1 08:00, out Day 2 09:00 → 0h, remark "Auto-closed (Missed logout)".
     */
    public function test_next_day_manual_logout_is_zeroed_like_autoclose(): void
    {
        $this->schedule(); // 2026-06-16 08:00–17:00

        Carbon::setTestNow(Carbon::parse('2026-06-16 08:00:00'));
        $punch = homeAttendance::logTimeIn($this->emp);

        Carbon::setTestNow(Carbon::parse('2026-06-17 09:00:00')); // forgot to log out; next day
        $punch->logTimeOut();
        $punch->refresh();

        $this->assertEquals(0.0, (float) $punch->duration_hours, 'missed logout earns no paid hours');
        $this->assertEquals(0.0, (float) $punch->night_diff_hours);
        $this->assertSame('Auto-closed (Missed logout)', $punch->remarks);
        // time_out pinned to the scheduled end (2026-06-16 17:00), exactly like autoClose.
        $this->assertSame('2026-06-16 17:00:00', Carbon::parse($punch->time_out)->format('Y-m-d H:i:s'));

        $summary = AttendanceSummary::where('employee_id', $this->emp)
            ->whereDate('attendance_date', '2026-06-16')->first();
        $this->assertNotNull($summary);
        $this->assertEquals(0.0, (float) $summary->total_hours);
    }

    /**
     * Regression guard: a LEGITIMATE overnight shift (22:00–06:00) whose logout lands on the
     * same calendar date as the scheduled end must keep its full hours — it must NOT be
     * mistaken for a missed logout. Out at 06:30 (Day 2) clamps to 06:00 = 8h, not 0.
     */
    public function test_overnight_next_day_logout_retains_hours(): void
    {
        $this->schedule([
            'sched_in'       => '22:00:00',
            'sched_out'      => '06:00:00',
            'sched_end_date' => '2026-06-17',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-16 22:00:00'));
        $punch = homeAttendance::logTimeIn($this->emp);

        Carbon::setTestNow(Carbon::parse('2026-06-17 06:30:00')); // slightly late out, same date as sched_out
        $punch->logTimeOut();
        $punch->refresh();

        $this->assertEquals(8.0, (float) $punch->duration_hours, 'legitimate overnight shift keeps full hours');
        $this->assertEquals(8.0, (float) $punch->night_diff_hours);
        $this->assertStringNotContainsString('Missed logout', (string) $punch->remarks);
    }

    /**
     * Regression guard (the Cacal bug): a DAY shift that ends at 23:00 whose owner clocks out just
     * past midnight is only ~1h late — NOT a missed logout. The old calendar-date check zeroed the
     * whole day because 00:00 (Day 2) fell on a different date than sched_out (Day 1 23:00). With
     * the grace-window fix (6h past sched_out), 00:00 is well inside the window, so the day keeps
     * its clamped hours and the real clock-out time survives.
     * Shift 14:00–23:00 (break 18:00–19:00), in Day 1 19:01, out Day 2 00:00 → 23:00 − 19:01 = 3.98h.
     */
    public function test_day_shift_logout_just_past_midnight_retains_hours(): void
    {
        $this->schedule([
            'sched_start_date' => '2026-07-05',
            'sched_in'         => '14:00:00',
            'sched_out'        => '23:00:00',
            'sched_end_date'   => '2026-07-05',
            'break_start'      => '18:00:00',
            'break_end'        => '19:00:00',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-05 19:01:00'));
        $punch = homeAttendance::logTimeIn($this->emp);

        Carbon::setTestNow(Carbon::parse('2026-07-06 00:00:00')); // 1h past the 23:00 end, next day
        $punch->logTimeOut();
        $punch->refresh();

        // 19:01→23:00 (clamped to sched end) = 3h59m ≈ 3.98h; break 18:00–19:00 is before time-in.
        $this->assertEqualsWithDelta(3.98, (float) $punch->duration_hours, 0.01, 'past-midnight day-shift clock-out keeps clamped hours');
        $this->assertStringNotContainsString('Missed logout', (string) $punch->remarks);
        // Real clock-out is preserved — NOT pinned back to the 23:00 scheduled end.
        $this->assertSame('2026-07-06 00:00:00', Carbon::parse($punch->time_out)->format('Y-m-d H:i:s'));

        $summary = AttendanceSummary::where('employee_id', $this->emp)
            ->whereDate('attendance_date', '2026-07-05')->first();
        $this->assertNotNull($summary);
        $this->assertEqualsWithDelta(3.98, (float) $summary->total_hours, 0.01);
        $this->assertSame('present', $summary->status);
    }
}

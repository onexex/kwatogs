<?php

namespace Tests\Unit;

use App\Models\EmployeeSchedule;
use App\Models\homeAttendance;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit coverage for the punch ("login") evaluation in
 * homeAttendance::evaluatePunch() — the schedule clamp, rendered duration,
 * night-differential and remarks. No database: the model is built in memory
 * and the schedule relation is attached manually.
 *
 * evaluatePunch() is protected, so we invoke it via reflection.
 */
class PunchEvaluationTest extends TestCase
{
    private function attendance(?EmployeeSchedule $schedule = null): homeAttendance
    {
        $att = new homeAttendance();
        $att->setRelation('schedule', $schedule); // null = "No Schedule" path
        return $att;
    }

    /** Invoke the protected evaluatePunch(). */
    private function evaluate(
        homeAttendance $att,
        string $in,
        string $out,
        ?string $schedIn = null,
        ?string $schedOut = null
    ): array {
        $m = new \ReflectionMethod($att, 'evaluatePunch');
        $m->setAccessible(true);
        return $m->invoke(
            $att,
            Carbon::parse($in),
            Carbon::parse($out),
            $schedIn !== null ? Carbon::parse($schedIn) : null,
            $schedOut !== null ? Carbon::parse($schedOut) : null
        );
    }

    private function schedule(string $start, string $in, string $out, ?string $bs = null, ?string $be = null): EmployeeSchedule
    {
        return new EmployeeSchedule([
            'sched_start_date' => $start,
            'sched_in'         => $in,
            'sched_out'        => $out,
            'break_start'      => $bs,
            'break_end'        => $be,
        ]);
    }

    // ── Caller-supplied window (the path logTimeOut() now uses) ─────────────

    /** Day shift: early-in + late-out are both clamped to the schedule. */
    public function test_day_shift_clamps_early_in_and_late_out(): void
    {
        $att = $this->attendance($this->schedule('2026-06-16', '08:00:00', '17:00:00'));
        // In 07:00 (early), out 17:30 (late) → rendered 08:00–17:00 = 9h, no ND (daytime).
        $r = $this->evaluate($att, '2026-06-16 07:00', '2026-06-16 17:30', '2026-06-16 08:00', '2026-06-16 17:00');

        $this->assertSame(9.0, $r['duration_hours']);
        $this->assertSame(0.0, $r['night_diff_hours']);
        $this->assertStringContainsString('Early In', $r['remarks']);
        $this->assertStringContainsString('Late Out', $r['remarks']); // #7: both notes survive
    }

    /** Early logout is honored (not clamped up); on-time in → no "Early/Late" remark. */
    public function test_day_shift_early_logout_is_honored(): void
    {
        $att = $this->attendance($this->schedule('2026-06-16', '08:00:00', '17:00:00'));
        // In 08:00, out 15:00 → 7h rendered.
        $r = $this->evaluate($att, '2026-06-16 08:00', '2026-06-16 15:00', '2026-06-16 08:00', '2026-06-16 17:00');

        $this->assertSame(7.0, $r['duration_hours']);
        $this->assertSame('Regular Shift', $r['remarks']);
    }

    /**
     * Overnight night shift 10PM–6AM: full graveyard window is night differential.
     * This ties the punch clamp and the ND calc together across midnight.
     */
    public function test_overnight_shift_full_night_diff(): void
    {
        $att = $this->attendance($this->schedule('2026-06-16', '22:00:00', '06:00:00'));
        // In 21:30 (early), out 06:30 (late) → rendered 22:00–06:00 = 8h, ND 8h.
        $r = $this->evaluate($att, '2026-06-16 21:30', '2026-06-17 06:30', '2026-06-16 22:00', '2026-06-17 06:00');

        $this->assertSame(8.0, $r['duration_hours']);
        $this->assertSame(8.0, $r['night_diff_hours']);
    }

    /** Overnight shift with an early logout at 3AM → 5 worked hours, all night diff. */
    public function test_overnight_shift_partial(): void
    {
        $att = $this->attendance($this->schedule('2026-06-16', '22:00:00', '06:00:00'));
        $r = $this->evaluate($att, '2026-06-16 22:00', '2026-06-17 03:00', '2026-06-16 22:00', '2026-06-17 06:00');

        $this->assertSame(5.0, $r['duration_hours']);
        $this->assertSame(5.0, $r['night_diff_hours']);
    }

    /** Punch-in after the shift already ended → invalid, zeroed, flagged. */
    public function test_invalid_punch_is_flagged_and_zeroed(): void
    {
        $att = $this->attendance($this->schedule('2026-06-16', '08:00:00', '17:00:00'));
        // In 18:00 (after sched end), out 18:30 → calcIn>calcOut → invalid.
        $r = $this->evaluate($att, '2026-06-16 18:00', '2026-06-16 18:30', '2026-06-16 08:00', '2026-06-16 17:00');

        $this->assertSame(0.0, $r['duration_hours']);
        $this->assertStringContainsString('Invalid', $r['remarks']);
    }

    // ── Fallback derivation (no window passed) ──────────────────────────────

    /**
     * When no window is passed, evaluatePunch derives it from the schedule anchored
     * to sched_start_date (the #3 fix). Overnight schedule must roll sched_out to +1 day.
     */
    public function test_window_is_derived_from_schedule_start_date(): void
    {
        $att = $this->attendance($this->schedule('2026-06-16', '22:00:00', '06:00:00'));
        // No window args → derived 2026-06-16 22:00 to 2026-06-17 06:00.
        $r = $this->evaluate($att, '2026-06-16 22:00', '2026-06-17 06:00');

        $this->assertSame(8.0, $r['duration_hours']);
        $this->assertSame(8.0, $r['night_diff_hours']);
    }

    /** No schedule at all → actual times used verbatim and remark says so. */
    public function test_no_schedule_uses_actual_time(): void
    {
        $att = $this->attendance(null);
        $r = $this->evaluate($att, '2026-06-16 09:00', '2026-06-16 12:00');

        $this->assertSame(3.0, $r['duration_hours']);
        $this->assertSame('No Schedule (Actual Time)', $r['remarks']);
    }
}

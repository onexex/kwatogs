<?php

namespace Tests\Unit;

use App\Models\EmployeeSchedule;
use App\Models\homeAttendance;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit coverage for homeAttendance::calculateNightDiff().
 *
 * Night-differential window = 10:00 PM to 6:00 AM.
 * Any break that overlaps that window is unpaid and must be excluded
 * from the night-differential total ("less the break that falls on the
 * night window", e.g. a break starting 11:00 PM reduces ND).
 *
 * These tests do NOT touch the database: we build the model in memory
 * and attach the schedule relation manually so calculateNightDiff() can
 * read break_start / break_end.
 */
class NightDiffTest extends TestCase
{
    /**
     * Build a homeAttendance whose calculateNightDiff() will see the given
     * break window (or no break when $breakStart is null).
     */
    private function attendance(?string $breakStart = null, ?string $breakEnd = null): homeAttendance
    {
        $att = new homeAttendance();

        $schedule = null;
        if ($breakStart !== null && $breakEnd !== null) {
            $schedule = new EmployeeSchedule([
                'break_start' => $breakStart,
                'break_end'   => $breakEnd,
            ]);
        }

        // setRelation avoids any lazy-load DB query for $this->schedule.
        $att->setRelation('schedule', $schedule);

        return $att;
    }

    /**
     * @dataProvider nightDiffCases
     */
    public function test_night_diff_is_computed_correctly(
        string $label,
        string $in,
        string $out,
        ?string $breakStart,
        ?string $breakEnd,
        float $expected
    ): void {
        $att = $this->attendance($breakStart, $breakEnd);

        $actual = $att->calculateNightDiff(Carbon::parse($in), Carbon::parse($out));

        $this->assertSame(
            $expected,
            $actual,
            "ND mismatch for case: {$label} (expected {$expected}h, got {$actual}h)"
        );
    }

    public static function nightDiffCases(): array
    {
        $D1 = '2026-06-16'; // shift start day
        $D2 = '2026-06-17'; // next day

        return [
            // label, time-in, time-out, break_start, break_end, expected ND hours
            '2pm to 11pm = 1h' => [
                '2pm->11pm', "$D1 14:00", "$D1 23:00", null, null, 1.0,
            ],
            '6pm to 6am with 3h break (11pm-2am) = 5h' => [
                '6pm->6am +3h break', "$D1 18:00", "$D2 06:00", '23:00:00', '02:00:00', 5.0,
            ],
            '6pm to 6am no break = 8h' => [
                '6pm->6am no break', "$D1 18:00", "$D2 06:00", null, null, 8.0,
            ],
            '6pm to 3am = 5h' => [
                '6pm->3am', "$D1 18:00", "$D2 03:00", null, null, 5.0,
            ],
            '8pm to 5am = 7h' => [
                '8pm->5am', "$D1 20:00", "$D2 05:00", null, null, 7.0,
            ],
            '9pm to 6am = 8h' => [
                '9pm->6am', "$D1 21:00", "$D2 06:00", null, null, 8.0,
            ],
            '10pm to 7am = 8h (capped at 6am)' => [
                '10pm->7am', "$D1 22:00", "$D2 07:00", null, null, 8.0,
            ],
            '6pm to 6am with 1h break at 11pm = 7h' => [
                '6pm->6am +1h break@11pm', "$D1 18:00", "$D2 06:00", '23:00:00', '00:00:00', 7.0,
            ],
            // Break wholly AFTER midnight (12AM-1AM) on an overnight shift must still be
            // deducted — regression for the post-midnight anchoring bug.
            '9pm to 6am with 1h break 12am-1am = 7h' => [
                '9pm->6am +1h break@12am', "$D1 21:00", "$D2 06:00", '00:00:00', '01:00:00', 7.0,
            ],
            // Break entirely OUTSIDE the night window must NOT reduce ND.
            '6pm to 6am with lunch break 12pm-1pm = 8h' => [
                '6pm->6am +daytime break', "$D1 18:00", "$D2 06:00", '12:00:00', '13:00:00', 8.0,
            ],
        ];
    }
}

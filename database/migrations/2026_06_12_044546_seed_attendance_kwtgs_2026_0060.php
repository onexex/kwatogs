<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Manual data seed (from attendance sheet) for employee KWTGS-2026-0060.
 *
 * Source columns: Date | Time In | Time Out | late | Total Hours
 * Blank row in the sheet (06/03) is a rest-day gap and is omitted.
 * Columns with no source data are stored as NULL (or their table default).
 */
return new class extends Migration
{
    private string $employeeId = 'KWTGS-2026-0060';

    /**
     * One entry per worked day from the sheet.
     * [ date (Y-m-d), time_in (H:i), time_out (H:i), mins_late, total_hours ]
     */
    private array $rows = [
        ['2026-05-26', '10:02', '21:01', 2,  7.96],
        ['2026-05-27', '09:43', '21:17', 0,  8.00],
        ['2026-05-28', '09:04', '21:00', 0,  8.00],
        ['2026-05-29', '10:22', '21:06', 22, 7.63],
        ['2026-05-30', '08:32', '21:10', 0,  8.00],
        ['2026-05-31', '08:42', '21:05', 0,  8.00],
        ['2026-06-01', '10:29', '21:08', 29, 7.52],
        ['2026-06-02', '09:21', '21:11', 0,  8.00],
        ['2026-06-04', '10:26', '21:05', 26, 7.56],
        ['2026-06-05', '08:41', '21:00', 0,  8.00],
        ['2026-06-06', '09:15', '21:00', 0,  8.00],
        ['2026-06-07', '09:00', '21:01', 0,  8.00],
        ['2026-06-08', '09:46', '21:00', 0,  8.00],
        ['2026-06-09', '09:56', '21:00', 0,  8.00],
        ['2026-06-10', '09:54', '21:00', 0,  8.00],
    ];

    public function up(): void
    {
        $now = Carbon::now();

        foreach ($this->rows as [$date, $timeIn, $timeOut, $minsLate, $totalHours]) {

            // Resolve the schedule covering this date for the employee.
            // schedule_id is NOT NULL; fall back to 0 if no schedule is found.
            $scheduleId = DB::table('employee_schedules')
                ->where('employee_id', $this->employeeId)
                ->whereDate('sched_start_date', '<=', $date)
                ->whereDate('sched_end_date', '>=', $date)
                ->orderByDesc('sched_start_date')
                ->value('id') ?? 0;

            $timeInTs  = $timeIn  ? "{$date} {$timeIn}:00"  : null;
            $timeOutTs = $timeOut ? "{$date} {$timeOut}:00" : null;

            // 1) Raw punch log -> home_attendances
            DB::table('home_attendances')->updateOrInsert(
                [
                    'employee_id'     => $this->employeeId,
                    'attendance_date' => $date,
                ],
                [
                    'schedule_id'      => $scheduleId,
                    'time_in'          => $timeInTs,
                    'time_out'         => $timeOutTs,
                    'duration_hours'   => $totalHours,
                    'night_diff_hours' => null,        // no source data
                    'status'           => 'Present',
                    'remarks'          => null,        // no source data
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]
            );

            // 2) Daily summary -> attendance_summaries (unique: employee_id + attendance_date)
            DB::table('attendance_summaries')->updateOrInsert(
                [
                    'employee_id'     => $this->employeeId,
                    'attendance_date' => $date,
                ],
                [
                    'total_hours'        => $totalHours,
                    'mins_late'          => $minsLate,
                    'mins_undertime'     => 0,
                    'mins_night_diff'    => 0,
                    'status'             => 'Present',
                    'remarks'            => null,       // no source data
                    'over_break_minutes' => 0,
                    'outpass_minutes'    => 0,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $dates = array_column($this->rows, 0);

        DB::table('home_attendances')
            ->where('employee_id', $this->employeeId)
            ->whereIn('attendance_date', $dates)
            ->delete();

        DB::table('attendance_summaries')
            ->where('employee_id', $this->employeeId)
            ->whereIn('attendance_date', $dates)
            ->delete();
    }
};

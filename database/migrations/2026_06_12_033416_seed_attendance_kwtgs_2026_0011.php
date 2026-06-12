<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Manual data seed (from attendance sheet) for employee KWTGS-2026-0011.
 *
 * Source columns: Date | Time In | Time Out | late | Total Hours
 * Blank rows in the sheet (05/29, 06/05) are rest-day gaps and are omitted.
 * Columns with no source data are stored as NULL (or their table default).
 */
return new class extends Migration
{
    private string $employeeId = 'KWTGS-2026-0011';

    /**
     * One entry per worked day from the sheet.
     * [ date (Y-m-d), time_in (H:i), time_out (H:i), mins_late, total_hours ]
     */
    private array $rows = [
        ['2026-05-26', '10:00', '21:09', 0, 8.00],
        ['2026-05-27', '09:52', '21:11', 0, 8.00],
        ['2026-05-28', '09:55', '21:00', 0, 8.00],
        ['2026-05-30', '08:59', '21:10', 0, 8.00],
        ['2026-05-31', '09:00', '21:05', 0, 8.00],
        ['2026-06-01', '09:59', '21:02', 0, 8.00],
        ['2026-06-02', '09:54', '21:11', 0, 8.00],
        ['2026-06-03', '09:59', '21:01', 0, 8.00],
        ['2026-06-04', '09:58', '21:06', 0, 8.00],
        ['2026-06-06', '08:57', '21:00', 0, 8.00],
        ['2026-06-07', '08:56', '21:00', 0, 8.00],
        ['2026-06-08', '09:57', '21:00', 0, 8.00],
        ['2026-06-09', '10:00', '21:00', 0, 8.00],
        ['2026-06-10', '10:02', '21:00', 2, 7.96],
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

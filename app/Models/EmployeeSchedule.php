<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    use HasFactory;

    protected $table = 'employee_schedules';

    protected $fillable = [
        'employee_id',
        'sched_start_date',
        'sched_in',
        'sched_end_date',
        'sched_out',
        'shift_type',
        'break_start',
        'break_end',
        'import_batch_id'
    ];

    /**
     * Company rule: a schedule must net exactly 8 working hours, i.e.
     * (sched_out − sched_in) − break === 8:00. The long window is intentional
     * (see the long-break policy) — the break absorbs whatever brings it to 8.
     */
    public const REQUIRED_NET_MINUTES = 480; // 8h * 60

    /** "HH:MM" (or "HH:MM:SS") → minutes since midnight. */
    public static function toMinutes(string $hhmm): int
    {
        $parts = array_map('intval', explode(':', $hhmm));
        return ($parts[0] ?? 0) * 60 + ($parts[1] ?? 0);
    }

    /**
     * Net working minutes for a shift, with overnight handling identical to the
     * attendance compute engine (AttendanceImportService::computeMetrics,
     * homeAttendance::logTimeOut). Returns null when either break bound is missing
     * (break is required — rule R1).
     */
    public static function netWorkingMinutes(string $schedIn, string $schedOut, ?string $breakStart, ?string $breakEnd): ?int
    {
        if (!$breakStart || !$breakEnd) { return null; }

        $span = self::toMinutes($schedOut) - self::toMinutes($schedIn);
        if ($span <= 0) { $span += 1440; }                       // overnight shift

        $brk = self::toMinutes($breakEnd) - self::toMinutes($breakStart);
        if ($brk <= 0) { $brk += 1440; }                         // break crosses midnight

        return $span - $brk;
    }

    /**
     * Validate a schedule's break against R1 (break required) + R2 (net === 8h).
     * Returns a human-readable error string, or null when the schedule is valid.
     */
    public static function netValidationError(string $schedIn, string $schedOut, ?string $breakStart, ?string $breakEnd): ?string
    {
        if (!$breakStart || !$breakEnd) {
            return 'Break Start and Break End are required.';
        }

        $net = self::netWorkingMinutes($schedIn, $schedOut, $breakStart, $breakEnd);
        if ($net === self::REQUIRED_NET_MINUTES) {
            return null;
        }

        $span = self::toMinutes($schedOut) - self::toMinutes($schedIn);
        if ($span <= 0) { $span += 1440; }
        $brk = self::toMinutes($breakEnd) - self::toMinutes($breakStart);
        if ($brk <= 0) { $brk += 1440; }

        return sprintf(
            'Net working hours must equal 8:00. This shift is %s with a %s break = %s. Adjust the break so (shift − break) = 8 hours.',
            self::fmtHours($span), self::fmtHours($brk), self::fmtHours($net)
        );
    }

    /** Minutes → "8h" / "8h 30m" / "-1h" for validation messages. */
    private static function fmtHours(int $mins): string
    {
        $sign = $mins < 0 ? '-' : '';
        $mins = abs($mins);
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return $sign . $h . 'h' . ($m ? " {$m}m" : '');
    }

    // Schedule belongs to an employee
    public function users()
    {
        return $this->belongsTo(User::class, 'employee_id','empID');
    }

    // Schedule has many home attendances
    public function homeAttendances()
    {
        return $this->hasMany(HomeAttendance::class, 'schedule_id');
    }

    // Optional: Get all attendance summaries for this schedule's employee (by date range)
    public function attendanceSummaries()
    {
        return $this->hasMany(AttendanceSummary::class, 'employee_id', 'employee_id')
                    ->whereBetween('attendance_date', [$this->sched_start_date, $this->sched_end_date]);
    }
}

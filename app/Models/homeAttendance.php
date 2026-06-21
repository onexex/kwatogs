<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class homeAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'employee_id',
        'attendance_date',
        'time_in',
        'time_out',
        'duration_hours',
        'night_diff_hours',
        'status',
        'remarks',
        
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'duration_hours' => 'decimal:2',
        'night_diff_hours' => 'decimal:2',
    ];

    // -----------------------
    // Relationships
    // -----------------------
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'empID');
    }

    public function schedule()
    {
        return $this->belongsTo(EmployeeSchedule::class, 'schedule_id');
    }

    // -----------------------
    // Helpers
    // -----------------------
    public function getTotalHoursAttribute()
    {
        if ($this->time_in && $this->time_out) {
            return round(Carbon::parse($this->time_out)->diffInMinutes(Carbon::parse($this->time_in)) / 60, 2);
        }
        return 0;
    }

    public function isOvernight()
    {
        if (!$this->time_in || !$this->time_out) return false;
        return Carbon::parse($this->time_out)->isAfter(Carbon::parse($this->time_in)->endOfDay());
    }

    public function calculateNightDiff(Carbon $actualIn, Carbon $actualOut)
    {
        $nightDiffMinutes = 0;

        // We start checking from the day of the Clock-In
        $currentDay = $actualIn->copy()->startOfDay();
        // We check until the day of the Clock-Out
        $lastDay = $actualOut->copy()->startOfDay();

        // 🍱 Build the break window (anchored to the shift start date) so we can
        // exclude any break that falls inside the 10PM–6AM window. Break time is
        // unpaid, therefore it must NOT be counted as night differential.
        $breakStart = null;
        $breakEnd   = null;
        if ($this->schedule && $this->schedule->break_start && $this->schedule->break_end) {
            $breakStart = Carbon::parse($actualIn->toDateString() . ' ' . $this->schedule->break_start);
            $breakEnd   = Carbon::parse($actualIn->toDateString() . ' ' . $this->schedule->break_end);
            // Handle overnight break (e.g. 11PM–2AM)
            if ($breakEnd->lt($breakStart)) { $breakEnd->addDay(); }
        }

        while ($currentDay->lte($lastDay)) {
            // Define the Night Window for the current iteration
            // Window: 10 PM (Current Day) to 6 AM (Next Day)
            $nightStart = $currentDay->copy()->setTime(22, 0);
            $nightEnd   = $currentDay->copy()->addDay()->setTime(6, 0);

            // Find the intersection between the Shift and the Night Window
            $intersectStart = $actualIn->gt($nightStart) ? $actualIn : $nightStart;
            $intersectEnd   = $actualOut->lt($nightEnd) ? $actualOut : $nightEnd;

            if ($intersectEnd->gt($intersectStart)) {
                $nightDiffMinutes += $intersectEnd->diffInMinutes($intersectStart);

                // 🌙 Deduct the portion of the break that lands inside this
                // night-window slice (break ∩ night window ∩ worked time).
                if ($breakStart && $breakEnd) {
                    $bStart = $breakStart->gt($intersectStart) ? $breakStart : $intersectStart;
                    $bEnd   = $breakEnd->lt($intersectEnd) ? $breakEnd : $intersectEnd;
                    if ($bEnd->gt($bStart)) {
                        $nightDiffMinutes -= $bEnd->diffInMinutes($bStart);
                    }
                }
            }

            $currentDay->addDay();
        }

        return round(max($nightDiffMinutes, 0) / 60, 2);
    }

    public function updateDailySummary()
    {
        $attendanceDate = $this->attendance_date instanceof Carbon
            ? $this->attendance_date->toDateString()
            : Carbon::parse($this->attendance_date)->toDateString();

        // 1️⃣ Get or create daily summary
        $summary = AttendanceSummary::firstOrCreate([
            'employee_id' => $this->employee_id,
            'attendance_date' => $attendanceDate,
        ]);

        // 2️⃣ Get attendance logs for this exact attendance date only
        $logs = self::where('employee_id', $this->employee_id)
            ->whereDate('attendance_date', $attendanceDate)
            ->get();

        // 3️⃣ Sum total hours and night differential
        $summary->total_hours = round($logs->sum('duration_hours'), 2);
        $summary->mins_night_diff = (int) $logs->sum(fn($log) => $log->night_diff_hours * 60);

        // 4️⃣ Fetch schedule valid for this attendance date
        $schedule = EmployeeSchedule::where('employee_id', $this->employee_id)
            ->whereDate('sched_start_date', '<=', $attendanceDate)
            ->whereDate('sched_end_date', '>=', $attendanceDate)
            ->orderBy('sched_start_date', 'desc')
            ->first();

        if ($schedule && $logs->count()) {
            $firstIn = Carbon::parse($logs->sortBy('time_in')->first()->time_in);
            $lastOut = Carbon::parse($logs->sortByDesc('time_out')->first()->time_out);

            $schedIn = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->sched_in);
            $schedOut = Carbon::parse($schedule->sched_end_date . ' ' . $schedule->sched_out);

            // Overnight adjustment: only if schedule crosses days
            if ($schedOut->lessThanOrEqualTo($schedIn)) {
                $schedOut->addDay();
            }

            // Calculate late minutes
            $summary->mins_late = $firstIn->gt($schedIn)
                ? $schedIn->diffInMinutes($firstIn)
                : 0;

            // Calculate undertime minutes
            $summary->mins_undertime = $lastOut->lt($schedOut)
                ? $lastOut->diffInMinutes($schedOut)
                : 0;
        } else {
            $summary->mins_late = 0;
            $summary->mins_undertime = 0;
        }

        // 5️⃣ --- Over-break & Outpass detection ---
        // Company rule: total break of up to 3 hours (180 min) is allowed;
        // anything beyond that is Over-break. Gaps outside the break period are Outpass.
        $maxBreakMinutes = 180; // 3-hour break cap
        $breakMinutes = 0;
        $outPass = 0;

        if ($schedule && $schedule->break_start && $schedule->break_end) {
            $breakStart = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->break_start);
            $breakEnd = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->break_end);

            // Sort logs by time_in
            $sortedLogs = $logs->sortBy('time_in')->values();

            for ($i = 0; $i < count($sortedLogs) - 1; $i++) {
                $timeOut = Carbon::parse($sortedLogs[$i]->time_out);
                $nextIn = Carbon::parse($sortedLogs[$i + 1]->time_in);
                $gap = $timeOut->diffInMinutes($nextIn);

                // Gap occurs during the break period -> counts as break time
                if ($timeOut->between($breakStart, $breakEnd) || $nextIn->between($breakStart, $breakEnd)) {
                    $breakMinutes += $gap;
                } else {
                    // Gap outside the break period -> Outpass
                    $outPass += $gap;
                }
            }
        }

        // Over-break is only the portion of total break beyond the 3-hour cap.
        $summary->over_break_minutes = max(0, $breakMinutes - $maxBreakMinutes);
        $summary->outpass_minutes = $outPass;

        // 6️⃣ Determine attendance status.
        // Use the SAME lowercase vocabulary as AttendanceImportService ('present'/'absent'/
        // 'ob'/'leave') so the column has one consistent set of values across punch + import.
        $summary->status = $summary->total_hours > 0 ? 'present' : 'absent';

        $summary->save();
    }

    /**
     * Close a punch whose owner never logged out. Sets the time-out to the scheduled
     * end of that shift (falling back to $reference), applies the company "missed logout"
     * penalty (0 paid hours), and rolls the change into the daily summary so the day is
     * never left half-open or unreconciled.
     */
    public function autoCloseMissedLogout(?Carbon $reference = null)
    {
        $reference = $reference ? $reference->copy() : now();

        $schedOut = $reference; // fallback when no schedule is attached
        if ($this->schedule && $this->schedule->sched_out) {
            // attendance_date is cast to a Carbon; normalise to a plain Y-m-d before appending the time.
            $dayStr = Carbon::parse($this->attendance_date)->toDateString();
            $schedOut = Carbon::parse($dayStr . ' ' . $this->schedule->sched_out);
            if ($this->time_in && $schedOut->lt(Carbon::parse($this->time_in))) {
                $schedOut->addDay(); // overnight shift
            }
        }

        $this->time_out        = $schedOut;
        $this->duration_hours  = 0; // company policy: missed logout earns no paid hours
        $this->night_diff_hours = 0;
        $this->remarks         = 'Auto-closed (Missed logout)';
        $this->save();
        $this->updateDailySummary();

        return $this;
    }

    public function logTimeOut($timeOut = null)
    {
        $timeOut = Carbon::parse($timeOut ?: now());
        $this->time_out = $timeOut;

        if (!$this->time_in) {
            $this->duration_hours = 0;
            $this->night_diff_hours = 0;
            $this->remarks = 'Invalid (No time-in found)';
            $this->save();
            $this->updateDailySummary();
            return $this;
        }

        $actualIn = Carbon::parse($this->time_in);
        $actualOut = $timeOut->copy();

        // 🛑 Prevent reversed times
        if ($actualOut->lt($actualIn)) {
            $actualOut = $actualIn->copy();
            $this->time_out = $actualOut;
        }

        // 🧭 1. Retrieve Schedule First
        $schedule = $this->schedule;
        if (!$schedule) {
            $schedule = EmployeeSchedule::where('employee_id', $this->employee_id)
                ->whereDate('sched_start_date', '<=', $actualIn->toDateString())
                ->whereDate('sched_end_date', '>=', $actualIn->toDateString())
                ->first();
        }
        $this->schedule_id = $schedule?->id ?? $this->schedule_id;
        // Keep the in-memory relation in sync with whatever schedule we resolved, so that
        // evaluatePunch() and calculateNightDiff() (which read $this->schedule) operate on
        // the SAME shift this method clamps against — no divergent re-derivation.
        if ($schedule) { $this->setRelation('schedule', $schedule); }

        // ⚡ 2. CLAMP PUNCH TIMES TO SCHEDULE (Core logic change)
        if ($schedule && $schedule->sched_in && $schedule->sched_out) {
            // Anchor the shift window to the schedule's START date (the work day), NOT the
            // punch-in calendar date — these differ for overnight / cross-day punches and
            // must agree with evaluatePunch(), which also anchors to sched_start_date.
            $schedIn = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->sched_in);
            $schedOut = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->sched_out);

            // Handle overnight shift: If sched_out is earlier than sched_in, it's the next day
            if ($schedOut->lt($schedIn)) {
                $schedOut->addDay();
            }

            /**
             * Logic: Rendered time must be BETWEEN SchedIn and SchedOut.
             * If user in at 7am for 8am shift, we use 8am.
             * If user out at 6pm for 5pm shift, we use 5pm.
             */
            $workingIn = $actualIn->gt($schedIn) ? $actualIn : $schedIn;   // Use the later time
            $workingOut = $actualOut->lt($schedOut) ? $actualOut : $schedOut; // Use the earlier time

            // If they clocked out before the shift even started, or in after it ended
            if ($workingIn->gt($workingOut)) {
                $workingIn = $workingOut;
            }

            // 🍱 LUNCH BREAK HANDLING (Inside scheduled time only)
            $breakDuration = 0;
            if ($schedule->break_start && $schedule->break_end) {
                $breakStart = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->break_start);
                $breakEnd = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->break_end);

                // Handle overnight break
                if ($breakEnd->lt($breakStart)) { $breakEnd->addDay(); }

                // Calculate overlap between working hours and break hours
                if ($workingIn->lt($breakEnd) && $workingOut->gt($breakStart)) {
                    $overlapStart = $workingIn->gt($breakStart) ? $workingIn : $breakStart;
                    $overlapEnd = $workingOut->lt($breakEnd) ? $workingOut : $breakEnd;
                    $breakDuration = $overlapEnd->diffInMinutes($overlapStart);
                }
            }

            // Evaluate using the SAME schedule window so night-diff/remarks clamp identically.
            $evaluated = $this->evaluatePunch($workingIn, $workingOut, $schedIn, $schedOut);

            $totalMinutes = ($workingOut->diffInMinutes($workingIn)) - $breakDuration;
            $this->duration_hours = max($totalMinutes / 60, 0);
            $this->night_diff_hours = $evaluated['night_diff_hours'];
            $this->remarks = $evaluated['remarks'];
            // If actual logout is a different day than the scheduled logout
            if ($actualOut->toDateString() !== $schedOut->toDateString() && $actualOut->gt($schedOut)) {
                $this->remarks .= ' [Logout Next Day - Capped]';
            }
        } else {
            // No schedule fallback (Actual time)
            $evaluated = $this->evaluatePunch($actualIn, $actualOut);
            $this->duration_hours = $actualOut->diffInMinutes($actualIn) / 60;
            $this->night_diff_hours = $evaluated['night_diff_hours'];
            $this->remarks = $evaluated['remarks'] . ' (No Schedule)';
        }

        $this->save();
        $this->updateDailySummary();

        return $this;
    }

    // ==============================================================
    //  📌 MAIN LOG TIME-IN FUNCTION (with overnight-safe logic)
    // ==============================================================
    public static function logTimeIn($employeeId)
    {

        $now = now();
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();
    

        // 1️⃣ Auto-close any previous open logs from older days (missed logouts).
        $previousOpenLogs = self::where('employee_id', $employeeId)
            ->whereNull('time_out')
            ->whereDate('attendance_date', '<', $today)
            ->get();

        foreach ($previousOpenLogs as $log) {
            $log->autoCloseMissedLogout($now);
        }

        // 2️⃣ Prevent duplicate open punches.
        // After older-day logs are closed above, any punch still open is from TODAY.
        // A recent one (< 16h) means the employee simply forgot to log out before
        // re-punching → block. A stale one (≥ 16h) is a dangling record → close it so
        // it can't orphan (logTimeOut only ever closes the latest open punch).
        $openPunch = self::where('employee_id', $employeeId)
            ->whereNull('time_out')
            ->latest('time_in')
            ->first();

        if ($openPunch) {
            $lastIn = Carbon::parse($openPunch->time_in);
            if ($now->diffInHours($lastIn) < 16) {
                throw new \Exception('You still have an active punch. Please log out before punching in again.');
            }
            $openPunch->autoCloseMissedLogout($now);
        }

        // 3️⃣ Match an active schedule (supports overnight)
        $bufferMinutes = 60; // Allow 1 hour early login before shift

        $candidates = EmployeeSchedule::where('employee_id', $employeeId)
            ->where(function ($q) use ($today, $yesterday) {
                // Only consider today's and yesterday's schedules
                $q->whereDate('sched_start_date', $today)
                ->orWhereDate('sched_start_date', $yesterday);
            })
            ->get();

        $matchedSchedule = null;

        foreach ($candidates as $s) {
            // Build datetime for schedule in/out
            $schedIn = Carbon::parse($s->sched_start_date . ' ' . $s->sched_in);
            $schedOut = Carbon::parse($s->sched_end_date . ' ' . $s->sched_out);

            // Handle overnight shifts
            if ($schedOut->lessThanOrEqualTo($schedIn)) {
                $schedOut->addDay();
            }

            // Allow time-in from 1 hour before start until actual schedOut (no post-shift)
            $windowStart = $schedIn->copy()->subMinutes($bufferMinutes);
            $windowEnd = $schedOut->copy();

            if ($now->between($windowStart, $windowEnd)) {
                $matchedSchedule = $s;
                break;
            }
        }

        // ── Emergency override ──────────────────────────────────────────
        // If the employee has an APPLIED schedule-change request for today
        // (filed via the Kuya Kwatogs assistant), open the window so they can
        // punch at the requested time even outside the strict shift window.
        if (!$matchedSchedule) {
            $hasEmergency = false;
            try {
                $hasEmergency = \App\Models\ScheduleRequest::where('employee_id', $employeeId)
                    ->whereDate('request_date', $today)
                    ->where('applied', true)
                    ->exists();
            } catch (\Throwable $e) { /* schedule_requests not migrated yet */ }
            if ($hasEmergency) {
                $matchedSchedule = EmployeeSchedule::where('employee_id', $employeeId)
                    ->whereDate('sched_start_date', $today)
                    ->orderBy('sched_start_date', 'desc')
                    ->first();
            }
        }

        if (!$matchedSchedule) {
            throw new \Exception('No active schedule found for your time-in window.');
        }

        // 4️⃣ Check break restriction.
        // Anchor the break window to the matched shift (NOT "today"). For a night shift a
        // break whose clock time is before the shift start (e.g. 02:00 lunch on a 22:00→06:00
        // shift) actually falls on the NEXT day, so shift it forward; then handle a break that
        // itself crosses midnight. Anchoring to "today" mis-dated this for overnight shifts.
        if ($matchedSchedule->break_start && $matchedSchedule->break_end) {
            $shiftIn = Carbon::parse($matchedSchedule->sched_start_date . ' ' . $matchedSchedule->sched_in);
            $breakStart = Carbon::parse($matchedSchedule->sched_start_date . ' ' . $matchedSchedule->break_start);
            $breakEnd = Carbon::parse($matchedSchedule->sched_start_date . ' ' . $matchedSchedule->break_end);
            if ($breakStart->lt($shiftIn)) { $breakStart->addDay(); $breakEnd->addDay(); } // post-midnight break
            if ($breakEnd->lt($breakStart)) { $breakEnd->addDay(); } // break itself crosses midnight
            $earlyReturn = $breakEnd->copy()->subMinutes(10);

            if ($now->between($breakStart, $earlyReturn)) {
                throw new \Exception("You are still on lunch break. Time-in allowed after {$earlyReturn->format('H:i')}.");
            }

            if ($now->between($earlyReturn, $breakEnd)) {
                $now = $breakEnd->copy(); // Snap to break end
            }
        }

        // 5️⃣ Use schedule start as the "work day"
        $attendanceDate = Carbon::parse($matchedSchedule->sched_start_date)->toDateString();

        // 6️⃣ Save new punch (multiple punches allowed)
        return self::create([
            'employee_id'     => $employeeId,
            'schedule_id'     => $matchedSchedule->id,
            'attendance_date' => $attendanceDate,
            'time_in'         => $now,
            'status'          => 'present',
        ]);
    }

    // protected function evaluatePunch($actualIn, $actualOut)
    // {
    //     $result = [
    //         'time_out' => $actualOut,
    //         'duration_hours' => 0,
    //         'night_diff_hours' => 0,
    //         'remarks' => null,
    //     ];

    //     $isInvalid = false;

    //     if ($this->schedule) {
    //         $schedIn = Carbon::parse($this->schedule->sched_start_date . ' ' . $this->schedule->sched_in);
    //         $schedOut = Carbon::parse($this->schedule->sched_end_date . ' ' . $this->schedule->sched_out);

    //         // Overnight adjust
    //         if ($schedOut->lessThanOrEqualTo($schedIn)) {
    //             $schedOut->addDay();
    //         }

    //         // 🛡️ CLAMPING: Only calculate for time WITHIN schedule
    //         // If in at 9PM for 10PM shift, start calc at 10PM.
    //         // If out at 7AM for 6AM shift, end calc at 6AM.
    //         $calcIn = $actualIn->gt($schedIn) ? $actualIn->copy() : $schedIn->copy();
    //         $calcOut = $actualOut->lt($schedOut) ? $actualOut->copy() : $schedOut->copy();

    //         // Safety: If for some reason calcIn is after calcOut (invalid punch)
    //         if ($calcIn->gt($calcOut)) {
    //             $calcIn = $calcOut->copy();
    //         }

    //         // Validation for missing logouts
    //         if ($actualIn->lt($schedIn->copy()->subDay())) {
    //             $isInvalid = true;
    //             $result['remarks'] = 'Auto-closed (Missed logout)';
    //         } elseif ($actualOut->gt($actualIn->copy()->addHours(16))) {
    //             $isInvalid = true;
    //             $result['remarks'] = 'Invalid (Over 16 hours)';
    //         }
    //     } else {
    //         // No schedule fallback
    //         $calcIn = $actualIn->copy();
    //         $calcOut = $actualOut->copy();
            
    //         if ($calcOut->diffInHours($actualIn) > 16) {
    //             $isInvalid = true;
    //             $result['remarks'] = 'Invalid (Over 16 hours)';
    //         }
    //     }

    //     // Compute duration and Night Diff using Clamped Times (calcIn to calcOut)
    //     if (!$isInvalid) {
    //         $result['duration_hours'] = round($calcOut->diffInMinutes($calcIn) / 60, 2);
            
    //         // 🌙 IMPORTANT: We now pass $calcIn and $calcOut to Night Diff calculation
    //         $result['night_diff_hours'] = $this->calculateNightDiff($calcIn, $calcOut);

    //         // Mark remarks if it crossed midnight
    //         if ($calcOut->format('Y-m-d') !== $calcIn->format('Y-m-d')) {
    //             $result['remarks'] = 'Overnight shift';
    //         }
    //     }

    //     $result['time_out'] = $actualOut;

    //     if ($this->schedule) {
    //         $result['attendance_date'] = Carbon::parse($this->schedule->sched_start_date)->toDateString();
    //     }

    //     return $result;
    // }

    /**
     * Evaluate a punch into duration / night-diff / remarks.
     *
     * The schedule window may be supplied by the caller ($schedIn/$schedOut) so this method
     * clamps against the EXACT same shift boundaries logTimeOut() used; when not supplied it
     * derives them from the attached schedule (anchored to sched_start_date).
     */
    protected function evaluatePunch($actualIn, $actualOut, ?Carbon $schedIn = null, ?Carbon $schedOut = null)
    {
        $result = [
            'time_out' => $actualOut,
            'duration_hours' => 0,
            'night_diff_hours' => 0,
            'remarks' => 'Regular Shift',
        ];

        // Derive the window from the schedule only if the caller didn't pass one in.
        if ((!$schedIn || !$schedOut) && $this->schedule) {
            $schedIn = Carbon::parse($this->schedule->sched_start_date . ' ' . $this->schedule->sched_in);
            $schedOut = Carbon::parse($this->schedule->sched_start_date . ' ' . $this->schedule->sched_out);
            if ($schedOut->lt($schedIn)) { $schedOut->addDay(); }
        }

        if ($schedIn && $schedOut) {
            // ✨ HARD CLAMPING (ANTI-ABUSE) ✨
            // Early in (actualIn < schedIn)   -> use schedIn.
            // Early out (actualOut < schedOut) -> use actualOut.
            // Late out (actualOut > schedOut) -> use schedOut.
            $calcIn = ($actualIn->lt($schedIn)) ? $schedIn->copy() : $actualIn->copy();
            $calcOut = ($actualOut->gt($schedOut)) ? $schedOut->copy() : $actualOut->copy();

            // Build a transparent remark without one condition silently clobbering another.
            $notes = [];
            if ($actualIn->lt($schedIn)) { $notes[] = 'Early In (Capped at Sched)'; }
            if ($actualOut->gt($schedOut)) { $notes[] = 'Late Out (Capped at Sched)'; }

            // 🛡️ Safety: invalid punch takes precedence over the cap notes.
            if ($calcIn->gt($calcOut)) {
                $calcIn = $calcOut->copy();
                $result['remarks'] = 'Invalid (Time-in after Time-out)';
            } elseif (!empty($notes)) {
                $result['remarks'] = implode(' + ', $notes);
            }
        } else {
            // Fallback when there is no schedule
            $calcIn = $actualIn;
            $calcOut = $actualOut;
            $result['remarks'] = 'No Schedule (Actual Time)';
        }

        $result['duration_hours'] = round($calcOut->diffInMinutes($calcIn) / 60, 2);
        $result['night_diff_hours'] = $this->calculateNightDiff($calcIn, $calcOut);

        return $result;
    }

   

    



        



    }

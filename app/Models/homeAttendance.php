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

    // public function calculateNightDiff(Carbon $actualIn, Carbon $actualOut)
    // {
    //     $nightDiffMinutes = 0;
    //     $current = $actualIn->copy()->startOfDay();

    //     while ($current->lt($actualOut)) {
    //         $nightStart = $current->copy()->setTime(22, 0); // 10 PM
    //         $nightEnd   = $current->copy()->addDay()->setTime(6, 0); // 6 AM next day

    //         $start = $actualIn->greaterThan($nightStart) ? $actualIn : $nightStart;
    //         $end   = $actualOut->lessThan($nightEnd) ? $actualOut : $nightEnd;

    //         if ($end->gt($start)) {
    //             $nightDiffMinutes += $end->diffInMinutes($start);
    //         }

    //         $current->addDay();
    //     }

    //     return round($nightDiffMinutes / 60, 2);
    // }

    public function calculateNightDiff(Carbon $actualIn, Carbon $actualOut)
    {
        $nightDiffMinutes = 0;
        
        // We start checking from the day of the Clock-In
        $currentDay = $actualIn->copy()->startOfDay();
        // We check until the day of the Clock-Out
        $lastDay = $actualOut->copy()->startOfDay();

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
            }

            $currentDay->addDay();
        }

        return round($nightDiffMinutes / 60, 2);
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

        // 5️⃣ --- NEW: Over-break & Outpass detection ---
        $overBreak = 0;
        $outPass = 0;

        if ($schedule && $schedule->break_start && $schedule->break_end) {
            $breakStart = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->break_start);
            $breakEnd = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->break_end);
            $expectedBreak = $breakEnd->diffInMinutes($breakStart);

            // Sort logs by time_in
            $sortedLogs = $logs->sortBy('time_in')->values();

            for ($i = 0; $i < count($sortedLogs) - 1; $i++) {
                $timeOut = Carbon::parse($sortedLogs[$i]->time_out);
                $nextIn = Carbon::parse($sortedLogs[$i + 1]->time_in);
                $gap = $timeOut->diffInMinutes($nextIn);

                // Gap occurs during break period
                if ($timeOut->between($breakStart, $breakEnd) || $nextIn->between($breakStart, $breakEnd)) {
                    if ($gap > $expectedBreak) {
                        $overBreak += ($gap - $expectedBreak);
                    }
                } else {
                    // Accidental outpass (gap not within break)
                    // if ($gap >= 5) { // optional threshold to ignore very small gaps
                        $outPass += $gap;
                    // }
                }
            }
        }

        $summary->over_break_minutes = $overBreak;
        $summary->outpass_minutes = $outPass;

        // 6️⃣ Determine attendance status
        $summary->status = $summary->total_hours > 0 ? 'Present' : 'Absent';

        $summary->save();
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

    //         // Validation
    //         if ($actualIn->lt($schedIn->copy()->subDay())) {
    //             $isInvalid = true;
    //             $result['remarks'] = 'Auto-closed (Missed logout)';
    //         } elseif ($actualOut->gt($actualIn->copy()->addHours(16))) {
    //             $isInvalid = true;
    //             $result['remarks'] = 'Invalid (Over 16 hours)';
    //         }

    //         // Limit duration calc to schedule end
    //         $calcOut = $actualOut->gt($schedOut) ? $schedOut->copy() : $actualOut->copy();
    //     } else {
    //         $calcOut = $actualOut->copy();
    //         if ($calcOut->diffInHours($actualIn) > 16) {
    //             $isInvalid = true;
    //             $result['remarks'] = 'Invalid (Over 16 hours)';
    //         }
    //     }

    //     // Compute duration
    //     if (!$isInvalid) {
    //         $result['duration_hours'] = round($calcOut->diffInMinutes($actualIn) / 60, 2);
    //         $result['night_diff_hours'] = $this->calculateNightDiff($actualIn, $calcOut);

    //         // Mark overnight
    //         if ($calcOut->isNextDay($actualIn) || $calcOut->lt($actualIn)) {
    //             $result['remarks'] = 'Overnight shift';
    //         }
    //     }

    //     $result['time_out'] = $actualOut;

    //     // Always tie attendance date to schedule start
    //     if ($this->schedule) {
    //         $result['attendance_date'] = Carbon::parse($this->schedule->sched_start_date)->toDateString();
    //     }

    //     return $result;
    // }
    protected function evaluatePunch($actualIn, $actualOut)
    {
        $result = [
            'time_out' => $actualOut,
            'duration_hours' => 0,
            'night_diff_hours' => 0,
            'remarks' => null,
        ];

        $isInvalid = false;

        if ($this->schedule) {
            $schedIn = Carbon::parse($this->schedule->sched_start_date . ' ' . $this->schedule->sched_in);
            $schedOut = Carbon::parse($this->schedule->sched_end_date . ' ' . $this->schedule->sched_out);

            // Overnight adjust
            if ($schedOut->lessThanOrEqualTo($schedIn)) {
                $schedOut->addDay();
            }

            // 🛡️ CLAMPING: Only calculate for time WITHIN schedule
            // If in at 9PM for 10PM shift, start calc at 10PM.
            // If out at 7AM for 6AM shift, end calc at 6AM.
            $calcIn = $actualIn->gt($schedIn) ? $actualIn->copy() : $schedIn->copy();
            $calcOut = $actualOut->lt($schedOut) ? $actualOut->copy() : $schedOut->copy();

            // Safety: If for some reason calcIn is after calcOut (invalid punch)
            if ($calcIn->gt($calcOut)) {
                $calcIn = $calcOut->copy();
            }

            // Validation for missing logouts
            if ($actualIn->lt($schedIn->copy()->subDay())) {
                $isInvalid = true;
                $result['remarks'] = 'Auto-closed (Missed logout)';
            } elseif ($actualOut->gt($actualIn->copy()->addHours(16))) {
                $isInvalid = true;
                $result['remarks'] = 'Invalid (Over 16 hours)';
            }
        } else {
            // No schedule fallback
            $calcIn = $actualIn->copy();
            $calcOut = $actualOut->copy();
            
            if ($calcOut->diffInHours($actualIn) > 16) {
                $isInvalid = true;
                $result['remarks'] = 'Invalid (Over 16 hours)';
            }
        }

        // Compute duration and Night Diff using Clamped Times (calcIn to calcOut)
        if (!$isInvalid) {
            $result['duration_hours'] = round($calcOut->diffInMinutes($calcIn) / 60, 2);
            
            // 🌙 IMPORTANT: We now pass $calcIn and $calcOut to Night Diff calculation
            $result['night_diff_hours'] = $this->calculateNightDiff($calcIn, $calcOut);

            // Mark remarks if it crossed midnight
            if ($calcOut->format('Y-m-d') !== $calcIn->format('Y-m-d')) {
                $result['remarks'] = 'Overnight shift';
            }
        }

        $result['time_out'] = $actualOut;

        if ($this->schedule) {
            $result['attendance_date'] = Carbon::parse($this->schedule->sched_start_date)->toDateString();
        }

        return $result;
    }

    // public function logTimeOut($timeOut = null)
    // {
    //     $timeOut = Carbon::parse($timeOut ?: now());
    //     $this->time_out = $timeOut;

    //     if (!$this->time_in) {
    //         $this->duration_hours = 0;
    //         $this->night_diff_hours = 0;
    //         $this->remarks = 'Invalid (No time-in found)';
    //         $this->save();
    //         $this->updateDailySummary();
    //         return $this;
    //     }

    //     $actualIn = Carbon::parse($this->time_in);
    //     $actualOut = $timeOut->copy();

    //     // Prevent reversed times
    //     if ($actualOut->lt($actualIn)) {
    //         $actualOut = $actualIn->copy();
    //         $this->time_out = $actualOut;
    //     }

    //     // 🔍 Retrieve correct schedule for this punch
    //     $schedule = $this->schedule;
    //         $breakDuration = 0;
    //     //  LUNCH BREAK HANDLING
    //     if ($schedule && $schedule->break_start && $schedule->break_end) {
        
    //         // Build Carbon timestamps for the break period
    //         $breakStart = Carbon::parse($actualIn->toDateString() . ' ' . $schedule->break_start);
    //         $breakEnd = Carbon::parse($actualIn->toDateString() . ' ' . $schedule->break_end);
       
    //         // If employee TIME-OUT happens during break → mark as lunch break
    //         if ($timeOut->between($breakStart, $breakEnd)) {
    //             // $this->time_out = $timeOut;
    //             $this->remarks = 'Lunch Break';    
    //             $this->save();
    //             // return $this;
    //         }
    //         $breakDuration = 0;

    //         if ($actualIn->lte($breakStart) && $timeOut->gte($breakEnd)) {
    //             $breakDuration = $breakEnd->diffInMinutes($breakStart);
    //         } 
    //         // elseif ($actualIn->lte($breakStart) && $timeOut->between($breakStart, $breakEnd)) {
    //         //     $breakDuration = $timeOut->diffInMinutes($breakStart);
    //         // }
    //          elseif ($actualIn->between($breakStart, $breakEnd) && $timeOut->gte($breakEnd)) {
    //             $breakDuration = $breakEnd->diffInMinutes($actualIn);
    //         } elseif ($actualIn->between($breakStart, $breakEnd) && $timeOut->between($breakStart, $breakEnd)) {
    //             $breakDuration = $timeOut->diffInMinutes($actualIn);
    //         } else {
    //             $breakDuration = 0;
    //         }
    //     }

    //     if (!$schedule) {
    //         // Fallback: find schedule based on the IN date or OUT date
    //         $schedule = EmployeeSchedule::where('employee_id', $this->employee_id)
    //             ->where(function ($q) use ($actualIn, $actualOut) {
    //                 $q->whereBetween('sched_start_date', [$actualIn->toDateString(), $actualOut->toDateString()])
    //                 ->orWhereBetween('sched_end_date', [$actualIn->toDateString(), $actualOut->toDateString()]);
    //             })
    //             ->first();
    //     }

    //     // If still no schedule, last resort is today's
    //     if (!$schedule) {
    //         $schedule = EmployeeSchedule::where('employee_id', $this->employee_id)
    //             ->whereDate('sched_start_date', '<=', $actualOut->toDateString())
    //             ->whereDate('sched_end_date', '>=', $actualOut->toDateString())
    //             ->first();
    //     }

    //     $this->schedule_id = $schedule?->id ?? $this->schedule_id;

    //     // 🧮 Evaluate punch (handles overnight or normal)
    //     $evaluated = $this->evaluatePunch($actualIn, $actualOut);

    //     // Convert punch duration to minutes
    //     $totalMinutes = $evaluated['duration_hours'] * 60;

    //     // Deduct lunch break minutes if applicable
    //     if (isset($this->break_minutes) && $this->break_minutes > 0) {
    //         $totalMinutes -= $this->break_minutes;
    //     }
    //     // Deduct break
    //     $totalMinutes -= $breakDuration;

    //     // Prevent negative result
    //     $totalMinutes = max($totalMinutes, 0);

    //     // Recompute into hours
    //     $this->duration_hours = $totalMinutes / 60;
    //     //   $evaluated = $this->evaluatePunch($actualIn, $actualOut);

    //     // $this->duration_hours = $evaluated['duration_hours'];
  
    //     $this->night_diff_hours = $evaluated['night_diff_hours'];
    //     $this->remarks = $evaluated['remarks'];
    //     $this->save();

    //     // ✅ Update summary only if valid or overnight
    //     if (empty($evaluated['remarks']) || str_contains($evaluated['remarks'], 'Overnight')) {
    //         $this->updateDailySummary();
    //     }

    //     return $this;
    // }

    // public function logTimeOut($timeOut = null)
    // {
    //     $timeOut = Carbon::parse($timeOut ?: now());
    //     $this->time_out = $timeOut;

    //     if (!$this->time_in) {
    //         $this->duration_hours = 0;
    //         $this->night_diff_hours = 0;
    //         $this->remarks = 'Invalid (No time-in found)';
    //         $this->save();
    //         $this->updateDailySummary();
    //         return $this;
    //     }

    //     $actualIn = Carbon::parse($this->time_in);
    //     $actualOut = $timeOut->copy();

    //     // 🛑 Prevent reversed times
    //     if ($actualOut->lt($actualIn)) {
    //         $actualOut = $actualIn->copy();
    //         $this->time_out = $actualOut;
    //     }

    //     // 🔍 Retrieve correct schedule for this punch
    //     $schedule = $this->schedule;
    //     $breakDuration = 0;

    //     // 🍱 LUNCH BREAK HANDLING
    //     if ($schedule && $schedule->break_start && $schedule->break_end) {
    //         $breakStart = Carbon::parse($actualIn->toDateString() . ' ' . $schedule->break_start);
    //         $breakEnd = Carbon::parse($actualIn->toDateString() . ' ' . $schedule->break_end);

    //         // If employee TIME-OUT happens during break → mark as lunch break
    //         if ($timeOut->between($breakStart, $breakEnd)) {
    //             $this->remarks = 'Lunch Break';
    //             $this->save();
    //         }

    //         if ($actualIn->lte($breakStart) && $timeOut->gte($breakEnd)) {
    //             $breakDuration = $breakEnd->diffInMinutes($breakStart);
    //         } elseif ($actualIn->between($breakStart, $breakEnd) && $timeOut->gte($breakEnd)) {
    //             $breakDuration = $breakEnd->diffInMinutes($actualIn);
    //         } elseif ($actualIn->between($breakStart, $breakEnd) && $timeOut->between($breakStart, $breakEnd)) {
    //             $breakDuration = $timeOut->diffInMinutes($actualIn);
    //         } else {
    //             $breakDuration = 0;
    //         }
    //     }

    //     // 🧭 If no schedule attached, find one
    //     if (!$schedule) {
    //         $schedule = EmployeeSchedule::where('employee_id', $this->employee_id)
    //             ->where(function ($q) use ($actualIn, $actualOut) {
    //                 $q->whereBetween('sched_start_date', [$actualIn->toDateString(), $actualOut->toDateString()])
    //                     ->orWhereBetween('sched_end_date', [$actualIn->toDateString(), $actualOut->toDateString()]);
    //             })
    //             ->first();
    //     }

    //     // Fallback: today’s schedule
    //     if (!$schedule) {
    //         $schedule = EmployeeSchedule::where('employee_id', $this->employee_id)
    //             ->whereDate('sched_start_date', '<=', $actualOut->toDateString())
    //             ->whereDate('sched_end_date', '>=', $actualOut->toDateString())
    //             ->first();
    //     }

    //     $this->schedule_id = $schedule?->id ?? $this->schedule_id;
    //         // dd($schedule->sched_in);

    //     //  ADJUST if actual time-in is earlier than scheduled time-in
    //     if ($schedule && $schedule->sched_in) {
    //         $schedIn = Carbon::parse($actualIn->toDateString() . ' ' . $schedule->sched_in);

    //         // If employee clocked in before schedule start, adjust from schedIn
    //         if ($actualIn->lt($schedIn) && $actualOut->gte($schedIn)) {
    //             $actualIn = $schedIn->copy();
    //         }
    //     }

    //     //  Evaluate punch (handles overnight or normal)
    //     $evaluated = $this->evaluatePunch($actualIn, $actualOut);

    //     // Convert punch duration to minutes
    //     $totalMinutes = $evaluated['duration_hours'] * 60;

    //     // Deduct lunch break minutes if applicable
    //     if (isset($this->break_minutes) && $this->break_minutes > 0) {
    //         $totalMinutes -= $this->break_minutes;
    //     }

    //     // Deduct computed break duration
    //     $totalMinutes -= $breakDuration;

    //     //  Prevent negative result
    //     $totalMinutes = max($totalMinutes, 0);

    //     // Convert back to hours
    //     $this->duration_hours = $totalMinutes / 60;
    //     $this->night_diff_hours = $evaluated['night_diff_hours'];
    //     $this->remarks = $evaluated['remarks'];
    //     $this->save();

    //     //  Update summary only if valid or overnight
    //     if (empty($evaluated['remarks']) || str_contains($evaluated['remarks'], 'Overnight')) {
    //         $this->updateDailySummary();
    //     }

    //     return $this;
    // }

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

        // ⚡ 2. CLAMP PUNCH TIMES TO SCHEDULE (Core logic change)
        if ($schedule && $schedule->sched_in && $schedule->sched_out) {
            // Construct Schedule Objects (Handling potential overnight shift logic)
            $schedIn = Carbon::parse($actualIn->toDateString() . ' ' . $schedule->sched_in);
            $schedOut = Carbon::parse($actualIn->toDateString() . ' ' . $schedule->sched_out);

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
                $breakStart = Carbon::parse($actualIn->toDateString() . ' ' . $schedule->break_start);
                $breakEnd = Carbon::parse($actualIn->toDateString() . ' ' . $schedule->break_end);

                // Handle overnight break
                if ($breakEnd->lt($breakStart)) { $breakEnd->addDay(); }

                // Calculate overlap between working hours and break hours
                if ($workingIn->lt($breakEnd) && $workingOut->gt($breakStart)) {
                    $overlapStart = $workingIn->gt($breakStart) ? $workingIn : $breakStart;
                    $overlapEnd = $workingOut->lt($breakEnd) ? $workingOut : $breakEnd;
                    $breakDuration = $overlapEnd->diffInMinutes($overlapStart);
                }
            }

            // Evaluate punch using the "clamped" times
            $evaluated = $this->evaluatePunch($workingIn, $workingOut);
            
            $totalMinutes = ($workingOut->diffInMinutes($workingIn)) - $breakDuration;
            $this->duration_hours = max($totalMinutes / 60, 0);
            $this->night_diff_hours = $evaluated['night_diff_hours']; // evaluatePunch should use workingIn/Out
            $this->remarks = $evaluated['remarks'];
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
    

        // 1️⃣ Auto-close any previous open logs from older days
        $previousOpenLogs = self::where('employee_id', $employeeId)
            ->whereNull('time_out')
            ->whereDate('attendance_date', '<', $today)
            ->get();

        foreach ($previousOpenLogs as $log) {
            $evaluated = $log->evaluatePunch(Carbon::parse($log->time_in), $now);
            $log->time_out = $evaluated['time_out'] ?? $now;
            $log->duration_hours = $evaluated['duration_hours'] ?? 0;
            $log->night_diff_hours = $evaluated['night_diff_hours'] ?? 0;
            $log->remarks = $evaluated['remarks'] ?? 'Auto-closed (Missed logout)';
            $log->save();
        }

        // 2️⃣ Prevent duplicate open punches
        $openPunch = self::where('employee_id', $employeeId)
            ->whereNull('time_out')
            ->first();

        if ($openPunch) {
            $lastIn = Carbon::parse($openPunch->time_in);
            if ($now->diffInHours($lastIn) < 16) {
                throw new \Exception('You still have an active punch. Please log out before punching in again.');
            }
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

        if (!$matchedSchedule) {
            throw new \Exception('No active schedule found for your time-in window.');
        }

        // 4️⃣ Check break restriction
        if ($matchedSchedule->break_start && $matchedSchedule->break_end) {
            $breakStart = Carbon::parse($today . ' ' . $matchedSchedule->break_start);
            $breakEnd = Carbon::parse($today . ' ' . $matchedSchedule->break_end);
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
            'status'          => 'Present',
        ]);
    }



        



    }

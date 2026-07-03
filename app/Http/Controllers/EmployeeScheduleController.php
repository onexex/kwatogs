<?php

namespace App\Http\Controllers;

use App\Models\department;
use App\Models\EmployeeSchedule;
use App\Models\homeAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeScheduleController extends Controller
{
    public function index()
    {
        $employees = User::orderBy('lname')->orderBy('fname')->get();
        $departments = department::orderBy('dep_name')->get(['id', 'dep_name']);

        return view('pages.management.empscheduler', compact('employees', 'departments'));
    }
 

    public function getSchedules(Request $request)
    {
        $search = $request->search ?? '';
        $perPage = $request->per_page ?? 10;

        $query = EmployeeSchedule::with('users')
            ->when($search, fn($q) =>
                $q->whereHas('users', fn($e) =>
                    $e->where('fname', 'like', "%$search%")
                    ->orWhere('lname', 'like', "%$search%")
                )
            )
            ->join('users', 'employee_schedules.employee_id', '=', 'users.empID')
            // 1. Group by Year and Month Descending (Newest Month at top)
            ->orderByRaw('YEAR(sched_start_date) DESC')
            ->orderByRaw('MONTH(sched_start_date) DESC')
            // 2. Sort days within that month Ascending (1st, 2nd, 3rd...)
            ->orderBy('sched_start_date', 'asc')
            // 3. Alphabetical Sort
            ->orderBy('users.lname')
            ->orderBy('users.fname')
            ->select('employee_schedules.*');

        $schedules = $query->paginate($perPage);

        $schedules->getCollection()->transform(fn($s) => [
            'id' => $s->id,
            'employee_name' => $s->users->lname . ', ' . $s->users->fname,
            'sched_start_date' => $s->sched_start_date,
            'sched_in' => $s->sched_in,
            'sched_end_date' => $s->sched_end_date,
            'sched_out' => $s->sched_out,
            'shift_type' => $s->shift_type
        ]);

        return response()->json($schedules);
    }


    /**
     * Active employees missing a schedule.
     * Active = emp_details.empStatus = '1' (same rule payroll uses).
     *
     * Modes:
     *  - from AND to given => per-day check: flag anyone missing a schedule on
     *    ANY calendar day in [from, to] (employees can work any day, weekends
     *    included), and report exactly which days are missing.
     *  - only one bound, or both blank => "never scheduled" style: flag anyone
     *    with no schedule row in that open window (blank = no schedule ever).
     */
    public function unscheduled(Request $request)
    {
        $from   = $request->filled('from') ? substr($request->from, 0, 10) : null;
        $to     = $request->filled('to')   ? substr($request->to, 0, 10)   : null;
        $depId  = $request->filled('department_id') ? $request->department_id : null;

        // Active employees only, optionally scoped to one department/company.
        $employees = User::query()
            ->join('emp_details', 'users.empID', '=', 'emp_details.empID')
            ->where('emp_details.empStatus', '1')
            ->when($depId, fn($q) => $q->where('emp_details.empDepID', $depId))
            ->orderBy('users.lname')
            ->orderBy('users.fname')
            ->get(['users.empID', 'users.fname', 'users.lname']);

        // ── Per-day mode (both dates given) ──────────────────────────────
        if ($from && $to) {
            $start = Carbon::parse($from);
            $end   = Carbon::parse($to);
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }

            // Guard against an unreasonably large range.
            if ($start->diffInDays($end) > 366) {
                return response()->json([
                    'error' => 'Please choose a range of one year or less.',
                ], 422);
            }

            // Every calendar day in the range.
            $allDates = [];
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $allDates[] = $d->toDateString();
            }

            // Scheduled rows per employee within the range
            // (each schedule row's working day = sched_start_date).
            $rowsByEmp = DB::table('employee_schedules')
                ->whereDate('sched_start_date', '>=', $start->toDateString())
                ->whereDate('sched_start_date', '<=', $end->toDateString())
                ->orderBy('sched_start_date')
                ->orderBy('sched_in')
                ->get(['employee_id', 'sched_start_date', 'sched_in', 'sched_out', 'shift_type'])
                ->groupBy('employee_id');

            $list = collect();
            foreach ($employees as $e) {
                $empRows = $rowsByEmp[$e->empID] ?? collect();
                $covDates = $empRows->pluck('sched_start_date')
                    ->map(fn($x) => substr($x, 0, 10))->unique()->all();
                $missing = array_values(array_diff($allDates, $covDates));

                if (count($missing) > 0) {
                    // Keep one entry per scheduled day (first shift of the day) for the breakdown.
                    $scheduled = $empRows->groupBy(fn($r) => substr($r->sched_start_date, 0, 10))
                        ->map(fn($dayRows) => [
                            'date'  => substr($dayRows->first()->sched_start_date, 0, 10),
                            'in'    => substr((string) $dayRows->first()->sched_in, 0, 5),
                            'out'   => substr((string) $dayRows->first()->sched_out, 0, 5),
                            'shift' => $dayRows->first()->shift_type,
                        ])->values();

                    $list->push([
                        'empID'         => $e->empID,
                        'name'          => strtoupper($e->lname . ', ' . $e->fname),
                        'missing_count' => count($missing),
                        'missing'       => $missing,
                        'scheduled'     => $scheduled,
                    ]);
                }
            }

            return response()->json([
                'count'      => $list->count(),
                'total_days' => count($allDates),
                'range'      => $allDates,
                'employees'  => $list->values(),
            ]);
        }

        // ── Open-window / never-scheduled mode (blank or one-sided) ──────
        $list = $employees->filter(function ($e) use ($from, $to) {
            $q = DB::table('employee_schedules')
                ->where('employee_id', $e->empID);
            if ($from) {
                $q->whereDate('sched_end_date', '>=', $from);
            }
            if ($to) {
                $q->whereDate('sched_start_date', '<=', $to);
            }
            return !$q->exists();
        })->map(fn($e) => [
            'empID' => $e->empID,
            'name'  => strtoupper($e->lname . ', ' . $e->fname),
        ])->values();

        return response()->json([
            'count'     => $list->count(),
            'employees' => $list,
        ]);
    }


    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'employee_id' => 'required|exists:users,empID',
    //         'sched_start_date' => 'required|date',
    //         'sched_end_date' => 'required|date|after_or_equal:sched_start_date',
    //         'sched_in' => 'required|date_format:H:i',
    //         'sched_out' => 'required|date_format:H:i',
    //         'days' => 'nullable|array',
    //         'shift_type' => 'nullable|string|max:50',
    //         'break_start' => 'required|date_format:H:i',
    //         'break_end' => 'required|date_format:H:i',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $employeeId = $request->employee_id;
    //     $start = Carbon::parse($request->sched_start_date);
    //     $end = Carbon::parse($request->sched_end_date);
    //     $days = $request->days ?? [];
    //     $created = 0;

    //     for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

    //         if (!empty($days) && !in_array($date->format('D'), $days)) {
    //             continue; // Skip days not selected
    //         }

    //         // Compute actual start and end datetime
    //         $startDateTime = Carbon::parse($date->toDateString() . ' ' . $request->sched_in);
    //         $endDateTime = Carbon::parse($date->toDateString() . ' ' . $request->sched_out);

    //         // Overnight shift? Add 1 day to end datetime
    //         if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
    //             $endDateTime->addDay();
    //         }

    //         // Check overlap using full datetime
    //         $overlap = EmployeeSchedule::where('employee_id', $employeeId)
    //             ->where(function($q) use ($startDateTime, $endDateTime) {
    //                 $q->whereRaw(
    //                     "STR_TO_DATE(CONCAT(sched_start_date, ' ', sched_in), '%Y-%m-%d %H:%i') < ? AND STR_TO_DATE(CONCAT(sched_end_date, ' ', sched_out), '%Y-%m-%d %H:%i') > ?",
    //                     [$endDateTime, $startDateTime]
    //                 );
    //             })
    //             ->exists();

    //         if ($overlap) {
    //             return response()->json([
    //                 'error' => "Schedule on {$date->format('Y-m-d')} overlaps with an existing schedule."
    //             ], 409);
    //         }

    //         // Create schedule
    //         EmployeeSchedule::create([
    //             'employee_id' => $employeeId,
    //             'sched_start_date' => $startDateTime->toDateString(),
    //             'sched_end_date' => $endDateTime->toDateString(),
    //             'sched_in' => $startDateTime->format('H:i'),
    //             'sched_out' => $endDateTime->format('H:i'),
    //             'shift_type' => $request->shift_type,
    //             'break_start' => $request->break_start,
    //             'break_end' => $request->break_end,
    //         ]);

    //         $created++;
    //     }

    //     return response()->json(['message' => "$created schedule(s) added successfully!"]);
    // }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sched_start_date' => 'required|date',
            'sched_end_date' => 'required|date|after_or_equal:sched_start_date',
            'sched_in' => 'required|date_format:H:i',
            'sched_out' => 'required|date_format:H:i',
            'days' => 'nullable|array',
            'shift_type' => 'nullable|string|max:50',
            'break_start' => 'required|date_format:H:i',
            'break_end' => 'required|date_format:H:i',
            'employee_ids'     => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // R2: a schedule must net exactly 8 working hours ((shift − break) === 8h).
        if ($netError = EmployeeSchedule::netValidationError($request->sched_in, $request->sched_out, $request->break_start, $request->break_end)) {
            return response()->json(['errors' => ['break_end' => [$netError]]], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($request->employee_ids as $empId) {
                $employeeId = $empId;
                $start = Carbon::parse($request->sched_start_date);
                $end = Carbon::parse($request->sched_end_date);
                $days = $request->days ?? [];
                $created = 0;

                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

                    if (!empty($days) && !in_array($date->format('D'), $days)) {
                        continue;
                    }

                    $startDateTime = Carbon::parse($date->toDateString() . ' ' . $request->sched_in);
                    $endDateTime   = Carbon::parse($date->toDateString() . ' ' . $request->sched_out);

                    if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
                        $endDateTime->addDay();
                    }

                    // Calculate total and net hours
                    $totalHours = $endDateTime->diffInMinutes($startDateTime) / 60;
                    $breakStart = Carbon::parse($date->toDateString() . ' ' . $request->break_start);
                    $breakEnd   = Carbon::parse($date->toDateString() . ' ' . $request->break_end);

                    if ($breakEnd->lessThanOrEqualTo($breakStart)) {
                        $breakEnd->addDay();
                    }

                    $breakHours = $breakEnd->diffInMinutes($breakStart) / 60;
                    $netHours   = $totalHours - $breakHours;

                    // 🚨 Warn if exceeds 9 hours
                    if ($netHours > 9 && !$request->boolean('confirm_long_shift')) {
                        DB::rollBack();
                        return response()->json([
                            'warning' => true,
                            'message' => "Schedule on {$date->format('Y-m-d')} exceeds 9 hours ({$netHours} hrs). Proceed?"
                        ]);
                    }

                    // 🔁 Check overlap
                    $overlap = EmployeeSchedule::where('employee_id', $employeeId)
                        ->whereDate('sched_start_date', '<=', $date)
                        ->whereDate('sched_end_date', '>=', $date)
                        ->whereRaw("
                            TIME(sched_in) < ?
                            AND TIME(sched_out) > ?
                        ", [$endDateTime->format('H:i:s'), $startDateTime->format('H:i:s')])
                        ->exists();

                    if ($overlap) {
               
                        DB::rollBack();
                        return response()->json([
                            'errors' => [
                                'sched_end_date' => [
                                    "Schedule on {$date->format('Y-m-d')} overlaps with an existing schedule for employee ID {$employeeId}."
                                ]
                            ]
                        ], 422);
                    }

                    EmployeeSchedule::create([
                        'employee_id'      => $employeeId,
                        'sched_start_date' => $startDateTime->toDateString(),
                        'sched_end_date'   => $endDateTime->toDateString(),
                        'sched_in'         => $startDateTime->format('H:i'),
                        'sched_out'        => $endDateTime->format('H:i'),
                        'shift_type'       => $request->shift_type,
                        'break_start'      => $request->break_start,
                        'break_end'        => $request->break_end,
                    ]);

                    $created++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedules created successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error'   => 'An unexpected error occurred. All changes have been reverted.',
                'details' => $e->getMessage()
            ], 500);
        }


        return response()->json(['message' => "$created schedule(s) added successfully!"]);
    }




    public function update(Request $request, $id)
    {
        $schedule = EmployeeSchedule::findOrFail($id);

        // Safeguard: once the employee has any attendance tied to this schedule it is
        // LOCKED — editing it would silently diverge already-recorded attendance (late/
        // undertime/night-diff were computed against the old shift) or re-clamp an open punch.
        if ($this->scheduleHasAttendance($schedule)) {
            return response()->json([
                'message' => 'This schedule is locked and can no longer be edited — the employee already has attendance recorded for it.'
            ], 409);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,empID',
            'sched_start_date' => 'required|date',
            'sched_end_date' => 'required|date|after_or_equal:sched_start_date',
            'sched_in' => 'required|date_format:H:i',
            'sched_out' => 'required|date_format:H:i',
            'shift_type' => 'nullable|string|max:50',
            'break_start' => 'required|date_format:H:i',
            'break_end' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // R2: a schedule must net exactly 8 working hours ((shift − break) === 8h).
        if ($netError = EmployeeSchedule::netValidationError($request->sched_in, $request->sched_out, $request->break_start, $request->break_end)) {
            return response()->json(['errors' => ['break_end' => [$netError]]], 422);
        }

        $startDateTime = Carbon::parse($request->sched_start_date . ' ' . $request->sched_in);
        $endDateTime = Carbon::parse($request->sched_end_date . ' ' . $request->sched_out);

        // Overnight shift? Add 1 day to end datetime
        if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
            $endDateTime->addDay();
        }

        // Check overlap excluding current schedule
        $overlap = EmployeeSchedule::where('employee_id', $request->employee_id)
            ->where('id', '!=', $id)
            ->where(function($q) use ($startDateTime, $endDateTime) {
                $q->whereRaw(
                    "STR_TO_DATE(CONCAT(sched_start_date, ' ', sched_in), '%Y-%m-%d %H:%i') < ? AND STR_TO_DATE(CONCAT(sched_end_date, ' ', sched_out), '%Y-%m-%d %H:%i') > ?",
                    [$endDateTime, $startDateTime]
                );
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'error' => "This schedule overlaps with an existing schedule."
            ], 409);
        }

        // Update schedule
        $schedule->update([
            'employee_id' => $request->employee_id,
            'sched_start_date' => $startDateTime->toDateString(),
            'sched_end_date' => $endDateTime->toDateString(),
            'sched_in' => $startDateTime->format('H:i'),
            'sched_out' => $endDateTime->format('H:i'),
            'shift_type' => $request->shift_type,
            'break_start' => $request->break_start,
        'break_end' => $request->break_end,
        ]);

        return response()->json(['message' => 'Schedule updated successfully!']);
    }



    public function destroy($id)
    {
        $schedule = EmployeeSchedule::findOrFail($id);

        // Safeguard: a schedule with attendance tied to it can't be deleted — doing so
        // would orphan the employee's recorded punches (schedule_id has no DB cascade).
        if ($this->scheduleHasAttendance($schedule)) {
            return response()->json([
                'message' => 'This schedule is locked and can no longer be deleted — the employee already has attendance recorded for it.'
            ], 409);
        }

        $schedule->delete();
        return response()->json(['message' => 'Schedule deleted successfully!']);
    }

    /**
     * A schedule is "locked" once the employee has any attendance tied to it — either a
     * punch that references this schedule row, or any punch on a date the schedule covers.
     * After that point, editing or deleting the schedule would diverge or orphan the
     * already-recorded attendance, so both are blocked.
     */
    private function scheduleHasAttendance(EmployeeSchedule $schedule): bool
    {
        return homeAttendance::where('schedule_id', $schedule->id)
            ->orWhere(function ($q) use ($schedule) {
                $q->where('employee_id', $schedule->employee_id)
                  ->whereBetween('attendance_date', [$schedule->sched_start_date, $schedule->sched_end_date]);
            })
            ->exists();
    }

     public function edit($id)
    {
        $schedule = EmployeeSchedule::findOrFail($id);
        return response()->json($schedule);
    }
}

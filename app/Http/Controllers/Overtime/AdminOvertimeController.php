<?php

namespace App\Http\Controllers\Overtime;

use App\Enums\OvertimeStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\empDetail;
use App\Models\EmployeeSchedule;
use App\Models\holidayLoggerModel;
use App\Models\Overtime;
use App\Models\otfiling;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOvertimeController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('adminovertime')) {
            abort(403, 'Unauthorized action.');
        }

        $employees = User::whereHas('empDetail', fn($q) => $q->where('empStatus', '1'))
            ->orderBy('lname')
            ->orderBy('fname')
            ->get();

        $overtimes = Overtime::select('overtimes.*')
            ->join('emp_details', 'emp_details.id', '=', 'overtimes.emp_detail_id')
            ->join('users', 'users.empID', '=', 'emp_details.empID')
            ->where('emp_details.empCompID', Auth::user()->empDetail->empCompID)
            ->with(['employee.user'])
            ->orderByDesc('overtimes.created_at')
            ->paginate(15);

        return view('pages.modules.admin_overtime', compact('employees', 'overtimes'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('adminovertime')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'employee_id' => ['required', 'exists:users,empID'],
            'dateFrom'    => ['required', 'date'],
            'dateTo'      => ['required', 'date', 'after_or_equal:dateFrom'],
            'timeFrom'    => ['required'],
            'timeTo'      => ['required'],
            'purpose'     => ['required', 'string', 'max:255'],
        ]);

        $employee   = User::where('empID', $request->employee_id)->firstOrFail();
        $empDetail  = $employee->empDetail;

        $fromDateTime = Carbon::parse($request->dateFrom . ' ' . $request->timeFrom);
        $toDateTime   = Carbon::parse($request->dateTo   . ' ' . $request->timeTo);

        if ($request->dateFrom !== $request->dateTo) {
            return response()->json(['status' => 'error', 'message' => 'The start and end dates must be the same.']);
        }

        if ($fromDateTime->greaterThanOrEqualTo($toDateTime)) {
            return response()->json(['status' => 'error', 'message' => 'Start time must be earlier than end time.']);
        }

        // OT filing window check
        $otfilling = otfiling::where('comp_id', $empDetail->empCompID)->first();
        if ($otfilling) {
            $now = Carbon::now();

            if ($fromDateTime->greaterThan($now) && $otfilling->filebefore == 0) {
                return response()->json(['status' => 'error', 'message' => 'Filing overtime for future schedules is not allowed for this employee.']);
            }

            if ($fromDateTime->greaterThan($now) && $otfilling->no_days_before > 0) {
                if ($fromDateTime->greaterThan($now->copy()->addDays($otfilling->no_days_before))) {
                    return response()->json(['status' => 'error', 'message' => "You may only file overtime up to {$otfilling->no_days_before} day(s) in advance."]);
                }
            }

            if ($toDateTime->lessThan($now) && $otfilling->fileafter == 0) {
                return response()->json(['status' => 'error', 'message' => 'Filing overtime for past schedules is not allowed for this employee.']);
            }

            if ($toDateTime->lessThan($now) && $otfilling->no_days_after > 0) {
                $minPastDate = $now->copy()->subDays($otfilling->no_days_after);
                if ($toDateTime->lessThan($minPastDate)) {
                    return response()->json(['status' => 'error', 'message' => "You may only file overtime within {$otfilling->no_days_after} day(s) after the work date."]);
                }
            }
        }

        // Schedule overlap check
        $schedules = EmployeeSchedule::where('employee_id', $employee->empID)
            ->whereDate('sched_start_date', '<=', $request->dateTo)
            ->whereDate('sched_end_date', '>=', $request->dateFrom)
            ->get();

        $hasOverlap = $schedules->contains(function ($schedule) use ($fromDateTime, $toDateTime) {
            $schedStart = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->sched_in);
            $schedEnd   = Carbon::parse($schedule->sched_end_date   . ' ' . $schedule->sched_out);
            return $fromDateTime->lt($schedEnd) && $toDateTime->gt($schedStart);
        });

        if ($hasOverlap) {
            return response()->json(['status' => 'error', 'message' => 'Employee has a work schedule that overlaps this time range.']);
        }

        // Duplicate OT check
        $overlapping = Overtime::where('emp_detail_id', $empDetail->id)
            ->where('status', '<>', OvertimeStatusEnum::CANCELED->name)
            ->where(function ($q) use ($fromDateTime, $toDateTime) {
                $q->whereRaw("STR_TO_DATE(CONCAT(date_from,' ',time_in),'%Y-%m-%d %H:%i') < ?", [$toDateTime])
                  ->whereRaw("STR_TO_DATE(CONCAT(date_to,' ',time_out),'%Y-%m-%d %H:%i') > ?", [$fromDateTime]);
            })
            ->exists();

        if ($overlapping) {
            return response()->json(['status' => 'error', 'message' => 'Employee already has an overlapping overtime record.']);
        }

        // Day type
        $isRegularDay = $schedules->contains(function ($schedule) use ($fromDateTime, $toDateTime) {
            $schedStart = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->sched_in);
            $schedEnd   = Carbon::parse($schedule->sched_end_date   . ' ' . $schedule->sched_out);
            return $fromDateTime->between($schedStart, $schedEnd) || $toDateTime->between($schedStart, $schedEnd);
        });

        $day_type   = $isRegularDay ? 'regular' : 'rest_day';
        $totalHours = $toDateTime->floatDiffInHours($fromDateTime);

        $regholiday  = holidayLoggerModel::whereDate('date', $request->dateFrom)->where('type', 0)->count();
        $specholiday = holidayLoggerModel::whereDate('date', $request->dateFrom)->where('type', 1)->count();

        if ($regholiday > 0) {
            $day_type = $regholiday > 1
                ? ($day_type === 'rest_day' ? 'rest_day_double_regular_holiday' : 'double_holiday')
                : ($day_type === 'rest_day' ? 'rest_day_regular_holiday'        : 'regular_holiday');
        }
        if ($specholiday > 0) {
            $day_type = $day_type === 'rest_day' ? 'rest_day_special_holiday' : 'special_holiday';
        }

        $overtimeRate = match ($day_type) {
            'regular'                          => 1.25,
            'rest_day'                         => 1.69,
            'special_holiday'                  => 1.69,
            'regular_holiday'                  => 2.60,
            'rest_day_regular_holiday'         => 3.38,
            'rest_day_special_holiday'         => 1.95,
            'rest_day_double_regular_holiday'  => 3.90,
            'double_holiday'                   => 3.38,
            default                            => 1.25,
        };

        $salary      = $empDetail->getSalaryInfo();
        $hourlyRate  = ($salary['basic'] / 26) / 8;

        if ($totalHours >= 8) {
            $payableHours      = $totalHours - 1;
            $premiumHours      = min($payableHours, 8);
            $excessHours       = max($payableHours - 8, 0);
            $overtimeHourlyPay = ($hourlyRate * $overtimeRate * $premiumHours) + ($hourlyRate * 1.25 * $excessHours);
            $totalHours        = $payableHours;
        } else {
            $overtimeHourlyPay = $hourlyRate * $overtimeRate * $totalHours;
        }

        Overtime::create([
            'emp_detail_id'         => $empDetail->id,
            'status'                => OvertimeStatusEnum::APPROVED->name,
            'date_from'             => $request->dateFrom,
            'date_to'               => $request->dateTo,
            'time_in'               => $request->timeFrom,
            'time_out'              => $request->timeTo,
            'purpose'               => $request->purpose,
            'total_hrs'             => $totalHours,
            'total_pay'             => $overtimeHourlyPay,
            'day_type'              => $day_type,
            'day_type_computation'  => $overtimeRate,
            'hourly_rate'           => $hourlyRate,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Overtime filed successfully for ' . ucwords(strtolower($employee->fname . ' ' . $employee->lname)) . '.']);
    }
}

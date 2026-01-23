<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Overtime;
use Illuminate\Http\Request;
use App\Models\EmployeeSchedule;
use App\Enums\OvertimeStatusEnum;
use App\Models\holidayLoggerModel;
use Illuminate\Support\Facades\Auth;

class OvertimeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $overtimes = Overtime::where('emp_detail_id', $user->empDetail->id)
            ->orderByDesc('created_at')
            ->paginate(10)
            ->through(function ($ot) {
                $from = Carbon::parse($ot->date_from . ' ' . $ot->time_in);
                $to = Carbon::parse($ot->date_to . ' ' . $ot->time_out);

                $hours = $from->diffInHours($to);
                $minutes = $from->diffInMinutes($to) % 60;

                return [
                    'id' => $ot->id,
                    'filing_datetime' => Carbon::parse($ot->created_at)->format('M d, Y h:i A'),
                    'time_in' => $from->format('M d, Y h:i A'),
                    'time_out' => $to->format('M d, Y h:i A'),
                    'purpose' => $ot->purpose,
                    'duration' => sprintf('%d hr %d min', $hours, $minutes),
                    'status' => strtoupper($ot->status ?? 'PENDING'),
                    'status_value' => OvertimeStatusEnum::fromName($ot->status),
                ];
            });
        
        return view('pages.modules.overtime', compact('overtimes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dateFrom' => [
                'required', 'date',
                function ($attribute, $value, $fail) use ($request) {
                    $from = Carbon::parse($request->dateFrom . ' ' . $request->timeFrom);
                    $to   = Carbon::parse($request->dateTo . ' ' . $request->timeTo);

                    if ($request->dateFrom !== $request->dateTo) {
                        $fail('The start and end dates must be the same.');
                    }

                    if ($from->greaterThanOrEqualTo($to)) {
                        $fail('The start date and time must be earlier than the end date and time.');
                    }
                },
            ],
            'dateTo'   => ['required', 'date', 'after_or_equal:dateFrom'],
            'timeFrom' => ['required'],
            'timeTo'   => ['required'],
            'purpose'  => ['required', 'string', 'max:255'],
        ]);

        $fromDateTime = Carbon::parse($request->dateFrom . ' ' . $request->timeFrom);
        $toDateTime   = Carbon::parse($request->dateTo . ' ' . $request->timeTo);
        $user = Auth::user();

        if ($user ) {
            $schedules = EmployeeSchedule::where('employee_id', $user->empID)
                ->whereDate('sched_start_date', '<=', $request->dateTo)
                ->whereDate('sched_end_date', '>=', $request->dateFrom)
                ->get();

            $hasOverlap = $schedules->contains(function ($schedule) use ($fromDateTime, $toDateTime) {
                $schedStart = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->sched_in);
                $schedEnd   = Carbon::parse($schedule->sched_end_date . ' ' . $schedule->sched_out);

                return $fromDateTime->lt($schedEnd) && $toDateTime->gt($schedStart);
            });

            if ($hasOverlap) {
                return back()->withErrors([
                    'dateFrom' => 'You already have a work schedule that overlaps this time range. Overtime is not allowed within scheduled hours.'
                ])->withInput();
            }
            $overlapping = Overtime::where('emp_detail_id', $user->empDetail->id)
                ->where('status', '<>', OvertimeStatusEnum::CANCELED->name)
                ->where(function ($query) use ($fromDateTime, $toDateTime) {
                    $query->where(function ($q) use ($fromDateTime, $toDateTime) {
                        $q->whereRaw("STR_TO_DATE(CONCAT(date_from, ' ', time_in), '%Y-%m-%d %H:%i') < ?", [$toDateTime])
                        ->whereRaw("STR_TO_DATE(CONCAT(date_to, ' ', time_out), '%Y-%m-%d %H:%i') > ?", [$fromDateTime]);
                    });
                })
                ->exists();

            if ($overlapping) {
                return back()->withErrors([
                    'dateFrom' => 'You already have an overtime that overlaps with this date and time range.',
                ])->withInput();
            }

            $isRegularDay = $schedules->contains(function ($schedule) use ($fromDateTime, $toDateTime) {
                $schedStart = Carbon::parse($schedule->sched_start_date . ' ' . $schedule->sched_in);
                $schedEnd   = Carbon::parse($schedule->sched_end_date . ' ' . $schedule->sched_out);

                return $fromDateTime->between($schedStart, $schedEnd)
                    || $toDateTime->between($schedStart, $schedEnd);
            });
            
            $totalHours = $toDateTime->floatDiffInHours($fromDateTime);
            
            $salary = $user->empDetail->getSalaryInfo();
            $empBasic   = $salary['basic'];
            $dailyRate  = $empBasic / 26;
            $hourlyRate = $dailyRate / 8;

            $day_type = '';

            $regholiday = holidayLoggerModel::whereDate('date', $request->dateFrom)
                ->where('type', 0)
                ->count();

            if ( $isRegularDay ) {
                $day_type = 'regular';
            } else {
                $day_type = 'rest_day';
            }

            if (($regholiday) > 0) {
                if ($regholiday > 1) {
                    if ($day_type == 'rest_day') {
                        $day_type = 'rest_day_double_regular_holiday';
                    } else {
                        $day_type = 'double_holiday';
                    }
                } else {
                    if ($day_type == 'rest_day') {
                        $day_type = 'rest_day_regular_holiday';
                    } else {
                        $day_type = 'regular_holiday';
                    }
                }
            }
            
            $specholiday = holidayLoggerModel::whereDate('date', $request->dateFrom)
                ->where('type', 1)
                ->count();

            if ($specholiday > 0) {
                if ($day_type == 'rest_day') {
                    $day_type = 'rest_day_special_holiday';
                } else {
                    $day_type = 'special_holiday';
                }
            }

            $overtimeRate = match ($day_type) {
                'regular' => 1.25,
                'rest_day' => 1.69,
                'special_holiday' => 1.69,
                'regular_holiday' => 2.60,
                'rest_day_regular_holiday' => 3.38,
                'rest_day_special_holiday' => 1.95,
                'rest_day_double_regular_holiday' => 3.90,
                'double_holiday' => 3.38,
                default => 1.25,
            };

            $overtimeHourlyPay = $hourlyRate * $overtimeRate * $totalHours;
            
            $overtime = Overtime::create([
                'emp_detail_id' => $user->empDetail->id,
                'status' => OvertimeStatusEnum::FORAPPROVAL->name,
                'date_from' => $request->dateFrom,
                'date_to' => $request->dateTo,  
                'time_in' => $request->timeFrom,   
                'time_out' => $request->timeTo,   
                'purpose' => $request->purpose,  
                'total_hrs' => $totalHours,
                'total_pay' => $overtimeHourlyPay,
            ]);

            return back()->with('success', 'Overtime filed successfully!');
        }
    }

    public function updateStatus(Overtime $overtime, Request $request) 
    {
        if ($overtime) {
            
            $overtime->status = $request->status;
            $overtime->save();

            return back()->with('success', 'Overtime updated status!');
        }

    }
}

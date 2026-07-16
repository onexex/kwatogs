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
        $user = Auth::user();

        if (!$user->can('adminovertime')) {
            abort(403, 'Unauthorized action.');
        }

        // Managers (spatie admin role, legacy super admin, or anyone who can
        // approve overtime) see the whole company and file pre-approved; a
        // supervisor is scoped to their own department and files FOR APPROVAL.
        // Mirrors the guard/status re-applied in store().
        $isManager = $this->isManager($user);
        $myDepID   = optional($user->empDetail)->empDepID;

        $employees = User::whereHas('empDetail', function ($q) use ($isManager, $myDepID) {
                $q->where('empStatus', '1');
                if (!$isManager) {
                    $q->where('empDepID', $myDepID);
                }
            })
            ->with('empDetail.position')
            ->orderBy('lname')
            ->orderBy('fname')
            ->get();

        // Shared scope for both the list and the stat chips — mirrors the
        // employee dropdown above: managers see the whole workforce, a supervisor
        // is scoped to their own department. Deliberately NOT filtered by the
        // logged-in user's empCompID: admin accounts carry placeholder companies
        // (e.g. 'wedo01') that match no real staff, so a company filter would hide
        // every record — including ones the manager just filed.
        $scoped = fn() => Overtime::query()
            ->join('emp_details', 'emp_details.id', '=', 'overtimes.emp_detail_id')
            ->when(!$isManager, fn($q) => $q->where('emp_details.empDepID', $myDepID));

        $overtimes = $scoped()
            ->select('overtimes.*')
            ->with(['employee.user', 'filedBy'])
            ->orderByDesc('overtimes.created_at')
            ->paginate(15);

        $counts = $scoped()
            ->selectRaw('overtimes.status, COUNT(*) as c')
            ->groupBy('overtimes.status')
            ->pluck('c', 'status');

        $stats = [
            'total'       => (int) $counts->sum(),
            'forapproval' => (int) $counts->get(OvertimeStatusEnum::FORAPPROVAL->name, 0),
            'approved'    => (int) $counts->get(OvertimeStatusEnum::APPROVED->name, 0)
                           + (int) $counts->get(OvertimeStatusEnum::APPROVEDBYCFO->name, 0),
            'disapproved' => (int) $counts->get(OvertimeStatusEnum::DISAPPROVED->name, 0),
        ];

        return view('pages.modules.admin_overtime', compact('employees', 'overtimes', 'isManager', 'stats'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->can('adminovertime')) {
            abort(403, 'Unauthorized action.');
        }

        $isManager = $this->isManager($user);

        $resolved = $this->resolveFiling($request, $user, $isManager);

        if (isset($resolved['error'])) {
            return response()->json(['status' => 'error', 'message' => $resolved['error']]);
        }

        // A manager (admin / super admin / overtime approver) files pre-approved.
        // A supervisor — who holds `adminovertime` only — files FOR APPROVAL,
        // routing the request to Pending Overtime Requests for a real approver.
        $status = $isManager
            ? OvertimeStatusEnum::APPROVED->name
            : OvertimeStatusEnum::FORAPPROVAL->name;

        Overtime::create($resolved['payload'] + [
            'filed_by' => $user->id,
            'status'   => $status,
        ]);

        $employeeName = $this->displayName($resolved['employee']);

        return response()->json([
            'status'  => 'success',
            'message' => $isManager
                ? 'Overtime filed successfully for ' . $employeeName . '.'
                : 'Overtime for ' . $employeeName . ' was submitted for approval.',
        ]);
    }

    /**
     * Edit a filing that is still FOR APPROVAL. Once an approver has acted on it
     * the figures may already have fed payroll, so the record is frozen — the
     * same reasoning as the Summary Logs payroll lock. The whole filing gauntlet
     * is re-run (filing window, schedule overlap, duplicate OT, rate/pay), with
     * this record excluded from its own overlap check.
     */
    public function update(Request $request, Overtime $overtime)
    {
        $user = Auth::user();

        if (!$user->can('adminovertime')) {
            abort(403, 'Unauthorized action.');
        }

        $isManager = $this->isManager($user);

        if ($overtime->status !== OvertimeStatusEnum::FORAPPROVAL->name) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only overtime that is still For Approval can be edited.',
            ]);
        }

        // The filer may correct their own submission; a manager may correct any
        // pending one in their scope. Rows filed through other flows (self-filing
        // or import) carry filed_by = null and are manager-only.
        if (!$isManager && (int) $overtime->filed_by !== (int) $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You may only edit overtime that you filed.',
            ]);
        }

        // The record's CURRENT employee must be in scope too, otherwise a
        // supervisor could edit an out-of-department row by pointing it at one of
        // their own employees.
        if (!$isManager && optional($overtime->employee)->empDepID !== optional($user->empDetail)->empDepID) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You may only edit overtime for employees in your own department.',
            ]);
        }

        $resolved = $this->resolveFiling($request, $user, $isManager, $overtime);

        if (isset($resolved['error'])) {
            return response()->json(['status' => 'error', 'message' => $resolved['error']]);
        }

        // Instance save so Auditable records the before→after diff. Status and
        // filed_by are deliberately untouched — an edit is a correction, not a
        // re-filing, so it stays FOR APPROVAL under the original filer.
        $overtime->fill($resolved['payload'])->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Overtime for ' . $this->displayName($resolved['employee']) . ' was updated.',
        ]);
    }

    /**
     * Shared filing gauntlet for store() and update(): validates the request,
     * enforces the department scope, checks the OT filing window / schedule
     * overlap / duplicate OT, derives the day type + rate, and computes payable
     * hours and pay. Returns ['error' => string] or ['payload' => array,
     * 'employee' => User]. $ignore excludes a record from the duplicate check so
     * an edit doesn't collide with itself.
     */
    private function resolveFiling(Request $request, $user, bool $isManager, ?Overtime $ignore = null): array
    {
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

        if (!$empDetail) {
            return ['error' => 'That employee has no employment record.'];
        }

        // Re-enforce the department scope server-side — the dropdown is not the guard.
        if (!$isManager && $empDetail->empDepID !== optional($user->empDetail)->empDepID) {
            return ['error' => 'You may only file overtime for employees in your own department.'];
        }

        $fromDateTime = Carbon::parse($request->dateFrom . ' ' . $request->timeFrom);
        $toDateTime   = Carbon::parse($request->dateTo   . ' ' . $request->timeTo);

        if ($request->dateFrom !== $request->dateTo) {
            return ['error' => 'The start and end dates must be the same.'];
        }

        if ($fromDateTime->greaterThanOrEqualTo($toDateTime)) {
            return ['error' => 'Start time must be earlier than end time.'];
        }

        // OT filing window check
        $otfilling = otfiling::where('comp_id', $empDetail->empCompID)->first();
        if ($otfilling) {
            $now = Carbon::now();

            if ($fromDateTime->greaterThan($now) && $otfilling->filebefore == 0) {
                return ['error' => 'Filing overtime for future schedules is not allowed for this employee.'];
            }

            if ($fromDateTime->greaterThan($now) && $otfilling->no_days_before > 0) {
                if ($fromDateTime->greaterThan($now->copy()->addDays($otfilling->no_days_before))) {
                    return ['error' => "You may only file overtime up to {$otfilling->no_days_before} day(s) in advance."];
                }
            }

            if ($toDateTime->lessThan($now) && $otfilling->fileafter == 0) {
                return ['error' => 'Filing overtime for past schedules is not allowed for this employee.'];
            }

            if ($toDateTime->lessThan($now) && $otfilling->no_days_after > 0) {
                $minPastDate = $now->copy()->subDays($otfilling->no_days_after);
                if ($toDateTime->lessThan($minPastDate)) {
                    return ['error' => "You may only file overtime within {$otfilling->no_days_after} day(s) after the work date."];
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
            return ['error' => 'Employee has a work schedule that overlaps this time range.'];
        }

        // Duplicate OT check — an edit must not collide with the record being edited.
        $overlapping = Overtime::where('emp_detail_id', $empDetail->id)
            ->where('status', '<>', OvertimeStatusEnum::CANCELED->name)
            ->when($ignore, fn($q) => $q->where('id', '<>', $ignore->id))
            ->where(function ($q) use ($fromDateTime, $toDateTime) {
                $q->whereRaw("STR_TO_DATE(CONCAT(date_from,' ',time_in),'%Y-%m-%d %H:%i') < ?", [$toDateTime])
                  ->whereRaw("STR_TO_DATE(CONCAT(date_to,' ',time_out),'%Y-%m-%d %H:%i') > ?", [$fromDateTime]);
            })
            ->exists();

        if ($overlapping) {
            return ['error' => 'Employee already has an overlapping overtime record.'];
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

        return [
            'employee' => $employee,
            'payload'  => [
                'emp_detail_id'         => $empDetail->id,
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
            ],
        ];
    }

    private function displayName(User $employee): string
    {
        return ucwords(strtolower($employee->fname . ' ' . $employee->lname));
    }

    /**
     * A "manager" sees the whole company and files pre-approved overtime; anyone
     * else with `adminovertime` (a supervisor) is scoped to their own department
     * and files FOR APPROVAL. Managers are the spatie `admin` role, the legacy
     * super admin (`users.role == 1`), or anyone who can approve overtime — the
     * same admin-exemption convention used by Maintenance Mode and the
     * separated-employee block.
     */
    private function isManager($user): bool
    {
        return $user->hasRole('admin')
            || (int) $user->role === 1
            || $user->can('approveovertime');
    }
}

<?php
namespace App\Http\Controllers\Leave;

use Carbon\Carbon;
use App\Models\Leave;
use App\Models\leavetype;
use Illuminate\Http\Request;
use App\Enums\LeaveStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\leavevalidationModel;
use Illuminate\Support\Facades\Auth;
use App\Models\LeaveCreditAllocation;
use App\Models\LeaveDetail;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    public function getAllLeaves()
    {
        $user = Auth::user();
        $leaves = $user->leaves()->with(['leaveType'])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'leaves' => $leaves
        ]); 
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(),[
            'date_from' => 'required',
            'date_to' => 'required',
            'purpose' => 'required',
        ]);

        if(!$validator->passes()){
            return response()->json([
                'status'=>201,
                'error'=>$validator->errors()->toArray()
            ]);
        }

        // Business rule: an employee may only file a leave on a day they have actually
        // logged in (a time-in punch exists for today). No time-in today => the whole
        // application is blocked, regardless of the requested leave dates.
        $hasLoginToday = \App\Models\homeAttendance::where('employee_id', $user->empID)
            ->whereNotNull('time_in')
            ->whereDate('time_in', Carbon::today())
            ->exists();

        if (!$hasLoginToday) {
            return response()->json([
                'status'  => 422,
                'blocked' => true,
                'message' => 'You must be timed-in for today before filing a leave application.',
            ]);
        }

        if (isset($request->halfday)) {
            if ($request->date_from != $request->date_to) {
                return response()->json([
                    'status' => 201,
                    'error' => ['date_to' => ['For half-day leave, Start Date and End Date must be the same.']]
                ]);
            }
        }

        $leavel = Leave::where('employee_id', $user->empID)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->date_from, $request->date_to])
                      ->orWhereBetween('end_date', [$request->date_from, $request->date_to])
                      ->orWhere(function ($query) use ($request) {
                          $query->where('start_date', '<=', $request->date_from)
                                ->where('end_date', '>=', $request->date_to);
                      });
            })
            ->where('status', '!=', LeaveStatusEnum::DISAPPROVED->name)
            ->first();

        if ($leavel) {
            return response()->json([
                'status' => 201,
                'error' => ['date_from' => ['You already have a leave application that overlaps with the selected dates.']]
            ]);
        }

        $halfday = isset($request->halfday) ? (int) $request->halfday : 0;
        $durationHours = $this->computeDurationHours($request->date_from, $request->date_to, $halfday);

        // Determine if this is a paid leave that needs validation
        $autoDisapproveReason = null;

        if ($request->leavekind == 0) {
            $leaveType = leavetype::where('id', $request->leavetype)->first();
            $empDetail = $user->empDetail;

            // Gender check for maternity/paternity leave
            $leaveTypeName = strtolower($leaveType->type_leave ?? '');
            $empGender = $user->employeeInformation->gender ?? null; // 1 = Male, 2 = Female
            if ((str_contains($leaveTypeName, 'maternity') || str_contains($leaveTypeName, 'maternal')) && $empGender != 2) {
                $autoDisapproveReason = 'This leave type is only applicable for female employees.';
            } elseif ((str_contains($leaveTypeName, 'paternity') || str_contains($leaveTypeName, 'paternal')) && $empGender != 1) {
                $autoDisapproveReason = 'This leave type is only applicable for male employees.';
            }

            $leaveValidation = leavevalidationModel::where('leave_type', $leaveType->id)
                ->where('compID', $empDetail->empCompID)
                ->first();

            if (!$autoDisapproveReason && !$leaveValidation) {
                $autoDisapproveReason = 'No leave validation found for this leave type. Contact supervisor.';
            } elseif (!$autoDisapproveReason) {
                // Filing-window (before/after) + minimum-duration rules.
                $autoDisapproveReason = $this->filingWindowReason($leaveValidation, $request->date_from, $request->date_to, $halfday);

                if (!$autoDisapproveReason && !$empDetail->empDateRegular) {
                    $autoDisapproveReason = 'Missing regularization date. Contact supervisor.';
                }

                if (!$autoDisapproveReason) {
                    if ($leaveValidation->pre_allocated == 1) {
                        $preAllocatedLeave = LeaveCreditAllocation::where('employee_id', $user->empID)
                            ->where('leavetype_id', $leaveType->id)
                            ->where('year', now()->year)
                            ->first();

                        if (!$preAllocatedLeave) {
                            $autoDisapproveReason = 'No leave credits allocated for this leave type. Contact supervisor.';
                        } else {
                            $leaveDuration = $halfday ? 0.5 : (Carbon::parse($request->date_to)->diffInDays(Carbon::parse($request->date_from)) + 1);
                            $balance = $preAllocatedLeave->balance;

                            if ($balance < $leaveDuration) {
                                $autoDisapproveReason = 'Insufficient leave balance. Requested ' . $leaveDuration . ' day(s) but only ' . $balance . ' credit(s) remaining.';
                            } else {
                                $leaveApplied = Leave::where('employee_id', $user->empID)
                                    ->where('leave_type', $request->leavetype)
                                    ->where('leave_kind', $request->leavekind)
                                    ->where('status', LeaveStatusEnum::FORAPPROVAL->name)
                                    ->sum('total_hrs');

                                if ($leaveApplied + ($leaveDuration * 8) > $balance * 8) {
                                    $autoDisapproveReason = 'Applying for this leave will exceed your available balance including pending applications.';
                                }
                            }
                        }
                    } else {
                        $yearlyCredit = $leaveValidation->credits;
                        $startDate = Carbon::parse($request->date_from);
                        $leaveDurationDays = Carbon::parse($request->date_to)->diffInDays($startDate) + 1;
                        $monthsElapsed = $startDate->month;
                        $earnedCredits = round(($yearlyCredit / 12) * $monthsElapsed, 2);

                        $usedHours = Leave::where('employee_id', $user->empID)
                            ->where('leave_type', $request->leavetype)
                            ->where('leave_kind', $request->leavekind)
                            ->where('status', '!=', LeaveStatusEnum::DISAPPROVED->name)
                            ->sum('total_hrs');

                        $remainingCredits = $earnedCredits - ($usedHours / 8);

                        if ($remainingCredits <= 0) {
                            $autoDisapproveReason = 'Insufficient leave balance. No credits remaining for this period.';
                        } elseif ($leaveDurationDays > $remainingCredits) {
                            $autoDisapproveReason = 'Insufficient leave balance. Requested ' . $leaveDurationDays . ' day(s) but only ' . $remainingCredits . ' credit(s) earned so far.';
                        }
                    }
                }
            }
        } elseif ($request->leavekind == 1) {
            // Unpaid leave: enforce the same filing-window (before/after) and minimum-duration
            // rules as paid leave, but WITHOUT the leave-credit/balance and regularization gates
            // (unpaid leave consumes no credits). Rules apply only when a validation row exists;
            // with no config there is nothing to enforce, so the request is allowed through.
            $leaveType = leavetype::where('id', $request->leavetype)->first();

            $leaveValidation = $leaveType
                ? leavevalidationModel::where('leave_type', $leaveType->id)
                    ->where('compID', $user->empDetail->empCompID)
                    ->first()
                : null;

            if ($leaveValidation) {
                $autoDisapproveReason = $this->filingWindowReason($leaveValidation, $request->date_from, $request->date_to, $halfday);
            }
        }

        $status = $autoDisapproveReason ? LeaveStatusEnum::DISAPPROVED->name : LeaveStatusEnum::FORAPPROVAL->name;

        $leaveRecord = Leave::create([
            'employee_id'         => $user->empID,
            'start_date'          => $request->date_from,
            'end_date'            => $request->date_to,
            'leave_type'          => $request->leavetype,
            'total_hrs'           => $durationHours,
            'reason'              => $request->purpose,
            'status'              => $status,
            'is_half_day'         => $halfday,
            'leave_kind'          => $request->leavekind,
            'disapproved_remarks' => $autoDisapproveReason,
        ]);

        $dateFrom = Carbon::parse($request->date_from);
        $dateTo   = Carbon::parse($request->date_to);

        while ($dateFrom->lte($dateTo)) {
            $detailHours = ($halfday && $request->date_from == $request->date_to) ? 4 : 8;

            LeaveDetail::create([
                'employee_id' => $user->empID,
                'leave_id'    => $leaveRecord->id,
                'leavetype_id'=> $request->leavetype,
                'date'        => $dateFrom->format('Y-m-d'),
                'leave_kind'  => $request->leavekind,
                'total_hours' => $detailHours,
                'status'      => $status,
            ]);

            $dateFrom->addDay();
        }

        if ($autoDisapproveReason) {
            return response()->json([
                'status'  => 200,
                'auto_disapproved' => true,
                'message' => 'Your leave application was automatically disapproved: ' . $autoDisapproveReason,
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Leave application submitted successfully.',
        ]);
    }

    private function computeDurationHours(string $dateFrom, string $dateTo, int $halfday): int
    {
        if ($halfday) {
            return 4;
        }

        $diffDays = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1;
        return $diffDays * 8;
    }

    /**
     * Filing-window + minimum-duration validation shared by paid and unpaid leave.
     *
     * Checks, in order: file-before window (must file at least N days in advance / may not
     * file in advance), file-after window (must file within N days after / may not file after
     * the fact), and the leave type's minimum number of days per application (min_leave).
     * Deliberately excludes any leave-credit/balance logic. Returns the disapproval reason
     * string, or null when the request satisfies all configured rules.
     */
    private function filingWindowReason(leavevalidationModel $leaveValidation, string $dateFrom, string $dateTo, int $halfday): ?string
    {
        $today = Carbon::now()->startOfDay();
        $targetDate = Carbon::parse($dateFrom);
        $diff = $today->diffInDays($targetDate, false);
        $daysAfter = $diff < 0 ? abs($diff) : 0;
        $daysBefore = $diff > 0 ? $diff : 0;

        if ($leaveValidation->file_before == 1 && $leaveValidation->no_before_file > 0) {
            if ($daysBefore > $leaveValidation->no_before_file) {
                return 'This leave type requires filing at least ' . $leaveValidation->no_before_file . ' day(s) before the leave start date.';
            }
        } elseif ($leaveValidation->file_before == 0 && $daysBefore > 0) {
            return 'This leave type cannot be filed in advance.';
        }

        if ($leaveValidation->file_after == 1 && $leaveValidation->no_after_file > 0) {
            if ($daysAfter > $leaveValidation->no_after_file) {
                return 'This leave type requires filing within ' . $leaveValidation->no_after_file . ' day(s) after the leave date.';
            }
        } elseif ($leaveValidation->file_after == 0 && $daysAfter > 0) {
            return 'This leave type cannot be filed after the leave has occurred.';
        }

        if ($leaveValidation->min_leave > 0) {
            $leaveDurationDays = $halfday ? 0.5 : (Carbon::parse($dateTo)->diffInDays(Carbon::parse($dateFrom)) + 1);
            if ($leaveDurationDays < $leaveValidation->min_leave) {
                return 'This leave type requires a minimum of ' . $leaveValidation->min_leave . ' day(s) per application.';
            }
        }

        return null;
    }

    public function checkLeaveCredit(Request $request)
    {
        $user = Auth::user();
        $leave = leavetype::where('id', $request->leave_id)
            ->first();

        if ($leave) {
            $leaveValidation = leavevalidationModel::where('leave_type', $leave->id)
                ->where('compID', $user->empDetail->empCompID)
                ->first();
            $empDetail = $user->empDetail;
            if ($leaveValidation) {
                if (!$empDetail->empDateRegular) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Missing Regularization Date'
                    ]);
                }
                if ($leaveValidation->pre_allocated == 1) {
                    $preAllocatedLeave = LeaveCreditAllocation::where('employee_id', $user->empID)
                        ->where('leavetype_id', $leave->id)
                        ->where('year', now()->year)
                        ->first();

                    if ($preAllocatedLeave) {
                         return response()->json([
                            'leave_credit' =>  $preAllocatedLeave->credits_allocated
                        ]);
                    } else {
                        return response()->json([
                            'status' => 404,
                            'message' => 'Leave credit missing. Contact supervisor.'
                        ]);
                    }
                } else {

                    $yearlyCredit = $leaveValidation->credits; 
                    $monthsElapsed = now()->month; 

                    $monthlyAccrual = $yearlyCredit / 12;

                    $usedHours = Leave::where('employee_id', $user->empID)
                        ->where('leave_type', $request->leave_id)
                        ->where('leave_kind', 0)
                        ->where('status', '!=',  LeaveStatusEnum::DISAPPROVED->name)
                        ->whereDate('start_date', '<=', now())
                        ->whereYear('start_date', now()->year)
                        ->sum('total_hrs');

                    return response()->json([
                        'leave_credit' =>  round($monthlyAccrual * $monthsElapsed - $usedHours / 8, 2) 
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'No Leave Validation yet'
                ]);
            }
        }
    }

    public function destroy(Leave $leave)
    {
        if ($leave->status != LeaveStatusEnum::FORAPPROVAL->name) {
            return response()->json([
                'status' => 400,
                'message' => 'Only leaves with status "FOR APPROVAL" can be deleted.'
            ]);
        }

        $leaveDetails = LeaveDetail::where('leave_id', $leave->id)->get();
        foreach ($leaveDetails as $detail) {
            $detail->delete();
        }
        $leave->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Leave deleted successfully.'
        ]);
    }
}

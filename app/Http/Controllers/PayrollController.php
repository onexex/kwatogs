<?php
namespace App\Http\Controllers;

use App\Helpers\ContributionHelper;
use App\Models\AttendanceSummary;
use App\Models\empDetail;
use App\Models\EmployeeSchedule;
use App\Models\holidayLoggerModel;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\OB;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollLog;
use App\Models\SssContribution;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
 
class PayrollController extends Controller
{


    public function fetchPayroll(Request $request)
    {
        try {
            $request->validate([
                'date_from'         => 'nullable|date',
                'date_to'           => 'nullable|date',
                'payDate'           => 'nullable|date',
                'company_id'        => 'nullable',
                'classification_id' => 'nullable',
                'department_id'     => 'nullable',
            ]);

            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');
            $pay_date = $request->query('payDate');

            // ✨ Saluhin ang bagong parameters ✨
            $companyId = $request->query('company_id', 'all') ?: 'all';
            $classificationId = $request->query('classification_id', 'all') ?: 'all';
            $departmentId = $request->query('department_id', 'all') ?: 'all';

            // Ginagamit ang 'users' table base sa iyong User::class relation
            // ✨ Isinama ang 'employee.empDetail' para maka-access tayo sa classification at company ✨
            $query = Payroll::with(['employee.empDetail'])
                ->join('users', 'payrolls.employee_id', '=', 'users.empID')
                ->select('payrolls.*');

            // ✨ FIX: Ihiwalay ang Pay Date filter para laging mag-trigger ✨
            if (!empty($pay_date)) {
                $query->where('payrolls.pay_date', $pay_date);
            }

            // ✨ OPTIONAL: Kung gusto mo rin mag-filter gamit ang cut-off dates ✨
            if (!empty($dateFrom) && !empty($dateTo)) {
                $query->where('payrolls.payroll_start_date', '>=', $dateFrom)
                      ->where('payrolls.payroll_end_date', '<=', $dateTo);
            }

            // ✨ I-FILTER BASE SA COMPANY, CLASSIFICATION AT DEPARTMENT ✨
            if ($companyId !== 'all' || $classificationId !== 'all' || $departmentId !== 'all') {
                $query->whereHas('employee.empDetail', function ($q) use ($companyId, $classificationId, $departmentId) {

                    if ($companyId !== 'all') {
                        $q->where('empCompID', $companyId);
                    }

                    if ($classificationId !== 'all') {
                        $q->where('empClassification', $classificationId);
                    }

                    if ($departmentId !== 'all') {
                        $q->where('empDepID', $departmentId);
                    }
                });
            }

            // Pwede mo na i-order gamit ang columns mula sa users table
            $payrolls = $query->orderBy('users.fname', 'asc')
                            ->orderBy('users.lname', 'asc')
                            ->get();

            return response()->json($payrolls);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Invalid filter values.',
                'error'   => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Fetch Payroll Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payroll records.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Late/undertime bracket rounding on the PERIOD TOTAL (company policy):
     *   1..30 min  => 0.5 hr ; 31..59 min => 1.0 hr (per hour block).
     */
    private function lateBracketHours($mins): float
    {
        $mins = (int) $mins;
        if ($mins <= 0) return 0.0;
        $full = intdiv($mins, 60);
        $rem  = $mins % 60;
        if ($rem === 0) return (float) $full;
        return $full + ($rem <= 30 ? 0.5 : 1.0);
    }

    public function computePayroll(Request $request)
    {
        $employees = collect();
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'pay_date' => 'required|date',
                'department_id' => 'nullable',
                'company_id' => 'nullable',
            ]);

            $startDate = $validated['date_from'];
            $endDate = $validated['date_to'];
            $payDate = $validated['pay_date'];

            // 'all' = compute every active employee; otherwise limit to one department
            $departmentId = $request->query('department_id', 'all') ?: 'all';
            $companyId    = $request->query('company_id', 'all') ?: 'all';

            //  Fetch Active Employees (optionally filtered by department)
            $employees = User::with(['empDetail', 'empDetail.department'])
                ->whereHas('empDetail', function ($q) use ($departmentId, $companyId) {
                    $q->where('empStatus', '1');
                    if ($departmentId !== 'all') {
                        $q->where('empDepID', $departmentId);
                    }
                    if ($companyId !== 'all') {
                        $q->where('empCompID', $companyId);
                    }
                })
                ->get();

            // IDs being processed in this run. Used to scope cleanup so computing a
            // single department never deletes payroll belonging to other departments.
            $employeeIds = $employees->pluck('empID');

            // ==============================
            //  PRE-GENERATION VALIDATION (runs first; no records written on failure)
            //  Cancel if any OT/Leave inside the cut-off is still pending approval
            //  (FOR APPROVAL or APPROVED-awaiting-CFO).
            // ==============================
            $empDetailIds = $employees->map(fn($e) => optional($e->empDetail)->id)->filter()->values();
            $pendingStatuses = ['FORAPPROVAL', 'APPROVED'];
            $issues = [];
            $statusLabel = fn($s) => $s === 'FORAPPROVAL' ? 'For Approval' : 'Approved (awaiting CFO)';

            $pendingOt = DB::table('overtimes as o')
                ->join('emp_details as ed', 'ed.id', '=', 'o.emp_detail_id')
                ->join('users as u', 'u.empID', '=', 'ed.empID')
                ->whereIn('o.emp_detail_id', $empDetailIds)
                ->whereIn('o.status', $pendingStatuses)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('o.date_from', [$startDate, $endDate])
                      ->orWhereBetween('o.date_to', [$startDate, $endDate]);
                })
                ->selectRaw("o.date_from, o.date_to, o.total_hrs, o.status, u.empID as employee_id, TRIM(CONCAT(COALESCE(u.lname,''),', ',COALESCE(u.fname,''))) as employee_name")
                ->orderBy('employee_name')->get();
            foreach ($pendingOt as $r) {
                $issues[] = [
                    'employee_id'   => $r->employee_id,
                    'employee_name' => strtoupper(trim($r->employee_name)),
                    'type'          => 'Overtime',
                    'period'        => Carbon::parse($r->date_from)->format('M d') . ' - ' . Carbon::parse($r->date_to)->format('M d, Y'),
                    'detail'        => number_format((float) $r->total_hrs, 2) . ' hr(s)',
                    'status'        => $statusLabel($r->status),
                ];
            }

            $pendingLeave = DB::table('leaves as l')
                ->join('users as u', 'u.empID', '=', 'l.employee_id')
                ->leftJoin('leavetypes as lt', 'lt.id', '=', 'l.leave_type')
                ->whereIn('l.employee_id', $employeeIds)
                ->whereIn('l.status', $pendingStatuses)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('l.start_date', [$startDate, $endDate])
                      ->orWhereBetween('l.end_date', [$startDate, $endDate]);
                })
                ->selectRaw("l.start_date, l.end_date, l.status, lt.type_leave as leave_type, u.empID as employee_id, TRIM(CONCAT(COALESCE(u.lname,''),', ',COALESCE(u.fname,''))) as employee_name")
                ->orderBy('employee_name')->get();
            foreach ($pendingLeave as $r) {
                $issues[] = [
                    'employee_id'   => $r->employee_id,
                    'employee_name' => strtoupper(trim($r->employee_name)),
                    'type'          => 'Leave',
                    'period'        => Carbon::parse($r->start_date)->format('M d') . ' - ' . Carbon::parse($r->end_date)->format('M d, Y'),
                    'detail'        => $r->leave_type ?? 'Leave',
                    'status'        => $statusLabel($r->status),
                ];
            }

            if (count($issues) > 0) {
                DB::rollBack(); // nothing written: no payroll, no payroll_logs
                Log::channel('payroll')->warning('Payroll generation CANCELLED - pending approvals', [
                    'pay_date' => $payDate, 'count' => count($issues),
                ]);
                return response()->json([
                    'status'     => 'error',
                    'validation' => 'pending_approvals',
                    'message'    => 'Payroll generation was cancelled. Resolve the pending overtime / leave below first.',
                    'issues'     => $issues,
                ], 422);
            }

            Log::channel('payroll')->info('=== Payroll run START ===', [
                'pay_date'   => $payDate,
                'cutoff'     => $startDate.' to '.$endDate,
                'department' => $departmentId,
                'employees'  => $employeeIds->count(),
            ]);

            // ==============================
            //  CLEANUP OLD PAYROLL RECORDS
            // ==============================
            $existingPayrolls = Payroll::where('pay_date', $payDate)
                ->whereIn('employee_id', $employeeIds)
                ->get();
            foreach ($existingPayrolls as $oldPayroll) {

                //  Roll back previous loan payments
                $loanPayments = LoanPayment::where('payroll_id', $oldPayroll->id)->get();
                foreach ($loanPayments as $payment) {
                    $loan = Loan::find($payment->loan_id);
                    if ($loan) {
                        $loan->balance += $payment->amount_paid;
                        if ($loan->balance > 0) $loan->status = 'active';
                        $loan->save();
                    }
                    $payment->delete();
                }

                //  Delete payroll record
                $oldPayroll->delete();
            }

            //  Clean payroll details for same payDate (scoped to processed employees)
            PayrollDetail::where('payroll_date', $payDate)
                ->whereIn('employee_id', $employeeIds)
                ->delete();

            // ==============================
            //  LOAD HOLIDAYS
            // ==============================
            $holidays = holidayLoggerModel::whereBetween('date', [$startDate, $endDate])->get();
            // Holidays are now PER DEPARTMENT. Group by date but keep every row's
            // department_id so each employee only gets the holidays for their dept.
            // department_id = NULL  =>  applies to ALL departments.
            $holidaysByDate = [];
            foreach ($holidays as $holiday) {
                $d = date('Y-m-d', strtotime($holiday->date));
                $holidaysByDate[$d][] = [
                    'type'          => $holiday->type,
                    'department_id' => $holiday->department_id,
                ];
            }

            $allLeaves = LeaveDetail::where('status', 'APPROVEDBYCFO')
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

            $allObs = OB::where('status', 'APPROVEDBYCFO')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
                })
                ->get()
                ->groupBy('employee_id');

            $allOts = Overtime::where('status', 'APPROVEDBYCFO')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date_from', [$startDate, $endDate])
                    ->orWhereBetween('date_to', [$startDate, $endDate]);
                })
                ->get()
                ->groupBy('emp_detail_id');

            // PERFORMANCE: pre-load schedules + attendance summaries for ALL employees
            //    in ONE query each (grouped by employee), instead of two queries per
            //    employee inside the loop. Big speed-up on large runs.
            $allSchedules = EmployeeSchedule::whereIn('employee_id', $employeeIds)
                ->whereBetween('sched_start_date', [$startDate, $endDate])
                ->get()
                ->groupBy('employee_id');

            $allSummaries = AttendanceSummary::whereIn('employee_id', $employeeIds)
                ->with('manualDeductions')
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get()
                ->groupBy('employee_id');

            // ==============================
            //  PROCESS EACH EMPLOYEE
            // ==============================
            foreach ($employees as $emp) {
                //  Salary Base Info
                $salary = $emp->empDetail->getSalaryInfo();
                $empBasic   = $salary['basic'];

                //  This employee's department (for per-department holiday matching)
                $empDeptId = optional($emp->empDetail)->empDepID;
                // Resolve which holiday type applies to THIS employee on a given date.
                // A holiday tied to the employee's department wins; a NULL-department
                // holiday applies to everyone; otherwise no holiday for this employee.
                $holidayTypeForDate = function ($dateStr) use (&$holidaysByDate, $empDeptId) {
                    if (empty($holidaysByDate[$dateStr])) return null;
                    $allDeptType = null;
                    foreach ($holidaysByDate[$dateStr] as $h) {
                        if ($h['department_id'] === null || $h['department_id'] === '') {
                            if ($allDeptType === null) $allDeptType = $h['type'];
                            continue;
                        }
                        if ((string) $h['department_id'] === (string) $empDeptId) {
                            return $h['type']; // department-specific match wins
                        }
                    }
                    return $allDeptType; // fall back to an all-departments holiday
                };
                $allowance  = 0; // computed daily after the attendance loop (days present + rest-day OT)
                $scheduledDates = []; // distinct scheduled dates this cut-off (for rest-day OT detection)
                $dailyRate  = $empBasic / 26;
                $hourlyRate = $dailyRate / 8;

                //  Initialize payroll counters
                {
                    $absentDays = 0;
                    $daysPresent = 0; 
                    $totalHoursWorked = 0;
                    $totalOT = 0;
                    $totalLate = 0;
                    $totalUndertime = 0;
                    $holidayPay = 0;
                    $basicPay=0;
                    $netPay=0;
                    $payRec=0;
                    $over_break_minutes=0;
                    $outpass_minutes = 0;
                    $night_diff_pay = 0;
                    $night_diff_mins = 0;
                    $custom_deduction_mins = 0;
                    $absentDeduction = 0; // 0 for daily/probationary (no-work-no-pay); set for RGLR below
                    $appliedHolidays = []; // holidays that actually applied to THIS employee's dept
                    $detailRows = [];      // per-day rows collected for ONE bulk insert (perf)
                }

                //  Get employee schedules + attendance summaries (from the bulk pre-load)
                $employeeSchedules = $allSchedules->get($emp->empID, collect());

                // Attendance summaries for the period
                $attendanceSummaries = $allSummaries->get($emp->empID, collect())
                    ->keyBy(fn($s) => date('Y-m-d', strtotime($s->attendance_date)));

                // OB / OT / Leave lookups
                $employeeLeaves = $allLeaves->get($emp->empID, collect());
                $employeeObs    = $allObs->get($emp->empID, collect());
                $employeeOtsAll = $allOts->get(optional($emp->empDetail)->id, collect()); // ALL approved OT this period (keyed by emp_detail_id)
                $employeeOts    = $employeeOtsAll->keyBy(fn($ot) => Carbon::parse($ot->date_from)->format('Y-m-d'));

                // ✨ OT is paid for EVERY approved OT in the period, independent of the schedule.
                //    Rest-day / holiday OT falls on non-scheduled days, so it is summed here and
                //    NOT inside the schedule-driven daily loop below.
                $totalOT = $employeeOtsAll->sum(fn($ot) => (float) ($ot->total_pay ?? 0));

                // Skip only when there is truly nothing to pay this cut-off:
                // no schedule AND no OT AND no OB AND no approved leave.
                if ($employeeSchedules->isEmpty()
                    && $employeeOtsAll->isEmpty()
                    && $employeeObs->isEmpty()
                    && $employeeLeaves->isEmpty()
                ) {
                    continue;
                }

                // ==============================
                //  DAILY ATTENDANCE LOOP
                // ==============================
                foreach ($employeeSchedules as $schedule) {
                    $schedStart = Carbon::parse($schedule->sched_start_date);
                    $schedEnd   = Carbon::parse($schedule->sched_end_date);

                    for ($date = $schedStart->copy(); $date->lte($schedEnd); $date->addDay()) {
                        $dateStr = $date->format('Y-m-d');
                        $scheduledDates[$dateStr] = true;
                        $summary = $attendanceSummaries[$dateStr] ?? null;

                        // --- Quick lookups using collections ---
                        $onLeave = $employeeLeaves->first(fn($l) =>
                            $dateStr >= $l->start_date && $dateStr <= $l->end_date
                        );

                        $onOB = $employeeObs->first(fn($ob) =>
                            $dateStr >= $ob->start_date && $dateStr <= $ob->end_date
                        );

                        // OT total is computed once above from all approved OT (see $totalOT).

                        if ($onLeave || $onOB) {
                            $isAbsent = false;
                            // Pwede mo rin i-count as daysPresent kung bayad ang leave nila
                            $daysPresent++; 
                        } else {
                            $isAbsent = (!$summary || $summary->total_hours == 0);
                            if ($isAbsent) {
                                $absentDays++;
                            } else {
                                $daysPresent++; // ✨ ADD THIS: Bilangin kapag pumasok ✨
                            }
                        }

                        //  Accumulate worked hours + deductions
                        if ($summary) {
                            $totalHoursWorked += $summary->total_hours;
                            $totalLate        += $summary->mins_late;
                            $totalUndertime   += $summary->mins_undertime;
                            $over_break_minutes  += $summary->over_break_minutes;
                            $outpass_minutes   += $summary->outpass_minutes;
                            $night_diff_mins +=  $summary->mins_night_diff;

                            if ($summary->deductions && $summary->deductions->isNotEmpty()) {
                                $custom_deduction_mins += $summary->deductions->sum('deduction_minutes');
                            }
                        }

                        // ==============================
                        //  HOLIDAY HANDLING (OT on the date overrides; otherwise pay holiday)
                        // ==============================
                        $holidayType = $holidayTypeForDate($dateStr); // dept-aware (null = none for this employee)
                        if ($holidayType !== null) {
                            $worked        = $summary && $summary->total_hours > 0;
                            $appliedHolidays[] = ['date' => $dateStr, 'type' => $holidayType == '0' ? 'Regular' : 'Special'];
                            $prevDay       = $date->copy()->subDay()->format('Y-m-d');
                            $presentBefore = isset($attendanceSummaries[$prevDay]) && $attendanceSummaries[$prevDay]->total_hours > 0;
                            $hasOtToday    = $employeeOts->has($dateStr);

                            if ($hasOtToday) {
                                // Worked on the holiday and filed OT — the OT module already pays it.
                                // Just make sure the day is not charged as an absence.
                                if ($isAbsent && $absentDays > 0) {
                                    $absentDays--;
                                }
                            } else {
                                // No OT on this holiday — grant the standard holiday benefit.
                                if ($holidayType == '0') { // REGULAR holiday
                                    if ($worked) {
                                        $holidayPay += $dailyRate * 1;
                                    } elseif ($onLeave || $onOB) {
                                        $holidayPay += $dailyRate;
                                    } elseif ($presentBefore) {
                                        $holidayPay += $dailyRate;
                                        if ($absentDays > 0) {
                                            $absentDays--;
                                        }
                                    }
                                } elseif ($holidayType == '1' && $worked) { // SPECIAL holiday, worked
                                    $holidayPay += $dailyRate * 0.3;
                                }
                            }
                        }
                          $logsType = $onLeave ? 'Leave' : ($onOB ? 'OB' : ($isAbsent ? 'Absent' : 'Present'));
                        //  Collect daily record (one bulk insert per employee below — perf).
                        //  Keyed by date so overlapping schedules don't duplicate a day.
                        $detailRows[$dateStr] = [
                            'employee_id'         => $emp->empID,
                            'payroll_date'        => $payDate,
                            'date'                => $dateStr,
                            'payroll_id'          => null,
                            'logsType'            => $logsType,
                            'totalHours'          => $summary->total_hours ?? 0,
                            'late_minutes'        => $summary->mins_late ?? 0,
                            'undertime_minutes'   => $summary->mins_undertime ?? 0,
                            'night_diff_hours'    => ($summary->mins_night_diff ?? 0) / 60,
                            'night_diff_pay'      => ($summary->mins_night_diff ?? 0) / 60 * ($hourlyRate * 0.10),
                            'late_deduction'      => ($summary->mins_late ?? 0) / 60 * $hourlyRate,
                            'undertime_deduction' => ($summary->mins_undertime ?? 0) / 60 * $hourlyRate,
                            'penalty_amount'      => 0, // Placeholder, compute if needed
                            'adjustment_amount'   => 0, // Placeholder, compute if needed
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ];
                    }
                }

                //  PERFORMANCE: one bulk insert for all of this employee's daily rows
                //     (the period's old rows were already deleted in the cleanup above).
                if (!empty($detailRows)) {
                    PayrollDetail::insert(array_values($detailRows));
                }

                // ==============================
                //  DAILY ALLOWANCE
                //  monthly allowance / 26 = daily. Paid per day PRESENT, plus one extra
                //  daily allowance for each distinct rest-day (non-scheduled) date with OT.
                // ==============================
                $dailyAllowance = ($salary['allowance'] ?? 0) / 26;
                $restDayOtDateList = $employeeOtsAll
                    ->map(fn($ot) => Carbon::parse($ot->date_from)->format('Y-m-d'))
                    ->unique()
                    ->reject(fn($d) => isset($scheduledDates[$d]))
                    ->values();
                $restDayOtDates = $restDayOtDateList->count();
                // Base allowance (absences already excluded — base is days present).
                $allowanceGross  = ($daysPresent + $restDayOtDates) * $dailyAllowance;
                // Tardiness + undertime also reduce the allowance, valued at the allowance's hourly rate.
                $allowanceHourly = $dailyAllowance / 8;
                $allowanceLateUt = ($this->lateBracketHours($totalLate) + $this->lateBracketHours($totalUndertime)) * $allowanceHourly;
                $allowance = max($allowanceGross - $allowanceLateUt, 0);

                // ==============================
                //  CLASSIFICATION: REGULAR vs DAILY
                // ==============================
                $employeeClass = $emp->empDetail->empClassification;
                $custom_deduction_pay = ($custom_deduction_mins / 60) * $hourlyRate;

                if ($employeeClass === 'RGLR') {
                    //  MONTHLY-PAID EMPLOYEES
                    $basicPay = $empBasic / 2;
                    $absentDeduction    = $absentDays * $dailyRate;
                    $lateDeduction      = $this->lateBracketHours($totalLate) * $hourlyRate;
                    $undertimeDeduction = $this->lateBracketHours($totalUndertime) * $hourlyRate;
                    $overBreakDeduction = ($over_break_minutes / 60) * $hourlyRate;
                    $outPassDeduction   = ($outpass_minutes / 60) * $hourlyRate;
                    $night_diff_pay =   ($night_diff_mins / 60) * $hourlyRate;
                  
                    
                    // $deductions = $absentDeduction + $lateDeduction + $undertimeDeduction + $outPassDeduction + $overBreakDeduction; 
                    $deductions = $absentDeduction + $lateDeduction + $undertimeDeduction + $outPassDeduction + $overBreakDeduction + $custom_deduction_pay;
                    $otPay = $totalOT; // regular employees are also entitled to OT pay
                    // Semi-monthly base = half of monthly basic (matches $basicPay and the halved allowance)
                    $grossPay = $basicPay - $deductions + $holidayPay + $night_diff_pay + $otPay;

                } else {
                 
                    // // ✨ IBINAWAS NA ANG $deductions SA GROSS PAY ✨
                    // $grossPay = max(($regularPay - $deductions + $otPay + $holidayPay + $night_diff_pay ), 0);
                    $basicPay = $dailyRate;
    
                    // ✨ FIX: Gamitin ang Days Present imbes na Total Hours ✨
                    $regularPay = $daysPresent * $dailyRate; 
                    
                    $otPay = $totalOT;
                    
                    // HINDI na natin kailangan ang absentDeduction dito dahil 
                    // kung absent siya, hindi siya kasama sa $daysPresent (No Work, No Pay)
                    
                    $lateDeduction      = $this->lateBracketHours($totalLate) * $hourlyRate;
                    $undertimeDeduction = $this->lateBracketHours($totalUndertime) * $hourlyRate;
                    $overBreakDeduction = ($over_break_minutes / 60) * $hourlyRate;
                    $outPassDeduction   = ($outpass_minutes / 60) * $hourlyRate;
                    $night_diff_pay     = ($night_diff_mins / 60) * ($hourlyRate * 0.10);

                    // Kukunin ang deductions (katulad din sa Regular)
                    $deductions = $lateDeduction + $undertimeDeduction + $outPassDeduction + $overBreakDeduction + $custom_deduction_pay; 
                    
                    // Ibabawas ang deductions sa regular pay
                    $grossPay = max(($regularPay - $deductions + $otPay + $holidayPay + $night_diff_pay), 0);
                }

                // ==============================
                //  CONTRIBUTIONS & LOANS
                // ==============================
                $isEndOfMonth = Carbon::parse($payDate)->isSameDay(Carbon::parse($payDate)->endOfMonth());
                $previousGross = Payroll::getPreviousGrossIfEndOfMonth(
                    $emp->empID,
                    $payDate,
                    $employeeClass,
                );

                $monthlyGross = $grossPay + $previousGross;
                $contributions = ContributionHelper::computeAll(
                    $monthlyGross,
                    $employeeClass,
                    $isEndOfMonth,
                    $emp->empID
                );

                //  Extract loan deductions
                $salaryLoan = $contributions['loan_breakdown']['salary'] ?? 0;
                $charges = $contributions['loan_breakdown']['charges/penalty'] ?? 0;
                $cash_adv = $contributions['loan_breakdown']['cash_adv'] ?? 0;
                $other = $contributions['loan_breakdown']['other'] ?? 0;
                // Gov-type loans (SSS / Pag-IBIG). These reduce the loan balance via
                // LoanPayment below, so they must also reduce take-home pay here.
                $sssLoan = $contributions['loan_breakdown']['sss'] ?? 0;
                $pagibigLoan = $contributions['loan_breakdown']['pagibig'] ?? 0;
                $taxable_income = $contributions['taxable_income'] ?? 0;

                //  Compute gov dues
                $govDues = $contributions['sss']['employee_share']
                    + $contributions['philhealth']['employee_share']
                    + $contributions['pagibig']['employee_share']
                    + $contributions['withholding_tax'];

                //  Compute net & receivable
                $netPay = max(0, ($grossPay - $govDues));
                $payRec = $netPay - $salaryLoan - $charges - $cash_adv - $other - $sssLoan - $pagibigLoan + $allowance;

                // ✨ ADD THIS SAFEGUARD ✨
                $canAffordLoans = true;
                if ($payRec < 0) {
                    // I-set sa 0 ang receivable para hindi negative ang ilalabas sa payslip
                    $payRec = max(0, $netPay + $allowance);

                    // I-flag na hindi natuloy ang kaltas ng loans
                    $canAffordLoans = false;

                    // Optional: I-zero out ang loan record sa payroll details para hindi lumabas sa payslip
                    $salaryLoan = 0;
                    $charges = 0;
                    $cash_adv = 0;
                    $other = 0;
                    $sssLoan = 0;
                    $pagibigLoan = 0;
                }

                // ==============================
                //  PAYROLL COMPUTATION LOG (per employee)
                // ==============================
                $breakdown = [
                    'employee_id'      => $emp->empID,
                    'name'             => trim(($emp->lname ?? '').', '.($emp->fname ?? '')),
                    'classification'   => $employeeClass,
                    'rates'            => [
                        'basic_monthly'   => $empBasic,
                        'daily_rate'      => round($dailyRate, 2),
                        'hourly_rate'     => round($hourlyRate, 2),
                        'daily_allowance' => round($dailyAllowance, 2),
                        'allowance_hourly'=> round($allowanceHourly, 2),
                    ],
                    'attendance'       => [
                        'scheduled_days'    => count($scheduledDates),
                        'days_present'      => $daysPresent,
                        'absent_days'       => $absentDays,
                        'rest_day_ot_days'  => $restDayOtDates,
                        'rest_day_ot_dates' => $restDayOtDateList->all(),
                    ],
                    'tardiness'        => [
                        'total_minutes' => $totalLate,
                        'bracket_hours' => $this->lateBracketHours($totalLate),
                        'x_hourly_rate' => round($hourlyRate, 2),
                        'deduction'     => round($lateDeduction, 2),
                    ],
                    'undertime'        => [
                        'total_minutes' => $totalUndertime,
                        'bracket_hours' => $this->lateBracketHours($totalUndertime),
                        'x_hourly_rate' => round($hourlyRate, 2),
                        'deduction'     => round($undertimeDeduction, 2),
                    ],
                    'absences'         => [
                        'days'      => $absentDays,
                        'x_daily'   => round($dailyRate, 2),
                        'deduction' => round($absentDeduction, 2),
                    ],
                    'over_break'       => ['minutes' => $over_break_minutes, 'deduction' => round($overBreakDeduction, 2)],
                    'outpass'          => ['minutes' => $outpass_minutes, 'deduction' => round($outPassDeduction, 2)],
                    'custom_deduction' => ['minutes' => $custom_deduction_mins, 'amount' => round($custom_deduction_pay, 2)],
                    'overtime'         => ['total_pay' => round($totalOT, 2), 'rest_day_ot_dates' => $restDayOtDateList->all()],
                    'holiday'          => [
                        'pay'     => round($holidayPay, 2),
                        'count'   => count($appliedHolidays),
                        'applied' => $appliedHolidays, // dates/types for THIS employee's department
                    ],
                    'holiday_pay'      => round($holidayPay, 2),
                    'night_diff'       => ['minutes' => $night_diff_mins, 'pay' => round($night_diff_pay, 2)],
                    'allowance'        => [
                        'daily_rate'        => round($dailyAllowance, 2),
                        'days_paid'         => $daysPresent + $restDayOtDates,
                        'gross'             => round($allowanceGross, 2),
                        'late_ut_hours'     => $this->lateBracketHours($totalLate) + $this->lateBracketHours($totalUndertime),
                        'late_ut_deduction' => round($allowanceLateUt, 2),
                        'net'               => round($allowance, 2),
                    ],
                    'totals'           => [
                        'total_deductions' => round($deductions, 2),
                        'basic_pay'        => round($basicPay, 2),
                        'gross_pay'        => round($grossPay, 2),
                    ],
                    'contributions'    => [
                        'sss'        => $contributions['sss']['employee_share'] ?? 0,
                        'philhealth' => $contributions['philhealth']['employee_share'] ?? 0,
                        'pagibig'    => $contributions['pagibig']['employee_share'] ?? 0,
                        'tax'        => $contributions['withholding_tax'] ?? 0,
                        'gov_dues'   => round($govDues, 2),
                        'taxable'    => round($taxable_income, 2),
                    ],
                    'loans'            => [
                        'company' => $salaryLoan, 'charges' => $charges, 'cash_adv' => $cash_adv,
                        'other' => $other, 'sss_loan' => $sssLoan, 'pagibig_loan' => $pagibigLoan,
                        'can_afford' => $canAffordLoans,
                    ],
                    'net_pay'          => round($netPay, 2),
                    'pay_receivable'   => round($payRec, 2),
                ];
                Log::channel('payroll')->info('Computed', $breakdown);

                // ==============================
                //  SAVE PAYROLL RECORD
                // ==============================
                $payroll = Payroll::updateOrCreate(
                    [
                        'employee_id'        => $emp->empID,
                        'payroll_start_date' => $startDate,
                        'payroll_end_date'   => $endDate,
                        'pay_date'           => $payDate,
                    ],
                    [
                        'basic_salary' => $empBasic,
                        'basicPay' => $basicPay,
                        'total_deductions' => $deductions,
                        'gross_pay'    => $grossPay,
                        'sss_contribution' => $contributions['sss']['employee_share'],
                        'philhealth_contribution' => $contributions['philhealth']['employee_share'],
                        'pagibig_contribution' => $contributions['pagibig']['employee_share'],
                        'sss_loan' => $sssLoan,
                        'pagibig_loan' => $pagibigLoan,
                        'taxable_income'=> $taxable_income,
                        'withholding_tax' => $contributions['withholding_tax'],
                        'allowances'   => $allowance,
                        'net_pay'      => $netPay,
                        'pay_rec'      => $payRec,
                        'holiday_pay'=>$holidayPay,
                        'company_loan' => $contributions['loan_breakdown']['salary'] ?? 0,
                        'sss_employer' => $contributions['sss']['employer_share'],
                        'philhealth_employer' => $contributions['philhealth']['employer_share'],
                        'pagibig_employer'=> $contributions['pagibig']['employer_share'],
                        'penalty_amount'=> $contributions['loan_breakdown']['charges/penalty'],
                        'overBreakDeduction'      => $overBreakDeduction,
                        'outPassDeduction'      => $outPassDeduction,
                        'night_diff_pay' => $night_diff_pay,
                        'overtime_pay' => $totalOT, // OT computed in loop; now persisted for display
                        // Breakdown for the Abs/Trd/Ut column (was never being stored before)
                        'late_deduction'       => $lateDeduction,
                        'undertime_deduction'  => $undertimeDeduction,
                        'abs_ut_deduction'     => $absentDeduction + $lateDeduction + $undertimeDeduction,
                    ]
                );

                //  Persist the per-employee computation breakdown (Payroll Logs module)
                PayrollLog::updateOrCreate(
                    ['employee_id' => $emp->empID, 'pay_date' => $payDate],
                    [
                        'payroll_id'         => $payroll->id,
                        'employee_name'      => trim(($emp->lname ?? '').', '.($emp->fname ?? '')),
                        'department_id'      => $emp->empDetail->empDepID ?? null,
                        'department_name'    => optional(optional($emp->empDetail)->department)->dep_name,
                        'classification'     => $employeeClass,
                        'payroll_start_date' => $startDate,
                        'payroll_end_date'   => $endDate,
                        'gross_pay'          => $grossPay,
                        'net_pay'            => $netPay,
                        'pay_rec'            => $payRec,
                        'breakdown'          => $breakdown,
                    ]
                );


                if ($isEndOfMonth && $employeeClass !== 'TRN' && $canAffordLoans) { 
                    foreach ($contributions['loan_details'] as $loan) {
                        LoanPayment::create([
                            'loan_id' => $loan['loan_id'],
                            'payroll_id' => $payroll->id,
                            'amount_paid' => $loan['deducted_amount'],
                            'payment_date' => now(),
                            'remarks' => 'Auto payroll deduction',
                        ]);

                        Loan::where('id', $loan['loan_id'])->update([
                            'balance' => $loan['new_balance'],
                            'status'  => $loan['new_balance'] <= 0 ? 'paid' : 'active'
                        ]);
                    }
                }

                // ==============================
                //  LINK PAYROLL DETAILS
                // ==============================
                PayrollDetail::where('employee_id', $emp->empID)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->update(['payroll_id' => $payroll->id]);
            }

            // ==============================
            //  COMMIT TRANSACTION
            // ==============================
            DB::commit();
            Log::channel('payroll')->info('=== Payroll run DONE ===', ['pay_date' => $payDate, 'employees' => $employeeIds->count()]);
            $scope = $departmentId === 'all' ? 'all departments' : "department #$departmentId";
            return "Payroll computed successfully for $scope — pay date $payDate ($startDate to $endDate). Employees processed: " . $employeeIds->count();

        } catch (\Throwable $e) {
            //  HANDLE ERRORS
            DB::rollBack();
            Log::error('Payroll computation failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Payroll computation failed: '.$e->getMessage() . $employees->pluck('empID')->join(', '),
            ], 500);
        }
    }

    
    public function getDetailsByPayroll(Request $request)
{
    try {
        $request->validate([
            'payroll_date'  => 'required|date',
            'company_id'    => 'nullable',
            'class_id'      => 'nullable',
            'department_id' => 'nullable',
        ]);

        // 1. Build Query
        $query = PayrollDetail::with(['employee', 'empdetails.department', 'empdetails.position'])
            ->where('payroll_date', $request->payroll_date);

        if ($request->has('company_id') && $request->company_id !== 'all') {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        if ($request->has('class_id') && $request->class_id !== 'all') {
            $query->whereHas('empdetails', function($q) use ($request) {
                $q->where('empClassification', $request->class_id);
            });
        }

        if ($request->has('department_id') && $request->department_id !== 'all') {
            $query->whereHas('empdetails', function($q) use ($request) {
                $q->where('empDepID', $request->department_id);
            });
        }

        $rawDetails = $query->get();

        // 2. Map and Grouping
        $details = $rawDetails->sortBy(function ($row) {
            return [strtolower($row->employee->lname ?? ''), $row->date];
        })->groupBy('employee_id')->map(function ($rows) {
            $employee = $rows->first()->employee;
            $empDetail = $rows->first()->empdetails;

            return [
                'employee_id'   => $employee?->empID ?? 'N/A',
                'employee_name' => $employee ? strtoupper(trim(($employee->lname ?? '') . ' ' . ($employee->fname ?? ''))) : 'UNKNOWN',
                'department'    => $empDetail?->department?->dep_name ?? 'N/A',
                'position'      => $empDetail?->position?->pos_desc ?? 'N/A',
                'records'       => $rows->map(function ($row) {
                    return [
                        'date'                => $row->date?->format('Y-m-d') ?? 'N/A',
                        'logsType'            => $row->logsType ?? '',
                        'totalHours'          => $row->totalHours ?? 0,
                        'late_minutes'        => $row->late_minutes ?? 0,
                        'undertime_minutes'   => $row->undertime_minutes ?? 0,
                        'late_deduction'      => $row->late_deduction ?? 0,
                        'undertime_deduction' => $row->undertime_deduction ?? 0,
                        'night_diff_hours'    => $row->night_diff_hours ?? 0,
                        'night_diff_pay'      => $row->night_diff_pay ?? 0,
                        'penalty_amount'      => $row->penalty_amount ?? 0,
                        'adjustment_amount'   => $row->adjustment_amount ?? 0,
                        'remarks'             => $row->remarks ?? '',
                    ];
                })->values(),
                'totals' => [
                    'totalHours'          => $rows->sum('totalHours'),
                    'late_minutes'        => $rows->sum('late_minutes'),
                    'undertime_minutes'   => $rows->sum('undertime_minutes'),
                    'late_deduction'      => $rows->sum('late_deduction'),
                    'undertime_deduction' => $rows->sum('undertime_deduction'),
                    'night_diff_hours'    => $rows->sum('night_diff_hours'),
                    'night_diff_pay'      => $rows->sum('night_diff_pay'),
                    'penalty_amount'      => $rows->sum('penalty_amount'),
                    'adjustment_amount'   => $rows->sum('adjustment_amount'),
                ],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $details,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => collect($e->errors())->flatten()->first() ?? 'Invalid filter values.',
            'error'   => $e->getMessage(),
        ], 422);

    } catch (\Exception $e) {
        // I-log ang error para makita sa storage/logs/laravel.log
        Log::error('Payroll Error: ' . $e->getMessage());

        // Return JSON error response para makita sa Network Tab
        return response()->json([
            'success' => false,
            'message' => 'Error processing payroll details.',
            'error'   => $e->getMessage(), // Pwede mong burahin ito sa production
            'trace'   => $e->getTraceAsString()
        ], 500);
    }
}


    /**
     * Render printable payslip(s) for a given pay date.
     * - Single employee: pass employee_id.
     * - Bulk: omit employee_id (optionally scope by company/classification/department).
     */
    public function payslip(Request $request)
    {
        $request->validate([
            'pay_date'          => 'required|date',
            'employee_id'       => 'nullable',
            'company_id'        => 'nullable',
            'classification_id' => 'nullable',
            'department_id'     => 'nullable',
        ]);

        $payDate          = $request->query('pay_date');
        $employeeId       = $request->query('employee_id');
        $companyId        = $request->query('company_id', 'all') ?: 'all';
        $classificationId = $request->query('classification_id', 'all') ?: 'all';
        $departmentId     = $request->query('department_id', 'all') ?: 'all';

        $query = Payroll::with([
                'employee.empDetail.department',
                'employee.empDetail.position',
                'employee.empDetail.company',
            ])
            ->join('users', 'payrolls.employee_id', '=', 'users.empID')
            ->where('payrolls.pay_date', $payDate)
            ->select('payrolls.*');

        if (!empty($employeeId)) {
            $query->where('payrolls.employee_id', $employeeId);
        }

        if ($companyId !== 'all' || $classificationId !== 'all' || $departmentId !== 'all') {
            $query->whereHas('employee.empDetail', function ($q) use ($companyId, $classificationId, $departmentId) {
                if ($companyId !== 'all')        { $q->where('empCompID', $companyId); }
                if ($classificationId !== 'all') { $q->where('empClassification', $classificationId); }
                if ($departmentId !== 'all')     { $q->where('empDepID', $departmentId); }
            });
        }

        $payrolls = $query->orderBy('users.lname')->orderBy('users.fname')->get();

        return view('pages.modules.payslip', compact('payrolls', 'payDate'));
    }

}

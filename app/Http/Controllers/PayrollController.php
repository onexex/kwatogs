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
use App\Models\PayrollApproval;
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
     * Late/undertime hours on the PERIOD TOTAL (company policy):
     *   Exact minutes — no bracket rounding. 1 minute late = 1 minute deducted.
     *   (Method name kept for compatibility; it now returns the precise hours.)
     */
    private function lateBracketHours($mins): float
    {
        $mins = (int) $mins;
        if ($mins <= 0) return 0.0;
        return round($mins / 60, 4);
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

            // Holiday-pay eligibility (see business rule in CLAUDE.md) needs to look back
            // to an employee's last SCHEDULED workday before a holiday, which can fall
            // before this cut-off's start (e.g. a holiday on day 1, last workday was the
            // prior period's Friday). Widen the lookup-only bulk fetches below by this
            // many days so that look-back never needs a per-employee/per-holiday query.
            $holidayLookbackDays = 14;
            $lookbackStart = Carbon::parse($startDate)->subDays($holidayLookbackDays)->format('Y-m-d');

            // ── Approval lock: an approved pay date is final ──
            if (\App\Models\PayrollApproval::isLocked($payDate)
                && !optional($request->user())->can('regeneratepayroll')) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'locked'  => true,
                    'message' => 'This payroll has been approved and is final. Regeneration is not allowed.',
                ], 423);
            }

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
                    // Recurring charges never tracked a balance, so there is nothing to
                    // restore — only finite loans get their balance/status rolled back.
                    if ($loan && !$loan->is_recurring) {
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

            // NOTE: lower bound widened to $lookbackStart (not $startDate) so the holiday
            // look-back rule can check an employee's leave status on a day before this
            // cut-off without an extra query. This is a pure lookup map — it does not
            // change which days are iterated as "this period's attendance" anywhere below.
            $allLeaves = LeaveDetail::where('status', 'APPROVEDBYCFO')
            ->whereBetween('date', [$lookbackStart, $endDate])
            ->get()
            ->groupBy('employee_id');

            $allObs = OB::where('status', 'APPROVEDBYCFO')
                ->where(function ($q) use ($lookbackStart, $endDate) {
                    $q->whereBetween('start_date', [$lookbackStart, $endDate])
                    ->orWhereBetween('end_date', [$lookbackStart, $endDate]);
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

            // SEPARATE from $allSchedules above on purpose: $allSchedules is iterated
            // directly as "every day this employee is scheduled THIS period" (the daily
            // attendance loop below), so it must stay scoped to [$startDate, $endDate].
            // This one is only ever used as a lookup to find a single past date (the
            // last scheduled workday before a holiday) and is safe to widen.
            $allSchedulesForLookback = EmployeeSchedule::whereIn('employee_id', $employeeIds)
                ->whereBetween('sched_start_date', [$lookbackStart, $endDate])
                ->get()
                ->groupBy('employee_id');

            // Widened lower bound — see $lookbackStart note above. Only used as a
            // date-keyed lookup map, so extra pre-period rows are harmless: the daily
            // loop below only ever looks up dates that come from $allSchedules (still
            // scoped to this period), never iterates this collection wholesale.
            $allSummaries = AttendanceSummary::whereIn('employee_id', $employeeIds)
                ->with('manualDeductions')
                ->whereBetween('attendance_date', [$lookbackStart, $endDate])
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

                // Business rule: Trainees ('TRN' classification — same code already used
                // for skipping gov't contributions/withholding tax in ContributionHelper
                // and for skipping loan deductions below) are NOT granted holiday pay.
                // Read once per employee from the already-eager-loaded empDetail — no
                // extra query, so this costs nothing in payroll generation speed.
                $isTraineeForHoliday = optional($emp->empDetail)->empClassification === 'TRN';
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

                // ==============================
                //  HOLIDAY-PAY ELIGIBILITY VIA LAST SCHEDULED WORKDAY (see CLAUDE.md)
                //  Built once per employee, reused for every holiday in this period.
                //  Everything here reads from collections already bulk-loaded above
                //  (widened by $lookbackStart) — no per-employee/per-holiday queries.
                // ==============================

                // Distinct scheduled dates within the look-back buffer, sorted ascending,
                // as plain 'Y-m-d' strings (ISO format sorts/compares correctly as strings,
                // so the eligibility check below never needs to re-parse them with Carbon).
                $scheduledDatesForLookback = $allSchedulesForLookback->get($emp->empID, collect())
                    ->map(fn($s) => Carbon::parse($s->sched_start_date)->format('Y-m-d'))
                    ->unique()
                    ->sort()
                    ->values();

                // Was the employee Present, on Approved PAID Leave (leave_kind == 0), or on
                // OB on a given date? Reuses $attendanceSummaries / $employeeLeaves /
                // $employeeObs already loaded above — no additional queries.
                $isPaidOnDate = function (string $d) use ($attendanceSummaries, $employeeLeaves, $employeeObs) {
                    $summary = $attendanceSummaries[$d] ?? null;
                    if ($summary && $summary->total_hours > 0) {
                        return true; // Present
                    }

                    $paidLeave = $employeeLeaves->first(fn($l) =>
                        Carbon::parse($l->date)->format('Y-m-d') === $d && (string) $l->leave_kind === '0'
                    );
                    if ($paidLeave) {
                        return true; // Approved paid leave
                    }

                    $onOb = $employeeObs->first(fn($ob) => $d >= $ob->start_date && $d <= $ob->end_date);

                    return (bool) $onOb;
                };

                // Resolves the business rule: find this employee's last scheduled workday
                // strictly before $holidayDateStr (capped at $holidayLookbackDays back), then
                // check whether that day was Present / Approved Paid Leave / OB. No schedule
                // found within the cap => ineligible (holiday pay = 0), per the agreed default.
                $wasEligibleViaLastScheduledWorkday = function (string $holidayDateStr) use ($scheduledDatesForLookback, $holidayLookbackDays, $isPaidOnDate) {
                    $cutoffStr = Carbon::parse($holidayDateStr)->subDays($holidayLookbackDays)->format('Y-m-d');

                    $lastScheduledDate = $scheduledDatesForLookback
                        ->filter(fn($d) => $d < $holidayDateStr && $d >= $cutoffStr)
                        ->last();

                    if ($lastScheduledDate === null) {
                        return false; // no provable scheduled workday in range -> ineligible
                    }

                    return $isPaidOnDate($lastScheduledDate);
                };

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

                    // Each schedule row is ONE shift = ONE work day, attributed to its START date.
                    // For overnight shifts sched_end_date is the NEXT MORNING (still the same shift),
                    // so iterating up to sched_end_date created a bogus 2nd "scheduled day" and marked
                    // the employee ABSENT on what is actually a rest day. Process the start date only.
                    for ($date = $schedStart->copy(); $date->lte($schedStart); $date->addDay()) {
                        $dateStr = $date->format('Y-m-d');
                        if (isset($scheduledDates[$dateStr])) { continue; } // day already counted by another shift row
                        $scheduledDates[$dateStr] = true;
                        $summary = $attendanceSummaries[$dateStr] ?? null;

                        // --- Quick lookups using collections ---
                        // FIX: LeaveDetail has no start_date/end_date (only a single `date`
                        // column — one row per leave day), so the previous range comparison
                        // here always evaluated false and $onLeave never matched anything.
                        $onLeave = $employeeLeaves->first(fn($l) =>
                            Carbon::parse($l->date)->format('Y-m-d') === $dateStr
                        );

                        $onOB = $employeeObs->first(fn($ob) =>
                            $dateStr >= $ob->start_date && $dateStr <= $ob->end_date
                        );

                        // Now that $onLeave can actually match (see fix above), distinguish
                        // paid vs unpaid leave (LeaveDetail.leave_kind: '0' = paid, '1' = unpaid —
                        // matches the leave-application form & all displays/imports)
                        // — only paid leave should count toward daysPresent / holiday pay below.
                        $isPaidLeave   = $onLeave && (string) $onLeave->leave_kind === '0';
                        $isUnpaidLeave = $onLeave && !$isPaidLeave;

                        // OT total is computed once above from all approved OT (see $totalOT).

                        if ($isPaidLeave || $onOB) {
                            $isAbsent = false;
                            // Pwede mo rin i-count as daysPresent kung bayad ang leave nila
                            $daysPresent++;
                        } elseif ($isUnpaidLeave) {
                            // Approved but unpaid: not a disciplinary absence, but also not a
                            // paid day — doesn't add to absentDays OR daysPresent.
                            $isAbsent = false;
                        } else {
                            $isAbsent = (!$summary || $summary->total_hours == 0);
                            if ($isAbsent) {
                                $absentDays++;
                            } else {
                                $daysPresent++; // ✨ ADD THIS: Bilangin kapag pumasok ✨
                            }
                        }

                        //  Accumulate worked hours + deductions
                        // Actual productive minutes for the day (0 if no attendance summary).
                        $workedMins = $summary ? (int) round(((float) $summary->total_hours) * 60) : 0;

                        if ($isPaidLeave && $onLeave && !$onOB) {
                            // PARTIAL/HALF-DAY PAID LEAVE. The day is paid for min(8h, worked +
                            // paid-leave): a worked half + a paid-leave half = a full day, but
                            // either half alone pays only that half. The attendance summary has no
                            // knowledge of the leave, so its raw mins_late/mins_undertime (measured
                            // against the full 8h schedule, which already includes the leave half)
                            // would be wrong here. Replace them with the true uncovered shortfall —
                            // the part of the schedule covered by neither work nor paid leave —
                            // charged as undertime. Because $workedMins is the ACTUAL productive
                            // minutes, any lateness/short-time inside the worked half is captured
                            // here too. ($onOB excluded: OB already covers the working portion.)
                            $leaveMins      = (int) round(((float) $onLeave->total_hours) * 60);
                            $shortfallMins  = max(0, 480 - $leaveMins - $workedMins);
                            $totalUndertime += $shortfallMins;
                        } elseif ($summary) {
                            $totalLate      += $summary->mins_late;
                            $totalUndertime += $summary->mins_undertime;
                        }

                        // These apply regardless (independent infractions / pay).
                        if ($summary) {
                            $totalHoursWorked += $summary->total_hours;
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
                        // Track the holiday benefit granted for THIS date only, so the Payroll
                        // Detail Report can show a dedicated "Holiday Pay" line for the day.
                        // $holidayPay is a running per-employee total; the per-day amount is the
                        // delta added inside the branch below.
                        $dayHolidayType   = $holidayType === null ? null : ($holidayType == '0' ? 'Regular' : 'Special');
                        $holidayPayBefore = $holidayPay;
                        if ($holidayType !== null) {
                            $worked        = $summary && $summary->total_hours > 0;
                            $appliedHolidays[] = ['date' => $dateStr, 'type' => $holidayType == '0' ? 'Regular' : 'Special'];
                            // Business rule: if the employee didn't work the holiday and wasn't on
                            // leave/OB that day either, fall back to their LAST SCHEDULED WORKDAY
                            // before the holiday (not just literal yesterday — rest days/weekends
                            // are skipped) and check Present / Approved Paid Leave / OB on THAT day.
                            // No scheduled workday found within $holidayLookbackDays => ineligible.
                            $eligibleViaLastScheduledWorkday = $wasEligibleViaLastScheduledWorkday($dateStr);
                            $hasOtToday    = $employeeOts->has($dateStr);

                            if ($hasOtToday) {
                                // Worked on the holiday and filed OT — the OT module already pays it.
                                // Just make sure the day is not charged as an absence.
                                if ($isAbsent && $absentDays > 0) {
                                    $absentDays--;
                                }
                            } elseif (!$isTraineeForHoliday) {
                                // No OT on this holiday — grant the standard holiday benefit.
                                // (Trainees are excluded from this whole branch above, so
                                // $holidayPay simply stays 0 for them — they still get paid
                                // OT normally via the $hasOtToday branch if they worked it.)
                                if ($holidayType == '0') { // REGULAR holiday
                                    if ($worked) {
                                        $holidayPay += $dailyRate * 1;
                                    } elseif ($isPaidLeave || $onOB) {
                                        // Unpaid leave ($isUnpaidLeave) does NOT grant holiday pay here —
                                        // it can still qualify via $eligibleViaLastScheduledWorkday below,
                                        // same as any other day the employee didn't work the holiday.
                                        $holidayPay += $dailyRate;
                                    } elseif ($eligibleViaLastScheduledWorkday) {
                                        $holidayPay += $dailyRate;
                                        if ($absentDays > 0) {
                                            $absentDays--;
                                        }
                                    }
                                } elseif ($holidayType == '1' && ($worked || $isPaidLeave || $onOB)) {
                                    // SPECIAL holiday premium (+30%). OB and approved PAID leave are
                                    // treated as rendered work here — same policy as the regular-holiday
                                    // branch above — so they earn 130% (100% via daysPresent + 30%),
                                    // not just 100%. Unpaid leave and plain absence get nothing
                                    // (special holidays follow "no work, no pay"; no day-before rule).
                                    $holidayPay += $dailyRate * 0.3;
                                }
                            }
                        }
                          $logsType = $onLeave ? 'Leave' : ($onOB ? 'OB' : ($isAbsent ? 'Absent' : 'Present'));
                        // Per-day holiday benefit actually granted (0 if ineligible / OT-paid).
                        $dayHolidayPay = round($holidayPay - $holidayPayBefore, 2);
                        //  Collect daily record (one bulk insert per employee below — perf).
                        //  Keyed by date so overlapping schedules don't duplicate a day.
                        $detailRows[$dateStr] = [
                            'employee_id'         => $emp->empID,
                            'payroll_date'        => $payDate,
                            'date'                => $dateStr,
                            'payroll_id'          => null,
                            'logsType'            => $logsType,
                            'holiday_type'        => $dayHolidayPay > 0 ? $dayHolidayType : null,
                            'holiday_pay'         => $dayHolidayPay,
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
                    // DOLE / Labor Code Art. 86: night shift differential = 10% of the
                    // hourly rate for each hour worked between 10 PM and 6 AM.
                    $night_diff_pay =   ($night_diff_mins / 60) * ($hourlyRate * 0.10);
                  
                    
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
                //  HR PAY ADJUSTMENTS (one-time, this pay date)
                // ==============================
                $payAdjGross = 0; // +/- applied to gross (taxed)
                $payAdjNet   = 0; // +/- applied to take-home (after tax)
                $payAdjustments = \App\Models\PayAdjustment::where('employee_id', $emp->empID)
                    ->whereDate('pay_date', $payDate)->get();
                foreach ($payAdjustments as $pa) {
                    $signed = ($pa->kind === 'deduction' ? -1 : 1) * (float) $pa->amount;
                    if ($pa->apply_to === 'gross') { $payAdjGross += $signed; } else { $payAdjNet += $signed; }
                }
                // Gross adjustments are applied BEFORE tax/contributions so the engine recomputes naturally
                $grossPay = max(0, $grossPay + $payAdjGross);

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

                // Per-employee government-dues enrolment toggles (default ON).
                // Read off the already-eager-loaded empDetail — no extra query.
                $duesFlags = [
                    'sss'        => (bool) (optional($emp->empDetail)->sss_enabled ?? true),
                    'philhealth' => (bool) (optional($emp->empDetail)->philhealth_enabled ?? true),
                    'pagibig'    => (bool) (optional($emp->empDetail)->pagibig_enabled ?? true),
                ];

                $contributions = ContributionHelper::computeAll(
                    $monthlyGross,
                    $employeeClass,
                    $isEndOfMonth,
                    $emp->empID,
                    $duesFlags,
                    $payDate
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
                // Taxable income shown every cutoff (display only; tax/contributions still
                // deduct on the end-of-month run). Uses $monthlyGross so the end-of-month
                // figure is the EXACT whole-month taxable income (current + previous cutoff);
                // on the 1st cutoff $monthlyGross == current gross and contributions are 0.
                $taxable_income = max(0, $monthlyGross
                    - ($contributions['sss']['employee_share'] ?? 0)
                    - ($contributions['philhealth']['employee_share'] ?? 0)
                    - ($contributions['pagibig']['employee_share'] ?? 0));

                //  Compute gov dues
                $govDues = $contributions['sss']['employee_share']
                    + $contributions['philhealth']['employee_share']
                    + $contributions['pagibig']['employee_share']
                    + $contributions['withholding_tax'];

                //  Compute net & receivable
                $netPay = max(0, ($grossPay - $govDues));
                $payRec = $netPay - $salaryLoan - $charges - $cash_adv - $other - $sssLoan - $pagibigLoan + $allowance + $payAdjNet;

                // ✨ ADD THIS SAFEGUARD ✨
                $canAffordLoans = true;
                if ($payRec < 0) {
                    // I-set sa 0 ang receivable para hindi negative ang ilalabas sa payslip
                    $payRec = max(0, $netPay + $allowance + max(0, $payAdjNet));

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
                    'adjustments'      => [
                        'gross_taxed'   => round($payAdjGross, 2),
                        'net_after_tax' => round($payAdjNet, 2),
                        'total'         => round($payAdjGross + $payAdjNet, 2),
                        'entries'       => $payAdjustments->map(function ($pa) {
                            return [
                                'label'    => $pa->label,
                                'kind'     => $pa->kind,
                                'apply_to' => $pa->apply_to,
                                'amount'   => (float) $pa->amount,
                            ];
                        })->all(),
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
                        'adjustment_amount' => $payAdjGross + $payAdjNet,
                        'company_loan' => $contributions['loan_breakdown']['salary'] ?? 0,
                        'cash_advance' => $cash_adv,
                        'other_deduction' => $other,
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

                        // Recurring charges keep their balance/status untouched so they
                        // continue every month; the LoanPayment above is still logged.
                        if (empty($loan['is_recurring'])) {
                            Loan::where('id', $loan['loan_id'])->update([
                                'balance' => $loan['new_balance'],
                                'status'  => $loan['new_balance'] <= 0 ? 'paid' : 'active'
                            ]);
                        }
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
                'records'       => $rows->flatMap(function ($row) {
                    $date = $row->date?->format('Y-m-d') ?? 'N/A';

                    $records = [[
                        'date'                => $date,
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
                    ]];

                    // Holiday benefit gets its own line right under the day's row
                    // (e.g. a present employee on a regular holiday => Present + Holiday Pay).
                    if (($row->holiday_pay ?? 0) > 0) {
                        $records[] = [
                            'date'                => $date,
                            'logsType'            => 'Holiday Pay',
                            'totalHours'          => 0,
                            'late_minutes'        => 0,
                            'undertime_minutes'   => 0,
                            'late_deduction'      => 0,
                            'undertime_deduction' => 0,
                            'night_diff_hours'    => 0,
                            'night_diff_pay'      => 0,
                            'penalty_amount'      => 0,
                            'adjustment_amount'   => 0,
                            'remarks'             => trim(($row->holiday_type ? $row->holiday_type . ' Holiday ' : 'Holiday ')
                                                    . '+₱' . number_format((float) $row->holiday_pay, 2)),
                        ];
                    }

                    return $records;
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

    /**
     * Delete computed payroll for a pay date — ONLY while it is still unapproved.
     * An approved pay date is final and must be Reopened first (regeneratepayroll).
     * Deletes the ENTIRE pay date regardless of the screen's filters. Loan
     * deductions taken in these payrolls are rolled back (balances restored)
     * just like a recompute.
     */
    public function destroyByPayDate(Request $request)
    {
        $request->validate([
            'pay_date' => 'required|date',
        ]);

        $payDate = $request->input('pay_date');

        // Approval lock: an approved pay date is final — block deletion outright.
        if (PayrollApproval::isLocked($payDate)) {
            return response()->json([
                'success' => false,
                'locked'  => true,
                'message' => 'This payroll is approved and final. Reopen it first before deleting.',
            ], 423);
        }

        // Every payroll row for this pay date, regardless of company/department/classification.
        $payrolls = Payroll::where('pay_date', $payDate)->get();

        if ($payrolls->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No payroll records found to delete for this selection.',
            ], 404);
        }

        $count = $payrolls->count();

        try {
            DB::transaction(function () use ($payrolls, $payDate) {
                foreach ($payrolls as $oldPayroll) {
                    // Roll back loan payments taken in this payroll (same as recompute cleanup).
                    $loanPayments = LoanPayment::where('payroll_id', $oldPayroll->id)->get();
                    foreach ($loanPayments as $payment) {
                        $loan = Loan::find($payment->loan_id);
                        // Recurring charges never tracked a balance — nothing to restore.
                        if ($loan && !$loan->is_recurring) {
                            $loan->balance += $payment->amount_paid;
                            if ($loan->balance > 0) $loan->status = 'active';
                            $loan->save();
                        }
                        $payment->delete();
                    }
                    $oldPayroll->delete();
                }

                // Detail + log rows for this pay date.
                PayrollDetail::where('payroll_date', $payDate)->delete();
                PayrollLog::where('pay_date', $payDate)->delete();
            });
        } catch (\Exception $e) {
            Log::error('Delete Payroll Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting payroll records.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Deleted {$count} unapproved payroll record(s) for {$payDate}. You can now recompute or roll back the related import.",
        ]);
    }

}

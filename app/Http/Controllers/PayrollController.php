<?php
namespace App\Http\Controllers;

use App\Helpers\ContributionHelper;
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
            ]);

            $startDate = $validated['date_from'];
            $endDate = $validated['date_to'];
            $payDate = $validated['pay_date'];

            // 'all' = compute every active employee; otherwise limit to one department
            $departmentId = $request->query('department_id', 'all') ?: 'all';

            //  Fetch Active Employees (optionally filtered by department)
            $employees = User::with('empDetail')
                ->whereHas('empDetail', function ($q) use ($departmentId) {
                    $q->where('empStatus', '1');
                    if ($departmentId !== 'all') {
                        $q->where('empDepID', $departmentId);
                    }
                })
                ->get();

            // IDs being processed in this run. Used to scope cleanup so computing a
            // single department never deletes payroll belonging to other departments.
            $employeeIds = $employees->pluck('empID');

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
            $holidayDates = $holidays->mapWithKeys(fn($holiday) => [
                date('Y-m-d', strtotime($holiday->date)) => $holiday->type
            ])->toArray();

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


            // ==============================
            //  PROCESS EACH EMPLOYEE
            // ==============================
            foreach ($employees as $emp) {
                //  Salary Base Info
                $salary = $emp->empDetail->getSalaryInfo();
                $empBasic   = $salary['basic'];
                $allowance  = $salary['allowance'] / 2;
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
                }

                //  Get employee schedules + attendance summaries
                $employeeSchedules = EmployeeSchedule::where('employee_id', $emp->empID)
                    ->whereBetween('sched_start_date', [$startDate, $endDate])
                    ->with('attendanceSummaries')
                    ->get();

                if ($employeeSchedules->isEmpty()) continue;

                // Hanapin at palitan itong part na ito:
                $attendanceSummaries = $emp->attendanceSummaries()
                    ->with('manualDeductions') // ✨ ADD THIS PARA IWAS N+1 LAG ✨
                    ->whereBetween('attendance_date', [$startDate, $endDate])
                    ->get()
                    ->keyBy(fn($s) => date('Y-m-d', strtotime($s->attendance_date)));
                    
                //key ob ot leave
                    $employeeLeaves = $allLeaves->get($emp->empID, collect());
                    $employeeObs    = $allObs->get($emp->empID, collect());
                    $employeeOts = $allOts->get($emp->empID, collect())
                        ->keyBy(fn($ot) => Carbon::parse($ot->date_from)->format('Y-m-d'));  

                // ==============================
                //  DAILY ATTENDANCE LOOP
                // ==============================
                foreach ($employeeSchedules as $schedule) {
                    $schedStart = Carbon::parse($schedule->sched_start_date);
                    $schedEnd   = Carbon::parse($schedule->sched_end_date);

                    for ($date = $schedStart->copy(); $date->lte($schedEnd); $date->addDay()) {
                        $dateStr = $date->format('Y-m-d');
                        $summary = $attendanceSummaries[$dateStr] ?? null;

                        // --- Quick lookups using collections ---
                        $onLeave = $employeeLeaves->first(fn($l) =>
                            $dateStr >= $l->start_date && $dateStr <= $l->end_date
                        );

                        $onOB = $employeeObs->first(fn($ob) =>
                            $dateStr >= $ob->start_date && $dateStr <= $ob->end_date
                        );

                        $otEntry = $employeeOts->get($dateStr);

                        // OT Pay (precomputed)
                        if ($otEntry) {
                            // If OT pay is stored in the OT table
                            $totalOT += $otEntry->total_pay ?? 0;
                        } 

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
                        //  HOLIDAY PAY HANDLING
                        // ==============================
                        if (array_key_exists($dateStr, $holidayDates)) {
                            $holidayType = $holidayDates[$dateStr];
                            $worked = $summary && $summary->total_hours > 0;
                            $prevDay = $date->copy()->subDay()->format('Y-m-d');
                            $presentBefore = isset($attendanceSummaries[$prevDay]) && $attendanceSummaries[$prevDay]->total_hours > 0;

                            // LEGAL HOLIDAY
                            if ($holidayType == '1') {
                                if ($worked){
                                    $holidayPay += $dailyRate * 1;
                                 } elseif ($onLeave || $onOB) {
                                    // Already excluded from $absentDays above (isAbsent = false),
                                    // so no adjustment needed here.
                                    $holidayPay += $dailyRate;
                                } elseif ($presentBefore) {
                                    // This day WAS counted as absent above; reverse that
                                    // since the employee still qualifies for holiday pay.
                                    $holidayPay += $dailyRate;
                                    if ($absentDays > 0) {
                                        $absentDays--;
                                    }
                                }
                            }
                            // SPECIAL HOLIDAY
                            elseif ($holidayType == '0' && $worked) {
                                $holidayPay += $dailyRate * .3;
                            } 
                        }
                          $logsType = $onLeave ? 'Leave' : ($onOB ? 'OB' : ($isAbsent ? 'Absent' : 'Present'));
                        //  Save daily record
                        PayrollDetail::updateOrCreate(
                            [
                                'employee_id' => $emp->empID,
                                'payroll_date'=> $payDate,
                                'date'        => $dateStr,
                            ],
                            [
                                'payroll_id'  => null,
                                'logsType'    =>  $logsType,
                                'totalHours'  => $summary->total_hours ?? 0,
                                'late_minutes' => $summary->mins_late ?? 0,
                                'undertime_minutes' => $summary->mins_undertime ?? 0,
                                'night_diff_hours' => ($summary->mins_night_diff ?? 0) / 60,
                                'night_diff_pay' => ($summary->mins_night_diff ?? 0) / 60 * ($hourlyRate * 0.10),
                                'late_deduction' => ($summary->mins_late ?? 0) / 60 * $hourlyRate,
                                'undertime_deduction' => ($summary->mins_undertime ?? 0) / 60 * $hourlyRate,
                                'penalty_amount' => 0, // Placeholder, compute if needed
                                'adjustment_amount' => 0, // Placeholder, compute if needed
                            ]
                        );
                    }
                }

                // ==============================
                //  CLASSIFICATION: REGULAR vs DAILY
                // ==============================
                $employeeClass = $emp->empDetail->empClassification;
                $custom_deduction_pay = ($custom_deduction_mins / 60) * $hourlyRate;

                if ($employeeClass === 'RGLR') {
                    //  MONTHLY-PAID EMPLOYEES
                    $basicPay = $empBasic / 2;
                    $absentDeduction    = $absentDays * $dailyRate;
                    $lateDeduction      = ($totalLate / 60) * $hourlyRate;
                    $undertimeDeduction = ($totalUndertime / 60) * $hourlyRate;
                    $overBreakDeduction = ($over_break_minutes / 60) * $hourlyRate;
                    $outPassDeduction   = ($outpass_minutes / 60) * $hourlyRate;
                    $night_diff_pay =   ($night_diff_mins / 60) * $hourlyRate;
                  
                    
                    // $deductions = $absentDeduction + $lateDeduction + $undertimeDeduction + $outPassDeduction + $overBreakDeduction; 
                    $deductions = $absentDeduction + $lateDeduction + $undertimeDeduction + $outPassDeduction + $overBreakDeduction + $custom_deduction_pay;
                    $grossPay = $empBasic - $deductions + $holidayPay + $night_diff_pay;

                } else {
                 
                    // // ✨ IBINAWAS NA ANG $deductions SA GROSS PAY ✨
                    // $grossPay = max(($regularPay - $deductions + $otPay + $holidayPay + $night_diff_pay ), 0);
                    $basicPay = $dailyRate;
    
                    // ✨ FIX: Gamitin ang Days Present imbes na Total Hours ✨
                    $regularPay = $daysPresent * $dailyRate; 
                    
                    $otPay = $totalOT;
                    
                    // HINDI na natin kailangan ang absentDeduction dito dahil 
                    // kung absent siya, hindi siya kasama sa $daysPresent (No Work, No Pay)
                    
                    $lateDeduction      = ($totalLate / 60) * $hourlyRate;
                    $undertimeDeduction = ($totalUndertime / 60) * $hourlyRate;
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
                        // Breakdown for the Abs/Trd/Ut column (was never being stored before)
                        'late_deduction'       => $lateDeduction,
                        'undertime_deduction'  => $undertimeDeduction,
                        'abs_ut_deduction'     => $absentDeduction + $lateDeduction + $undertimeDeduction,
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

}

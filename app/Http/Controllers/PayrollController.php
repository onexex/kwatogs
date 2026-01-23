<?php
namespace App\Http\Controllers;

use App\Models\OB;
use Carbon\Carbon;
use App\Models\Loan;
use App\Models\User;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Overtime;
use App\Models\empDetail;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use App\Models\PayrollDetail;
use App\Models\SssContribution;
use App\Models\EmployeeSchedule;
use App\Models\holidayLoggerModel;
use Illuminate\Support\Facades\DB;
use App\Helpers\ContributionHelper;
use Illuminate\Support\Facades\Log;
 
class PayrollController extends Controller
{

    // ==============================
    // FETCH PAYROLL
    // ==============================
    public function fetchPayroll(Request $request)
    {
        // 🧩 Extract query parameters
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $pay_date = $request->query('payDate');
        $filter = $request->query('filter', 'all'); // optional filter: all, released, pending

        // 🧭 Prepare base query with employee relation
        $query = Payroll::with('employee');

        // 📅 Filter by date range (if provided)
        if ($dateFrom && $dateTo) {
            $query->where('pay_date', $pay_date);
        }

        // ⚙️ Filter by status (if not "all")
        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        // 📋 Get final list
        $payrolls = $query->orderBy('pay_date', 'desc')->get();

        return response()->json($payrolls);
    }

    // ==============================
    // COMPUTE PAYROLL
    // ==============================
    public function computePayroll(Request $request)
    {
        DB::beginTransaction();

        try {
            //  Fetch Active Employees (Test: single employee)
            $employees = User::with('empDetail')
                ->where('empID', 'KWTGS-0021')
                ->whereHas('empDetail', function ($q) {
                    $q->where('empStatus', '1');
                })
                ->get();

            $validated = $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'pay_date' => 'required|date',
            ]);

            $startDate = $validated['date_from'];
            $endDate = $validated['date_to'];
            $payDate = $validated['pay_date'];

            // ==============================
            //  CLEANUP OLD PAYROLL RECORDS
            // ==============================
            $existingPayrolls = Payroll::where('pay_date', $payDate)->get();
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

            //  Clean payroll details for same payDate
            PayrollDetail::where('payroll_date', $payDate)->delete();

            // ==============================
            //  LOAD HOLIDAYS
            // ==============================
            $holidays = holidayLoggerModel::whereBetween('date', [$startDate, $endDate])->get();
            $holidayDates = $holidays->mapWithKeys(fn($holiday) => [
                date('Y-m-d', strtotime($holiday->date)) => $holiday->type
            ])->toArray();

            // =======================================================
            //   Preload all relevant records once
            // =======================================================
            $allLeaves = Leave::where('status', 'Approved')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
                })
                ->get()
                ->groupBy('employee_id');

            $allObs = OB::where('status', 'Approved')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
                })
                ->get()
                ->groupBy('employee_id');

            $allOts = Overtime::where('status', 'Approved')
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
                }

                //  Get employee schedules + attendance summaries
                $employeeSchedules = EmployeeSchedule::where('employee_id', $emp->empID)
                    ->whereBetween('sched_start_date', [$startDate, $endDate])
                    ->with('attendanceSummaries')
                    ->get();

                if ($employeeSchedules->isEmpty()) continue;

                //  Key attendance summaries by date
                $attendanceSummaries = $emp->attendanceSummaries()
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

                        //  Check absence
                        if ($onLeave || $onOB) {
                            $isAbsent = false;
                        } else {
                            $isAbsent = (!$summary || $summary->total_hours == 0);
                            if ($isAbsent) $absentDays++;
                        }

                        //  Accumulate worked hours + deductions
                        if ($summary) {
                            $totalHoursWorked += $summary->total_hours;
                            $totalLate        += $summary->mins_late;
                            $totalUndertime   += $summary->mins_undertime;
                            $over_break_minutes  += $summary->over_break_minutes;
                            $outpass_minutes   += $summary->outpass_minutes;
                            $night_diff_mins +=  $summary->mins_night_diff;
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
                                 } elseif ($presentBefore || $onLeave || $onOB) {
                                    $holidayPay += $dailyRate;
                                    $absentDays = $absentDays - 1; // adjust absence
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
                                'payroll_id'  => null,
                                'employee_id' => $emp->empID,
                                'payroll_date'=> $payDate,
                                'date'        => $dateStr,
                                'logsType'    =>  $logsType,
                            ],
                            []
                        );
                    }
                }

                // ==============================
                //  CLASSIFICATION: REGULAR vs DAILY
                // ==============================
                $employeeClass = $emp->empDetail->empClassification;

                if ($employeeClass === 'RGLR') {
                    //  MONTHLY-PAID EMPLOYEES
                    $basicPay = $empBasic / 2;
                    $absentDeduction    = $absentDays * $dailyRate;
                    $lateDeduction      = ($totalLate / 60) * $hourlyRate;
                    $undertimeDeduction = ($totalUndertime / 60) * $hourlyRate;
                    $overBreakDeduction = ($over_break_minutes / 60) * $hourlyRate;
                    $outPassDeduction   = ($outpass_minutes / 60) * $hourlyRate;
                    $night_diff_pay =   ($night_diff_mins / 60) * $hourlyRate;
                  
                    
                    $deductions = $absentDeduction + $lateDeduction + $undertimeDeduction + $outPassDeduction + $overBreakDeduction; 
                    $grossPay = $empBasic - $deductions + $holidayPay + $night_diff_pay;

                } else {
                    //  DAILY / CONTRACTUAL EMPLOYEES
                    $basicPay = $dailyRate;
                    $regularPay = $totalHoursWorked * $hourlyRate;
                    $otPay = $totalOT;
                    $absentDeduction    = $absentDays * $dailyRate;
                    $lateDeduction      = ($totalLate / 60) * $hourlyRate;
                    $undertimeDeduction = ($totalUndertime / 60) * $hourlyRate;
                    $overBreakDeduction = ($over_break_minutes / 60) * $hourlyRate;
                    $outPassDeduction   = ($outpass_minutes / 60) * $hourlyRate;
                    $night_diff_pay =   ($night_diff_mins / 60) * $hourlyRate;

                    $deductions = $absentDeduction + $lateDeduction + $undertimeDeduction + $outPassDeduction + $overBreakDeduction; 
                    $grossPay = max(($regularPay + $otPay + $holidayPay + $night_diff_pay ), 0);
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
                $taxable_income = $contributions['taxable_income'];

                //  Compute gov dues
                $govDues = $contributions['sss']['employee_share']
                    + $contributions['philhealth']['employee_share']
                    + $contributions['pagibig']['employee_share']
                    + $contributions['withholding_tax'];

                //  Compute net & receivable
                $netPay = max(0, ($grossPay - $govDues));
                $payRec = $netPay - $salaryLoan - $charges - $cash_adv - $other + $allowance;

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
                        'sss_loan' => $contributions['loan_breakdown']['sss'] ?? 0,
                        'pagibig_loan' => $contributions['loan_breakdown']['pagibig'] ?? 0,
                        'taxable_income'=> $taxable_income,
                        'withholding_tax' => $contributions['withholding_tax'],
                        'allowances'   => $allowance,
                        'net_pay'      => $netPay,
                        'holiday_pay'=>$holidayPay,
                        'company_loan' => $contributions['loan_breakdown']['salary'] ?? 0,
                        'sss_employer' => $contributions['sss']['employer_share'],
                        'philhealth_employer' => $contributions['philhealth']['employer_share'],
                        'pagibig_employer'=> $contributions['pagibig']['employer_share'],
                        'penalty_amount'=> $contributions['loan_breakdown']['charges/penalty'],
                        'overBreakDeduction'      => $overBreakDeduction,
                        'outPassDeduction'      => $outPassDeduction,
                        'night_diff_pay' => $night_diff_pay,
                    ]
                );

                // ==============================
                //  AUTO LOAN DEDUCTION (End of Month)
                // ==============================
                if ($isEndOfMonth && $employeeClass !== 'TRN') {
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
            return "Payroll computed successfully for pay date $payDate ($startDate to $endDate)";

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
                'message' => 'Payroll computation failed: '.$e->getMessage(),
            ], 500);
        }
    }
}

# Activation Checklist — KWATOGS HRIS

Run these once to turn on everything built recently. Order matters.

## 1. Run database migrations

```bash
php artisan migrate
```

Creates / alters:

| Migration | What it adds |
|---|---|
| `add_payroll_type_to_emp_details` | `empPayrollType` (Cash/Card) + `empCardNo` on employees |
| `set_payroll_mode_from_atm_cash_list` | Sets Card/Cash + account numbers from the ATM list (matches by name) |
| `add_other_description_to_loans` | "Other – specify" field on Loans & Charges |
| `add_cash_advance_to_payrolls` | `cash_advance` + `other_deduction` columns |
| `create_pay_adjustments_table` | Pay Adjustments + `adjustment_amount` on payrolls |
| `create_payroll_approvals_table` | Payroll approve/lock |
| `create_schedule_requests_table` | Schedule-change requests (Kuya Kwatogs) |

## 2. Register permissions

```bash
php artisan app:create-permission
```

This reads the enum classes and creates the new permissions:

- **Page tab:** `payadjustments`, `approvepayroll`, `regeneratepayroll`, `attendanceimport`, `createschedulechange`, `approveschedulechange`, `hrdashboard`, `auditlog`
- **Overtime tab:** `overtimeimport`
- **Leave tab:** `leaveimport`
- **Report tab:** `thirteenthmonth`

## 3. Clear caches AND restart PHP

```bash
php artisan optimize:clear
```

Then **restart your web server / PHP** (Apache, Nginx+PHP-FPM, or stop/restart `php artisan serve`).
> `optimize:clear` does **not** flush PHP **opcache** — only a restart does. Several fixes (LeaveService, ScheduleRequestService, homeAttendance, leavetype primary-key fix) won't take effect until PHP restarts.

## 4. Assign permissions (Roles screen)

| Permission | Give to |
|---|---|
| `hrdashboard` | HR / Admin |
| `payadjustments` | Payroll / HR |
| `approvepayroll` | Payroll approver |
| `regeneratepayroll` | Admin / override role only |
| `attendanceimport`, `overtimeimport`, `leaveimport` | HR / Admin |
| `thirteenthmonth` | Payroll / HR / Finance |
| `auditlog` | Admin / HR |
| `approveschedulechange` | HR (Pending Schedule Requests) |
| `createschedulechange` | All employees (enables Kuya Kwatogs on the clock-in page) |

## 5. Re-compute payroll once

After migrating, re-run **Generate** for the current period so the new columns populate:
`cash_advance`, `other_deduction`, `adjustment_amount`, and the every-cutoff `taxable_income`.

---

## Quick feature map

- **Payroll register:** Print Report (logo), Print Payslips, **Export** (Cash list / ATM bank file), Cash Adv + Company Loan columns, Totals row, **Approve / lock** with Reopen override.
- **Pay Adjustments** (Operations): +/− to Gross (taxed) or Take-home, flows into payroll & payroll log.
- **Imports** (Operations): Attendance, Overtime, Leave — drag/drop xlsx/csv, dependency-free, name-matching.
- **Kuya Kwatogs** (clock-in page): emergency schedule change — applies on submit so the employee can time in; HR confirms or reverts.
- **HR Control Center** (HR Dashboard): live KPIs, 14/30-day trend, who's-in (auto-refresh), pending approvals, attendance, payroll snapshot, workforce, absenteeism, alerts, drill-downs, Export PDF.

## Known follow-ups (optional)
- Leave **disapprove safeguard** when the employee already punched (currently a straight revert).
- HR dashboard **date-range filter** for the trend / absenteeism / payroll panels.
- Payslip line-item for Pay Adjustments.
- Recurring file note: `payroll.blade.php` has occasionally lost its final `@endsection` on save (causes a full-screen/no-menu page) — if that ever recurs, re-adding `@endsection` at the end fixes it.

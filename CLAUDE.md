# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> **Read this first.** It's the navigation index for the repo so files don't have to be re-scanned every task.
> When you add/rename/move a module, update the relevant table below in the same change.

## What this is
A self-hosted **HRIS software** (Laravel 10 / PHP 8.1, Blade + Bootstrap 5, jQuery/axios) sold to PH companies to run in-house. Covers payroll, attendance, scheduling, leave & OT, government compliance (SSS/PhilHealth/Pag-IBIG/BIR), 201 files, loans, role-based access, audit trail, reports — plus **KuBo**, an internal employee community/social feed.

## Commands
- **Install:** `composer install` then `npm install`
- **Front-end build:** `npm run dev` (Laravel Mix, alias of `npm run development`) · `npm run watch` (rebuild on change) · `npm run prod` (minified production build)
- **Run app locally:** `php artisan serve`
- **Tests (PHPUnit):** `php artisan test` or `./vendor/bin/phpunit`
  - Single suite: `php artisan test --testsuite=Unit` (suites are `Unit` and `Feature`)
  - Single test: `php artisan test --filter NightDiffTest`
- **Migrations:** `php artisan migrate`
- **Permissions:** `php artisan app:create-permission` — **required** to register new permission keys (see Access control). Run after adding a key, then assign it to roles in the UI.
- **Clear caches:** `php artisan optimize:clear` — then **restart PHP / the web server** for changes to take effect (per `ACTIVATION_CHECKLIST.md`).

### Activation flow (from `ACTIVATION_CHECKLIST.md`)
After deploying changes: `php artisan migrate` → `php artisan app:create-permission` → `php artisan optimize:clear` + restart PHP → assign permissions in the Roles screen → re-compute payroll once.

## Tech stack & key packages
- **Laravel 10**, PHP `^8.1`, MySQL (`DB_CONNECTION=mysql`, default db `projinventory`). Queue/cache/session default to sync/file; broadcast = log.
- **`spatie/laravel-permission`** — role/permission backbone (see Access control below).
- **`maatwebsite/excel`** — the bulk import/export engine (Exports in `app/Exports`, import services in `app/Services`, templates `*_import_template.xlsx` at repo root).
- **`doctrine/dbal`** (schema changes), **`laravel/sanctum`** (API auth), **`laravel/tinker`**.
- **Front end:** Laravel Mix (not Vite). Blade + Bootstrap 5 + FontAwesome + jQuery + axios for AJAX.

## Conventions
- **Design tokens (shared across all pages):** teal `#008080`, teal-dark `#006666`, teal-mid `#4db6ac`, teal-light `#e0f2f1`, slate `#334155`, slate-light `#64748b`, muted `#94a3b8`, bg `#f1f5f9`, border `#e2e8f0`. Cards use `--radius-card:14px`, `--shadow-card`. Reuse these `:root` vars; don't invent new palettes.
- **Access control:** role-based via spatie. Permission keys are enums in `app/Enums/Permissions`. New keys via `php artisan app:create-permission`. Roles built in Settings → User Roles, assigned in Settings → Employee Role. A sidebar item is hidden if the user lacks its permission.
- **Auth user fields:** `community_avatar`, `community_full_name` are used in KuBo views.

## Architecture (the big picture)
- **Routing:** `routes/web.php` is the main map; `api.php`, `channels.php`, `console.php` also exist. URLs don't always mirror view paths — confirm in `web.php`.
- **Controller layout is intentionally mixed:** grouped folders for feature areas (`KuBo/`, `Leave/`, `Overtime/`, `Reports/`, `Roles/`, `Profile/`) **plus** flat `*Ctrl.php` files, one per master-data screen (`companyCtrl.php`, `departmentCtrl.php`, `philhealthCtrl.php`, …). When touching a Settings screen, expect a flat `*Ctrl.php`; for payroll/leave/OT expect the larger grouped controllers.
- **Two Services locations:** `app/Services/` holds import + backup + schedule-request services (`AttendanceImportService`, `LeaveImportService`, `OvertimeImportService`, `DatabaseBackupService`, `ScheduleRequestService`, `DynamicMailManager`); `app/Http/Services/` holds domain services (`LeaveService`, `OvertimeService`, `RoleServices`). Business logic for these flows lives in the service, not the controller.
- **Email sending is provider-agnostic:** `App\Models\MailIntegrationSetting` stores admin-configured, encrypted credentials for whichever provider the client company uses (SMTP/Brevo, Mailgun, SES, Postmark — managed at Settings → Mail Integration). `App\Services\DynamicMailManager` reads the active row and configures a runtime `'dynamic'` Laravel mailer from it — any code that sends mail should go through `DynamicMailManager::mailer()`, not assume a fixed `.env` driver.
- **Maintenance mode** (Settings → Maintenance Mode, `maintenancemode` permission) is a custom app-level lockout, **not** Laravel's `php artisan down`. State lives in a single-row `App\Models\MaintenanceSetting` (`::current()` singleton, like `PayslipEmailSetting`): `is_active`, `scope` (`global` | `department`), `department_ids` (json, used when scope=department vs `emp_details.empDepID`), `message`, optional `starts_at`/`ends_at` window. `App\Http\Middleware\CheckMaintenanceMode` (alias `check.maintenance`, added to the main `['AuthCheck','check.employee.ip']` route group in `web.php`) blocks affected authenticated users with a 503 `errors/maintenance_active.blade.php` page (JSON 503 for ajax) — it does **not** log them out. Exemptions are permission-driven: `maintenancebypass` (assign to roles who keep working), `maintenancemode` (managers, so they can turn it off), the spatie `admin` role, and legacy super admin `users.role == 1`. `MaintenanceModeController@index/update` is the only backend; the screen is gated `can:maintenancemode`.
- **Payslip email automation** (`payslipemail` permission) lives mostly on the Payroll System screen, not its own page: the "Email Payslips" button in `pages/modules/payroll.blade.php` opens a modal (JS in `public/js/modules/payroll.js`) that calls `App\Http\Controllers\PayslipEmailController` (`/payroll/payslip/send`, `/payroll/payslip/status`, `/payroll/payslip/{payroll}/resend`, `/payroll/payslip-email/settings`). Sending itself is `App\Jobs\SendPayslipEmailJob` (queued, one per employee) → `App\Services\PayslipPdfService` (TCPDF, table-based `payslip_pdf.blade.php` template — NOT the grid/flexbox `payslip.blade.php` used for on-screen printing, TCPDF can't render that) for a password-protected PDF, password chosen by `App\Services\PayslipPasswordResolver` per `App\Models\PayslipEmailSetting` (birthdate from `emp_infos.empBdate`, or employee ID — there's no SSS-number column in this schema), then sent via `DynamicMailManager::mailer()`. Every attempt is logged to `App\Models\PayslipEmailLog`. Auto-send-on-approval is wired into `PayrollApprovalController@approve`.
- **Payroll domain** spans several models that must stay in sync: `Payroll`, `PayrollDetail`, `PayrollPeriod`, `PayrollLog` (computation breakdown shown on Payroll Logs screen), `PayrollApproval`. Government contributions are table-driven: `SssContribution`, `PhilhealthContribution`, `PagibigContribution`, `BirWithholdingTax` — editing a screen rarely means editing rates; the rates live in these tables/models.
- **Holiday-pay eligibility business rule** (in `PayrollController::computePayroll()`, REGULAR-holiday branch): if the employee didn't work the holiday and wasn't on leave/OB that day, look back to their **last SCHEDULED workday** before the holiday (via `EmployeeSchedule`, not just literal "yesterday" — skips rest days/weekends), capped at `$holidayLookbackDays` (14). Holiday pay is granted only if that prior scheduled day was Present, Approved **Paid** Leave (`LeaveDetail.leave_kind == '0'`), or OB; no scheduled day found within the cap ⇒ ineligible ⇒ holiday pay = 0. To keep this O(1) per employee (no N+1 across hundreds of employees × holidays), `$allLeaves`/`$allObs`/`$allSummaries` are bulk-fetched with their lower date bound widened to `$lookbackStart`, plus one extra bulk fetch `$allSchedulesForLookback` (deliberately separate from `$allSchedules`, which stays scoped to the cut-off and is iterated directly as "this period's attendance" — never widen that one). Per-employee, a sorted list of scheduled dates and an `$isPaidOnDate`/`$wasEligibleViaLastScheduledWorkday` closure pair are built once and reused for every holiday in the run. **Trainees are excluded from holiday pay entirely:** `$isTraineeForHoliday` (`$emp->empDetail->empClassification === 'TRN'`, the same code already used in `ContributionHelper`/`BirWithholdingTax` to skip gov't contributions, and in this method to skip loan deductions) is computed once per employee and gates the whole holiday-benefit grant — `$holidayPay` simply stays 0 for a `'TRN'` employee regardless of worked/leave/OB/lookback eligibility. **Holiday premium and OT are additive, not exclusive:** filing approved OT on the holiday does NOT cancel the holiday premium. The premium applies to the BASE worked/eligible day (regular +100% = `$dailyRate`, special +30% = `$dailyRate*0.3`); OT pays the EXTRA hours beyond schedule and is summed separately in `$totalOT` — they never overlap, so a non-trainee who works a holiday and files OT earns both. (Earlier code short-circuited on `$hasOtToday` and dropped the premium whenever any OT existed on the day — fixed.) `$hasOtToday` now only un-marks an otherwise-absent day; a single guarded `$absentDays--` covers both that case and the regular-holiday lookback case. No extra queries — `empClassification` is read off the already-eager-loaded `empDetail`.
- **Fixed bug (was: known pre-existing bug):** the daily loop's `$onLeave` lookup previously compared `$dateStr` against `$l->start_date`/`$l->end_date`, columns `LeaveDetail` doesn't have (only `date`), so it always evaluated false. Now compares `Carbon::parse($l->date)->format('Y-m-d') === $dateStr` directly, and distinguishes paid vs. unpaid leave via `leave_kind` (`'0'` = paid, `'1'` = unpaid — matches the leave-application form `<option value="0">Paid</option>` and every display/report/import; payroll previously had this **inverted**, so no paid leave was ever paid — fixed) — only paid leave counts toward `daysPresent` / holiday pay; unpaid leave is excused (not an absence) but not paid.
- **Employee record** is split across `User`, `emp_info`, `empDetail`, `emp_family`, `emp_education`, `e201` — a single employee touches multiple models.
- **KuBo** is a self-contained social feed: `Community*` models, `KuBo/` controllers, `resources/views/kubo/`. The feed is JS infinite-scroll appending into `#kuboFeedContainer` (not server-rendered in full).

## View map — `resources/views/`
| Path | Contains |
|------|----------|
| `layout/app.blade.php` | Master layout; pages `@extends('layout.app')` and fill `@section('content')`. |
| `home.blade.php`, `login/` | Landing + auth. |
| `pages/modules/` | **Workforce operations**: payroll, payslip, payroll_logs, leaveApplication, leaveRequestList, overtime(+request), pay_adjustments, loan, obtTracker, earlyout, debitAdvise, registration, 201/e201, attendance/leave/overtime import, schedule_requests_pending, sendOBT, employee/edit_employee, memorandum, hradjustment, alas, checkRegister. |
| `pages/management/` | **Settings / master data & admin**: companies, departments, positions, classification, jobLevels, employeeStatus, holidaylogger, leaveTypes, leavecreditallocation, ssscontribution, philhealth, pagibigcontribution, hmo, agencies, relationship, parentalSetting, shifts, time, empscheduler, accessrights, userRole, audit_trail, archive, databasebackup, **mailintegration** (modular email provider config — SMTP/Brevo, Mailgun, SES, Postmark — used for automated payslip email), **maintenancemode** (global or per-department system lockout — see Architecture), hr_dashboard, e201, documentation, the *validations* (leave/lilo/ob/eo) + sil/loan. |
| `pages/reports/` | **Reports** (+ `_print` variants): attendance, leave_report, overtime_report, employeeInformation, thirteenth_month, alas, dar, eo, ob, ot, leaveCredit. |
| `pages/users/` | manage, roles, role_permission. |
| `kubo/` | `layout/kubo.blade.php` (shell), `feed/`, `explore/`, `notifications/`, `profile/`, `components/` (create-post-modal, reaction-picker). |

**Canonical reference:** `pages/management/documentation.blade.php` is an in-app guide listing every screen, its route, and its permission key — the source of truth for what modules exist.

## How to navigate efficiently (for the AI)
1. Start from this file — don't re-scan the tree.
2. To find a screen's backend: view name in `pages/*` → matching `*Ctrl.php` or grouped controller → model(s) → route in `web.php` → service in `app/Services` or `app/Http/Services` if it's import/leave/OT/backup.
3. Only open the specific file you'll edit. Use `documentation.blade.php` for the full screen/permission inventory.
4. Keep this map current when structure changes.

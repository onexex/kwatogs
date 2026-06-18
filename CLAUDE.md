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
- **Two Services locations:** `app/Services/` holds import + backup + schedule-request services (`AttendanceImportService`, `LeaveImportService`, `OvertimeImportService`, `DatabaseBackupService`, `ScheduleRequestService`); `app/Http/Services/` holds domain services (`LeaveService`, `OvertimeService`, `RoleServices`). Business logic for these flows lives in the service, not the controller.
- **Payroll domain** spans several models that must stay in sync: `Payroll`, `PayrollDetail`, `PayrollPeriod`, `PayrollLog` (computation breakdown shown on Payroll Logs screen), `PayrollApproval`. Government contributions are table-driven: `SssContribution`, `PhilhealthContribution`, `PagibigContribution`, `BirWithholdingTax` — editing a screen rarely means editing rates; the rates live in these tables/models.
- **Employee record** is split across `User`, `emp_info`, `empDetail`, `emp_family`, `emp_education`, `e201` — a single employee touches multiple models.
- **KuBo** is a self-contained social feed: `Community*` models, `KuBo/` controllers, `resources/views/kubo/`. The feed is JS infinite-scroll appending into `#kuboFeedContainer` (not server-rendered in full).

## View map — `resources/views/`
| Path | Contains |
|------|----------|
| `layout/app.blade.php` | Master layout; pages `@extends('layout.app')` and fill `@section('content')`. |
| `home.blade.php`, `login/` | Landing + auth. |
| `pages/modules/` | **Workforce operations**: payroll, payslip, payroll_logs, leaveApplication, leaveRequestList, overtime(+request), pay_adjustments, loan, obtTracker, earlyout, debitAdvise, registration, 201/e201, attendance/leave/overtime import, schedule_requests_pending, sendOBT, employee/edit_employee, memorandum, hradjustment, alas, checkRegister. |
| `pages/management/` | **Settings / master data & admin**: companies, departments, positions, classification, jobLevels, employeeStatus, holidaylogger, leaveTypes, leavecreditallocation, ssscontribution, philhealth, pagibigcontribution, hmo, agencies, relationship, parentalSetting, shifts, time, empscheduler, accessrights, userRole, audit_trail, archive, databasebackup, hr_dashboard, e201, documentation, the *validations* (leave/lilo/ob/eo) + sil/loan. |
| `pages/reports/` | **Reports** (+ `_print` variants): attendance, leave_report, overtime_report, employeeInformation, thirteenth_month, alas, dar, eo, ob, ot, leaveCredit. |
| `pages/users/` | manage, roles, role_permission. |
| `kubo/` | `layout/kubo.blade.php` (shell), `feed/`, `explore/`, `notifications/`, `profile/`, `components/` (create-post-modal, reaction-picker). |

**Canonical reference:** `pages/management/documentation.blade.php` is an in-app guide listing every screen, its route, and its permission key — the source of truth for what modules exist.

## How to navigate efficiently (for the AI)
1. Start from this file — don't re-scan the tree.
2. To find a screen's backend: view name in `pages/*` → matching `*Ctrl.php` or grouped controller → model(s) → route in `web.php` → service in `app/Services` or `app/Http/Services` if it's import/leave/OT/backup.
3. Only open the specific file you'll edit. Use `documentation.blade.php` for the full screen/permission inventory.
4. Keep this map current when structure changes.

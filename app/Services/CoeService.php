<?php

namespace App\Services;

use App\Models\CoeRequest;
use App\Models\empDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Certificate of Employment business logic: the request-eligibility gate, the
 * frozen certificate snapshot, reference-number assignment, and the HR-dashboard
 * payload. The screen and the dashboard both call this service so they never
 * drift.
 */
class CoeService
{
    /**
     * Eligibility gate for raising a COE request. Returns ['ok' => bool,
     * 'missing' => string[]] describing every unmet requirement. Mirrors the
     * server-side re-check in CoeController@store so the UI and the guard agree.
     */
    public function requirements(string $employeeId): array
    {
        $missing = [];

        $detail = empDetail::with(['user'])->where('empID', $employeeId)->first();

        if (!$detail) {
            return ['ok' => false, 'missing' => ['Your employee record could not be found.']];
        }

        // 1. Active employment.
        if ((string) $detail->empStatus !== '1') {
            $missing[] = 'You must be an active (Employed) employee.';
        }
        if ($detail->flag_status === 'blacklist') {
            $missing[] = 'Your account is flagged and cannot request a certificate. Please contact HR.';
        }

        // 2. Complete profile — the fields the certificate states.
        $u = $detail->user;
        if (!$u || trim(($u->fname ?? '') . ($u->lname ?? '')) === '') {
            $missing[] = 'Your name is missing from your record.';
        }
        if (empty($detail->empPos)) {
            $missing[] = 'Your position is not set on your 201 record.';
        }
        if (empty($detail->empDepID)) {
            $missing[] = 'Your department is not set on your 201 record.';
        }
        if (empty($detail->empDateHired)) {
            $missing[] = 'Your hire date is not set on your 201 record.';
        }

        // 3. No request already awaiting review.
        if ($this->hasPending($employeeId)) {
            $missing[] = 'You already have a COE request awaiting HR review.';
        }

        return ['ok' => count($missing) === 0, 'missing' => $missing];
    }

    /** True when the employee has a request still awaiting HR review. */
    public function hasPending(string $employeeId): bool
    {
        return CoeRequest::where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Freeze the facts the certificate states, resolved from live records at
     * approval time. `$includeSalary` controls whether compensation is captured.
     */
    public function buildSnapshot(string $employeeId, bool $includeSalary): array
    {
        $detail = empDetail::with(['user', 'position', 'department', 'company', 'classification'])
            ->where('empID', $employeeId)->first();

        $u = optional($detail)->user;
        $fullName = $u ? $u->community_full_name : $employeeId;

        $hired   = $detail && $detail->empDateHired ? Carbon::parse($detail->empDateHired) : null;
        $regular = $detail && $detail->empDateRegular ? Carbon::parse($detail->empDateRegular) : null;

        // Separated (empStatus 0/2) → the certificate states PAST employment, ending
        // on the separation date; active employees read "up to the present".
        $isSeparated = $detail && (string) $detail->empStatus !== '1';
        $separation  = $isSeparated && $detail->separation_date ? Carbon::parse($detail->separation_date) : null;
        $tenureEnd   = $separation ?: Carbon::today();

        // Regular once the regularization date has arrived; otherwise probationary.
        $employmentStatus = ($regular && $regular->lte($tenureEnd)) ? 'Regular' : 'Probationary';

        $snapshot = [
            'employee_id'       => $employeeId,
            'full_name'         => $fullName ?: $employeeId,
            'position'          => optional($detail->position)->pos_desc ?: '—',
            'department'        => optional($detail->department)->dep_name ?: '—',
            'company'           => optional($detail->company)->comp_name ?: config('app.name', 'the Company'),
            // Company logo (filename stored under public/img/company) — frozen for the letterhead.
            'company_logo'      => optional($detail->company)->comp_logo_path
                ? 'img/company/' . $detail->company->comp_logo_path
                : null,
            'classification'    => optional($detail->classification)->class_desc ?: null,
            'date_hired'        => $hired ? $hired->toDateString() : null,
            'date_regular'      => $regular ? $regular->toDateString() : null,
            'employment_status' => $employmentStatus,
            'years_of_service'  => $hired ? round($hired->floatDiffInYears($tenureEnd), 2) : null,
            'include_salary'    => $includeSalary,
            'is_separated'      => $isSeparated,
            'separation_date'   => $separation ? $separation->toDateString() : null,
        ];

        if ($includeSalary) {
            $snapshot['basic']     = (float) optional($detail)->empBasic;
            $snapshot['allowance'] = (float) optional($detail)->empAllowance;
        }

        return $snapshot;
    }

    /** Next certificate reference, e.g. COE-2026-0007 (per-year sequence). */
    public function nextCertificateNo(): string
    {
        $year = Carbon::today()->format('Y');
        $count = CoeRequest::whereNotNull('certificate_no')
            ->where('certificate_no', 'like', "COE-{$year}-%")
            ->count();

        return sprintf('COE-%s-%04d', $year, $count + 1);
    }

    /** HR-dashboard payload: tallies + the pending queue with names. */
    public function dashboard(): array
    {
        $monthStart = Carbon::today()->startOfMonth()->toDateString();

        $pending = DB::table('coe_requests as r')
            ->leftJoin('users as u', 'u.empID', '=', 'r.employee_id')
            ->where('r.status', 'pending')
            ->selectRaw("r.id, r.employee_id, r.purpose, r.date_needed, r.created_at,
                TRIM(CONCAT(u.lname, ', ', u.fname)) as name")
            ->orderByDesc('r.created_at')
            ->limit(8)
            ->get()
            ->map(function ($r) {
                return [
                    'id'          => $r->id,
                    'employee_id' => $r->employee_id,
                    'name'        => $r->name ?: $r->employee_id,
                    'purpose'     => $r->purpose,
                    'date_needed' => $r->date_needed ? Carbon::parse($r->date_needed)->format('M d, Y') : null,
                    'requested'   => $r->created_at ? Carbon::parse($r->created_at)->format('M d, Y') : null,
                ];
            })->all();

        return [
            'pending' => $pending,
            'stats'   => [
                'pending'        => CoeRequest::where('status', 'pending')->count(),
                'approvedMonth'  => CoeRequest::where('status', 'approved')->whereDate('reviewed_at', '>=', $monthStart)->count(),
                'rejectedMonth'  => CoeRequest::where('status', 'rejected')->whereDate('reviewed_at', '>=', $monthStart)->count(),
                'totalMonth'     => CoeRequest::whereDate('created_at', '>=', $monthStart)->count(),
            ],
        ];
    }
}

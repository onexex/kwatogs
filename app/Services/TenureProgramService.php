<?php

namespace App\Services;

use App\Models\TenureProgram;
use App\Models\TenureProgramGrant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes tenure-milestone eligibility for active employees.
 *
 * Tenure = empDateHired → today (continuous), per the agreed business rule.
 * An employee "reaches" a program once their tenure >= the program's
 * years_required; they may reach several tiers at once (e.g. at 4 yrs they
 * qualify for both the 2-yr and 4-yr milestones). A grant row marks the
 * benefit as actually given; no row = pending.
 *
 * Kept O(employees × programs) with three bulk queries (programs, employees,
 * grants) and pure-PHP cross-referencing — safe to call from the dashboard.
 */
class TenureProgramService
{
    /** Lead time (days) for surfacing an upcoming anniversary. */
    public const UPCOMING_DAYS = 60;

    public function eligibility(): array
    {
        $programs = TenureProgram::with('benefits')
            ->where('is_active', true)
            ->orderBy('years_required')
            ->get();

        $empty = [
            'reached'  => [],
            'upcoming' => [],
            'stats'    => [
                'programs'      => $programs->count(),
                'reachedCount'  => 0,
                'pendingCount'  => 0,
                'grantedCount'  => 0,
                'upcomingCount' => 0,
            ],
        ];

        if ($programs->isEmpty()) {
            return $empty;
        }

        $emps = DB::table('emp_details as e')
            ->join('users as u', 'u.empID', '=', 'e.empID')
            ->where('e.empStatus', '1')               // active (Employed) only
            ->whereNotNull('e.empDateHired')
            ->selectRaw("e.empID as empid,
                TRIM(CONCAT(u.lname, ', ', u.fname)) as name,
                e.empDateHired as hired,
                COALESCE((SELECT dep_name FROM departments WHERE id = e.empDepID), '—') as dept")
            ->get();

        // All grants keyed by "<programId>|<empId>" for O(1) lookup.
        $grants = TenureProgramGrant::all()->keyBy(
            fn ($g) => $g->tenure_program_id . '|' . $g->employee_id
        );

        $today    = Carbon::today();
        $reached  = [];
        $upcoming = [];
        $pending  = 0;
        $granted  = 0;

        foreach ($emps as $emp) {
            $hired  = Carbon::parse($emp->hired);
            $tenure = round($hired->floatDiffInYears($today), 2);

            foreach ($programs as $p) {
                $benefits = $p->benefits
                    ->map(fn ($b) => $b->name . ($b->description ? ' (' . $b->description . ')' : ''))
                    ->values()
                    ->all();

                if ($tenure >= $p->years_required) {
                    $grant  = $grants->get($p->id . '|' . $emp->empid);
                    $status = $grant ? $grant->status : 'pending';
                    $status === 'granted' ? $granted++ : $pending++;

                    $reached[] = [
                        'employee_id' => $emp->empid,
                        'name'        => $emp->name,
                        'dept'        => $emp->dept,
                        'tenure'      => $tenure,
                        'program_id'  => $p->id,
                        'program'     => $p->title,
                        'years'       => (float) $p->years_required,
                        'benefits'    => $benefits,
                        'status'      => $status,
                        'granted_at'  => $grant && $grant->granted_at ? $grant->granted_at->format('M d, Y') : null,
                        'granted_by'  => $grant->granted_by ?? null,
                    ];
                } else {
                    $yearsRemaining = $p->years_required - $tenure;
                    $daysRemaining  = (int) ceil($yearsRemaining * 365.25);

                    if ($daysRemaining > 0 && $daysRemaining <= self::UPCOMING_DAYS) {
                        $upcoming[] = [
                            'employee_id' => $emp->empid,
                            'name'        => $emp->name,
                            'dept'        => $emp->dept,
                            'tenure'      => $tenure,
                            'program_id'  => $p->id,
                            'program'     => $p->title,
                            'years'       => (float) $p->years_required,
                            'benefits'    => $benefits,
                            'days'        => $daysRemaining,
                            'date'        => $today->copy()->addDays($daysRemaining)->format('M d, Y'),
                        ];
                    }
                }
            }
        }

        // Pending first, then highest milestone first — the work HR still owes.
        usort($reached, function ($a, $b) {
            if ($a['status'] !== $b['status']) {
                return $a['status'] === 'pending' ? -1 : 1;
            }
            return $b['years'] <=> $a['years'];
        });

        usort($upcoming, fn ($a, $b) => $a['days'] <=> $b['days']);

        return [
            'reached'  => $reached,
            'upcoming' => $upcoming,
            'stats'    => [
                'programs'      => $programs->count(),
                'reachedCount'  => count($reached),
                'pendingCount'  => $pending,
                'grantedCount'  => $granted,
                'upcomingCount' => count($upcoming),
            ],
        ];
    }
}

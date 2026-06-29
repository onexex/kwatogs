<?php

namespace App\Services;

use App\Models\Notice;
use App\Models\SuspensionRecommendation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Disciplinary-notice escalation + reporting.
 *
 * "Committed notices" = active disciplinary notices (type='disciplinary',
 * status='active'). Memos never count. Thresholds:
 *   - WARN    (3): employee is approaching the limit — surfaced as a warning.
 *   - SUSPEND (4): employee exceeds the limit — name shown in the HR alert
 *     flash and an auto suspension recommendation is created (once, pending).
 */
class NoticeService
{
    /** Disciplinary-notice count that flags an employee as "at risk". */
    public const WARN = 3;

    /** Disciplinary-notice count that triggers a suspension recommendation. */
    public const SUSPEND = 4;

    /** Active disciplinary-notice count for one employee. */
    public function disciplinaryCount(string $employeeId): int
    {
        return Notice::where('employee_id', $employeeId)
            ->where('type', 'disciplinary')
            ->where('status', 'active')
            ->count();
    }

    /**
     * Re-evaluate an employee after a disciplinary notice changes. If they are
     * at/over the SUSPEND threshold and have no open recommendation, create one
     * with an automatic reason. Returns the count for convenience.
     */
    public function evaluateEscalation(string $employeeId): int
    {
        $count = $this->disciplinaryCount($employeeId);

        if ($count >= self::SUSPEND) {
            $existing = SuspensionRecommendation::where('employee_id', $employeeId)
                ->where('status', 'pending')
                ->exists();

            if (!$existing) {
                $rec = new SuspensionRecommendation();
                $rec->employee_id    = $employeeId;
                $rec->notice_count   = $count;
                $rec->reason         = "Automatically recommended for suspension: accumulated {$count} active disciplinary notices "
                                     . "(company policy threshold: " . self::SUSPEND . "). HR review required.";
                $rec->status         = 'pending';
                $rec->recommended_by = 'System (auto)';
                $rec->recommended_at = Carbon::now();
                $rec->save();   // instance save → Auditable
            }
        }

        return $count;
    }

    /**
     * Map of employee_id => active disciplinary count (>0 only), with names.
     * One grouped query + one name lookup; safe for the dashboard.
     */
    public function offenderCounts(): \Illuminate\Support\Collection
    {
        $counts = Notice::where('type', 'disciplinary')
            ->where('status', 'active')
            ->selectRaw('employee_id, COUNT(*) as c')
            ->groupBy('employee_id')
            ->pluck('c', 'employee_id');

        if ($counts->isEmpty()) {
            return collect();
        }

        $names = DB::table('users')
            ->whereIn('empID', $counts->keys())
            ->selectRaw("empID, TRIM(CONCAT(lname, ', ', fname)) as name")
            ->pluck('name', 'empID');

        return $counts->map(function ($c, $empId) use ($names) {
            return [
                'employee_id' => $empId,
                'name'        => $names[$empId] ?? $empId,
                'count'       => (int) $c,
            ];
        })->values();
    }

    /** Names (for the alert flash) of employees at/over the suspend threshold. */
    public function flashOffenders(): array
    {
        return $this->offenderCounts()
            ->filter(fn ($o) => $o['count'] >= self::SUSPEND)
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /** Dashboard payload: at-risk, over-limit, pending recommendations + tallies. */
    public function dashboard(): array
    {
        $offenders = $this->offenderCounts();

        $atRisk = $offenders->filter(fn ($o) => $o['count'] >= self::WARN && $o['count'] < self::SUSPEND)
            ->sortByDesc('count')->values()->all();
        $over = $offenders->filter(fn ($o) => $o['count'] >= self::SUSPEND)
            ->sortByDesc('count')->values()->all();

        $pendingRecs = SuspensionRecommendation::where('status', 'pending')
            ->orderByDesc('recommended_at')
            ->get()
            ->map(function ($r) {
                $name = DB::table('users')->where('empID', $r->employee_id)
                    ->selectRaw("TRIM(CONCAT(lname, ', ', fname)) as name")->value('name');
                return [
                    'id'             => $r->id,
                    'employee_id'    => $r->employee_id,
                    'name'           => $name ?: $r->employee_id,
                    'notice_count'   => $r->notice_count,
                    'reason'         => $r->reason,
                    'recommended_at' => optional($r->recommended_at)->format('M d, Y'),
                ];
            })->all();

        $monthStart = Carbon::today()->startOfMonth()->toDateString();

        return [
            'atRisk'         => $atRisk,
            'over'           => $over,
            'pendingRecs'    => $pendingRecs,
            'stats'          => [
                'warn'           => self::WARN,
                'suspend'        => self::SUSPEND,
                'atRiskCount'    => count($atRisk),
                'overCount'      => count($over),
                'pendingRecs'    => count($pendingRecs),
                'issuedThisMonth'=> Notice::whereDate('issued_at', '>=', $monthStart)->count(),
                'activeDisc'     => Notice::where('type', 'disciplinary')->where('status', 'active')->count(),
            ],
        ];
    }
}

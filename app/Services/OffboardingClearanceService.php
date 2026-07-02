<?php

namespace App\Services;

use App\Models\empDetail;

/**
 * Offboarding clearance checklist — the single source of truth for which items
 * exist, which apply to which exit type, and whether an employee's clearance is
 * complete. The E-201 "Update Status" modal, the dossier card, and the COE
 * issuance gate all read from here so they never drift (the role NoticeService
 * plays for disciplinary thresholds).
 *
 * Items are HR-attested booleans on emp_details (cl_* columns) — no uploads.
 * Applicability keys off emp_details.empStatus: '0'=Resigned, '2'=End of
 * Contract / Terminated. Active ('1') employees have no clearance.
 */
class OffboardingClearanceService
{
    /**
     * key => [label, column, applies (empStatus values)].
     * The three shared items apply to every separated employee; the first two
     * are mutually exclusive by exit type.
     */
    public const ITEMS = [
        'resignation_letter' => ['label' => 'Resignation Letter',            'column' => 'cl_resignation_letter', 'applies' => ['0']],
        'office_notice'      => ['label' => 'Signed Notice from Office',     'column' => 'cl_office_notice',      'applies' => ['2']],
        'clearance_form'     => ['label' => 'Clearance Form',                'column' => 'cl_clearance_form',     'applies' => ['0', '2']],
        'company_items'      => ['label' => 'Return of Company-Issued Items','column' => 'cl_company_items',      'applies' => ['0', '2']],
        'quitclaim'          => ['label' => 'Signed/Received Quitclaim',     'column' => 'cl_quitclaim',          'applies' => ['0', '2']],
    ];

    /** All clearance column names (for resetting on re-activation). */
    public static function columns(): array
    {
        return array_map(fn ($i) => $i['column'], self::ITEMS);
    }

    /** Items that apply to a given employment status (keyed by item key). */
    public function applicableItems(string $empStatus): array
    {
        return array_filter(self::ITEMS, fn ($i) => in_array($empStatus, $i['applies'], true));
    }

    /** True when every applicable clearance item is ticked for this employee. */
    public function isComplete(empDetail $detail): bool
    {
        return count($this->missingItems($detail)) === 0;
    }

    /** Labels of the applicable clearance items still outstanding. */
    public function missingItems(empDetail $detail): array
    {
        $status  = (string) $detail->empStatus;
        $missing = [];

        foreach ($this->applicableItems($status) as $item) {
            if (!$detail->{$item['column']}) {
                $missing[] = $item['label'];
            }
        }

        return $missing;
    }

    /**
     * Render-friendly status of each applicable item for the dossier / issue UI:
     * [['key','label','done'=>bool,'reference'=>?string], …].
     */
    public function statusFor(empDetail $detail): array
    {
        $refs = is_array($detail->clearance_refs) ? $detail->clearance_refs : [];
        $out  = [];

        foreach ($this->applicableItems((string) $detail->empStatus) as $key => $item) {
            $out[] = [
                'key'       => $key,
                'label'     => $item['label'],
                'done'      => (bool) $detail->{$item['column']},
                'reference' => $refs[$key] ?? null,
            ];
        }

        return $out;
    }
}

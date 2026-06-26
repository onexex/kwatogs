<?php

namespace App\Services\Concerns;

use App\Models\ImportBatch;

/**
 * Shared helper for the import services: opens a batch row to tag every record an
 * import creates, so the import can later be pulled up and rolled back as a unit.
 */
trait CreatesImportBatch
{
    protected function createImportBatch(string $module, ?string $filename, int $rowCount, ?string $dateFrom, ?string $dateTo): ImportBatch
    {
        return ImportBatch::create([
            'module'    => $module,
            'filename'  => $filename,
            'user_id'   => optional(auth()->user())->id,
            'user_name' => $this->importUserName(),
            'row_count' => $rowCount,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]);
    }

    /** Display name for the current user (mirrors AuditLog::record's naming). */
    protected function importUserName(): string
    {
        $u = auth()->user();
        if (!$u) { return 'system'; }
        return trim(($u->fname ?? '') . ' ' . ($u->lname ?? '')) ?: ($u->name ?? 'User');
    }
}

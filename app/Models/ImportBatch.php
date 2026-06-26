<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per successful import (attendance / overtime / leave). Every record the
 * import created is tagged with this batch's id via `import_batch_id`, so the whole
 * import can be listed and rolled back together, then re-uploaded corrected.
 */
class ImportBatch extends Model
{
    use HasFactory;

    protected $table = 'import_batches';

    protected $fillable = [
        'module',
        'filename',
        'user_id',
        'user_name',
        'row_count',
        'inserted',
        'updated',
        'date_from',
        'date_to',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to'   => 'date',
    ];

    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

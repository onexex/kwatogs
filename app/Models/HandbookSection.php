<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * One section/chapter of the Employee Handbook. Authored by HR, read by
 * employees in the My Handbook workspace. Optionally carries a supporting
 * document and can require an employee acknowledgement.
 */
class HandbookSection extends Model
{
    use Auditable;

    protected $fillable = [
        'title', 'slug', 'body', 'sort_order', 'is_published', 'requires_ack', 'is_master',
        'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size',
        'version', 'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'requires_ack' => 'boolean',
        'is_master'    => 'boolean',
        'sort_order'   => 'integer',
        'version'      => 'integer',
    ];

    // The large body blob is noisy in the trail; keep the diff meaningful.
    protected static function auditIgnore(): array
    {
        return ['updated_at', 'created_at'];
    }

    public function acknowledgements()
    {
        return $this->hasMany(HandbookAcknowledgement::class, 'section_id');
    }
}

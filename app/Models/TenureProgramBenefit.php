<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single benefit under a tenure-milestone program (e.g. "Bigas (Rice)").
 */
class TenureProgramBenefit extends Model
{
    protected $fillable = ['tenure_program_id', 'name', 'description'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TenureProgram::class, 'tenure_program_id');
    }
}

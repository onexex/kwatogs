<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A tenure-milestone program (Programs Management).
 * Holds the years-of-service threshold and its benefits.
 */
class TenureProgram extends Model
{
    use Auditable;

    protected $fillable = ['title', 'years_required', 'description', 'is_active'];

    protected $casts = [
        'years_required' => 'float',
        'is_active'      => 'boolean',
    ];

    public function benefits(): HasMany
    {
        return $this->hasMany(TenureProgramBenefit::class);
    }

    public function grants(): HasMany
    {
        return $this->hasMany(TenureProgramGrant::class);
    }
}

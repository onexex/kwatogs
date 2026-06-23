<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Backs the PhilHealth Contribution settings screen (pages/management/philhealth
 * → public/js/settings/philhealth.js → philhealthCtrl). Maps to the legacy
 * `philhealth` table (migration create_philhealth_models_table). This is the
 * HR-maintained bracket table, NOT App\Models\PhilhealthContribution (the payroll
 * rate table read by App\Helpers\ContributionHelper) — different schema/purpose.
 */
class philhealthModel extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;

    protected $table = 'philhealth';

    protected $fillable = [
        'phsb',
        'salaryFrom',
        'salaryTo',
        'phee',
        'pher',
    ];
}

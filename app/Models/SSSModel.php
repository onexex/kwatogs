<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Backs the SSS Contribution Table settings screen (pages/management/ssscontribution
 * → public/js/settings/sss.js → sssCtrl). This is the manually-maintained bracket
 * table edited by HR, NOT App\Models\SssContribution (the payroll rate table read by
 * App\Helpers\ContributionHelper) — different schema, different purpose.
 */
class SSSModel extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;

    protected $table = 'sss';

    protected $fillable = [
        'sssc',
        'from',
        'to',
        'sser',
        'ssee',
        'ssec',
    ];
}

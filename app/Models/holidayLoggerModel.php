<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class holidayLoggerModel extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;
    protected $table = 'holiday_logger';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'date',
        'description',
        'type',
        'department_id',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(department::class, 'department_id', 'id');
    }
}

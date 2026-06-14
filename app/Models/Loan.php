<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Loan extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'loan_type',
        'other_description',
        'loan_amount',
        'balance',
        'monthly_amortization',
        'start_date',
        'end_date',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'empID');
    }
}

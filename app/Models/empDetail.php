<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpDetail extends Model
{
    use HasFactory;

    protected $table = 'emp_details';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'empID', 'empISID', 'empDepID', 'empCompID', 'empClassification', 'empPos',
        'empBasic', 'empStatus', 'empAllowance', 'empHrate', 'empWday', 'empJobLevel',
        'empAgencyID', 'empHMOID', 'empHMONo', 'empPicPath', 'empDateHired',
        'empDateResigned', 'empDateRegular', 'empPrevPos', 'empPrevDep',
        'empPrevWorkStartDate', 'empPassport', 'empPassportExpDate', 'empPassportIssueAuth',
        'empPagibig', 'empPhilhealth', 'empSSS', 'empTIN', 'empUMID', 'empPrevDesignation'
    ];

    protected $casts = [
        'empBasic' => 'float',
        'empAllowance' => 'float',
        'empHrate' => 'float',
        'empWday' => 'integer',
        'empDateHired' => 'date',
        'empDateResigned' => 'date',
        'empDateRegular' => 'date',
    ];

    public function company(): BelongsTo
    {
        // 1. Related Model: Company
        // 2. Foreign Key (in emp_details): 'empCompID'
        // 3. Owner Key (in companies): 'comp_id'
        return $this->belongsTo(company::class, 'empCompID', 'comp_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(department::class, 'empDepID', 'id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(position::class, 'empPos', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'empID', 'empID');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(agencies::class, 'empAgencyID', 'id');
    }
   
    public function jobLevel(): BelongsTo
    {
        return $this->belongsTo(joblevel::class, 'empJobLevel', 'id');
    }
   
    public function hmo(): BelongsTo
    {
        return $this->belongsTo(HMOModel::class, 'empHMOID', 'id');
    }
   
    public function classification(): BelongsTo
    {
        return $this->belongsTo(classification::class, 'empClassification', 'class_code');
    }

    public function getSalaryInfo()
    {
        return [
            'classification' => $this->empClassification,
            'status' => $this->empStatus,
            'basic' => $this->empBasic,
            'allowance' => $this->empAllowance,
            'hourly_rate' => $this->empHrate,
            'work_days' => $this->empWday,
        ];
    }
}

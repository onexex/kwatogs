<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class empDetail extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'emp_details';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'empID', 'empISID', 'empDepID', 'empCompID', 'empClassification', 'empPos',
        'empBasic', 'empStatus', 'empAllowance', 'empPayrollType', 'empCardNo', 'empHrate', 'empWday', 'empJobLevel',
        'empAgencyID', 'empHMOID', 'empHMONo', 'empPicPath', 'empDateHired',
        'empDateResigned', 'empDateRegular', 'empPrevPos', 'empPrevDep',
        'empPrevWorkStartDate', 'empPassport', 'empPassportExpDate', 'empPassportIssueAuth',
        'empPagibig', 'empPhilhealth', 'empSSS', 'empTIN', 'empUMID', 'empPrevDesignation',
        // Per-employee government-dues enrolment toggles (default enrolled)
        'sss_enabled', 'philhealth_enabled', 'pagibig_enabled',
    ];

    protected $casts = [
        'empBasic' => 'float',
        'empAllowance' => 'float',
        'empHrate' => 'float',
        'empWday' => 'integer',
        'empDateHired' => 'date',
        'empDateResigned' => 'date',
        'empDateRegular' => 'date',
        'sss_enabled' => 'boolean',
        'philhealth_enabled' => 'boolean',
        'pagibig_enabled' => 'boolean',
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

    public function employeeInformation(): BelongsTo
    {
        return $this->belongsTo(emp_info::class, 'empID', 'empID');
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
   
    public function immediateSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'empISID', 'empID');
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

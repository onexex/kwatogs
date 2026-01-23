<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $table = 'leaves';

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'leave_type',
        'total_hrs',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'approved_at' => 'datetime',
    ];

    // ==============================
    // 🔗 RELATIONSHIPS
    // ==============================

    public function employee()
    {
        // matches emp_details.empID
        return $this->belongsTo(empDetail::class, 'employee_id', 'empID');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    // ==============================
    // 🧩 SCOPES
    // ==============================

    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'Rejected');
    }

    // ==============================
    // 🧮 ACCESSORS / HELPERS
    // ==============================

    public function getDurationAttribute()
    {
        return $this->start_date && $this->end_date
            ? $this->start_date->diffInDays($this->end_date) + 1
            : 0;
    }
}

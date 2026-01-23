<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'empID',
        'email',
        'password',
        
        'status',
        'suffix',
        'lname',
        'fname',
        'mname',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    // protected $hidden = [
    //     'password',
    //     'remember_token',
    // ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

     // 🧩 Relationship
    public function empDetail()
    {
        return $this->hasOne(empDetail::class, 'empID', 'empID');
    }

    public function attendanceSummaries()
    {
        return $this->hasMany(AttendanceSummary::class, 'employee_id', 'empID');
    }

    public function education()
    {
        return $this->hasMany(emp_education::class, 'empID', 'empID');
    }

    public function employeeInformation()
    {
        return $this->belongsTo(emp_info::class, 'empID', 'empID');
    }
}

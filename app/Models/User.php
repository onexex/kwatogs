<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
 use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;
    use \App\Traits\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'empID',
        'email',
        'username',
        'password',
        'must_change_password',

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
        'must_change_password' => 'boolean',
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

    public function employmentDocuments()
    {
        return $this->hasMany(EmployeeDocument::class, 'user_id', 'id')->latest();
    }

    public function employeeInformation()
    {
        return $this->belongsTo(emp_info::class, 'empID', 'empID');
    }

    // KuBo Community Relationships

    public function communityPosts()
    {
        return $this->hasMany(CommunityPost::class, 'user_id', 'empID');
    }

    public function communityReactions()
    {
        return $this->hasMany(CommunityPostReaction::class, 'user_id', 'empID');
    }

    public function communityComments()
    {
        return $this->hasMany(CommunityComment::class, 'user_id', 'empID');
    }

    public function communityReposts()
    {
        return $this->hasMany(CommunityRepost::class, 'user_id', 'empID');
    }

    public function communityNotifications()
    {
        return $this->hasMany(CommunityNotification::class, 'user_id', 'empID');
    }

    public function getCommunityAvatarAttribute(): string
    {
        $picPath = $this->empDetail?->empPicPath;
        if ($picPath && file_exists(public_path($picPath))) {
            return asset($picPath);
        }
        return asset('img/undraw_profile.svg');
    }

    public function getCommunityFullNameAttribute(): string
    {
        $fname = $this->fname ?? '';
        $mname = $this->mname ?? '';
        $lname = $this->lname ?? '';
        $suffix = $this->suffix ?? '';

        $fullName = trim($fname . ' ' . ($mname ? strtoupper(substr($mname, 0, 1)) . '. ' : '') . $lname . ' ' . $suffix);
        return $fullName ?: 'Demo Employee';
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'employee_id', 'empID');
    }

    /**
     * IP addresses added to the allowlist by this user.
     */
    public function allowedIps()
    {
        return $this->hasMany(AllowedIp::class, 'created_by', 'empID');
    }
    
    protected function empID(): Attribute
    {
        return Attribute::make(
            // Pag kinuha ang data, i-format bilang 3 digits (e.g. 1 -> 001)
            get: fn ($value) => str_pad($value, 3, '0', STR_PAD_LEFT),
        );
    }
}

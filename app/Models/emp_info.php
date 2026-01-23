<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class emp_info extends Model
{
    use HasFactory;
    protected $table = 'emp_infos';
    protected $primaryKey = 'id ';
    public $timestamps = true;   

    protected $fillable = [
        'empID',
        'empBdate',
        'empCStatus',
        'empReligion',
        'empPContact',
        'empHContact',
        'empEmail',
        'empAddStreet',
        'empAddCityDesc',
        'empAddCity',
        'empAddBrgyDesc',
        'empAddBrgy',
        'empProvDesc',
        'empProv',
        'empZipcode',
        'empCountry',
        'gender',
        'citizenship',
    ];

     // 🔗 Relationship back to User
    public function user()
    {
        return $this->belongsTo(User::class, 'empID', 'empID');
    }
}

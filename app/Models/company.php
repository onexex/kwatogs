<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model // Capitalized class name to match Laravel PSR standards
{
    use HasFactory;
    use \App\Traits\Auditable;
    
    protected $table = 'companies';
    protected $primaryKey = 'id'; // Fixed the trailing space here
    public $timestamps = true;   

    protected $fillable = [
        'comp_id',
        'comp_name',
        'comp_code',
        'comp_color',
        'comp_logo_path',
    ];

    /**
     * Get all payrolls associated with the company.
     */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'company_id', 'id');
    }
}
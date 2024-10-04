<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Payslip extends Model
{
    use HasFactory;

    protected $table = 'payslips';

    protected $fillable = [
        'EmployeeID',
        'PayrollDate',
        'TotalEarnings',
        'GrossPay',
        'TotalDeductions',
        'NetPay',
        'PayrollDate2',
        'PayrollFrequency',
        'EmployeeStatus',
        'PayrollMonth',
        'PayrollYear',
        'ProjectID',
        'weekPeriodID',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'ProjectID');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
    public function dates()
    {
        return $this->hasMany(PayrollDate::class); // Adjust class name if necessary
    }
}

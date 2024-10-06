<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payroll';

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
                'assignment',
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
}

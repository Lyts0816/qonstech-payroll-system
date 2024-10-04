<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $table = 'loan';

    protected $fillable = [
        'EmployeeID',
        'LoanType',
        'LoanAmount',
        'Balance',
        //'MonthlyDeduction',
        'WeeklyDeduction',
        'NumberOfPayments',
        'PeriodID',
        //'EndDate',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }

    public function weekperiod()
    {
        return $this->belongsTo(WeekPeriod::class, 'PeriodID');
    }

}

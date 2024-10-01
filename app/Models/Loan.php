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
        'StartDate',
        //'EndDate',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }

}

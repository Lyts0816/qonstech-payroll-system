<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'employees';

    protected $fillable = [
        'EmployeeID',
        'PayrollDate',
        'TotalEarnings',
        'GrossPay',
        'TotalDeductions',
        'NetPay',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }
}

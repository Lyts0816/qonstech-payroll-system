<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Earnings extends Model
{
    use HasFactory;

    protected $table = 'earnings';

    protected $fillable = [
        'EmployeeID',
        'EarningType',
        'Amount',
        'StartDate',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }

    // public function overtime()
    // {
    //     return $this->belongsTo(Overtime::class, 'OvertimeID');
    // }
}

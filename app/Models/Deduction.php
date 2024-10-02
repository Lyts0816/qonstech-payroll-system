<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deduction extends Model
{
    use HasFactory;

    protected $table = 'deductions';

    
    protected $fillable = [
        'employeeID',
        'DeductionType',
        'Amount',
        'StartDate',
    ];

    // Define the relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeID');
    }

}

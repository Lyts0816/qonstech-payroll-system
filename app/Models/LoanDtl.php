<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanDtl extends Model
{
    use HasFactory;

    protected $table = 'loandtl'; // Specify the table name if it's different from the pluralized model name

    protected $fillable = [
        'loanid',
        'sequence',
        'tran_date',
        'PeriodID',
        'Amount',
        'IsPaid',
        'IsRenewed',
    ];

    // Optionally, define relationships here
    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loanid', 'id'); // Adjust 'id' to the primary key of the Loan model
    }
}

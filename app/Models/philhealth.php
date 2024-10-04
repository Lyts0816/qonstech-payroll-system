<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class philhealth extends Model
{
    use HasFactory;

    protected $table = 'philhealth';

    protected $fillable = [
        'MinSalary',
        'MaxSalary',
        'PremiumRate',
        'ContributionAmount',
    ];
}

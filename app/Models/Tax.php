<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    protected $table = 'tax';

    protected $fillable = [
        'MinSalary',
        'MaxSalary',
        'base_rate',
        'excess_percent',
    ];
}
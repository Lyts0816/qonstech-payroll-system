<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTime;

class WeekPeriod extends Model
{
    use HasFactory;

    protected $table = 'weekperiod'; // Explicitly setting the table name

    protected $fillable = [
        'StartDate',
        'EndDate',
        'Month',
        'Year',
        'Category',
        'Type',
    ];

		public function getTypeWithMonthAttribute()
{
    $monthName = DateTime::createFromFormat('!m', $this->Month)->format('F');
    return "{$this->Type} - {$monthName}";
}
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
	use HasFactory;

	protected $table = 'holidays';

	protected $fillable = [
		'HolidayName',
		'HolidayDate',
		'HolidayType',
		'ProjectID'
	];

	public function project()
	{
		return $this->belongsTo(Project::class, 'ProjectID');
	}

}

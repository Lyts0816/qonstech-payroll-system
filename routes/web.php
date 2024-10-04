<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route for viewing DTR
use App\Http\Controllers\AttendanceController;

// Other routes...

Route::get('/dtr/show', [AttendanceController::class, 'showDtr'])->name('dtr.show');


Route::redirect('/', '/admin/login');




<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route for viewing DTR
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PayrollController;


// Other routes...

Route::get('/dtr/show', [AttendanceController::class, 'showDtr'])->name('dtr.show');
Route::get('/payroll-records', [PayrollController::class, 'showPayrollRecords'])->name('payroll-records');


Route::redirect('/', '/admin/login');

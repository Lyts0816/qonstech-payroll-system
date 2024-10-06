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
use App\Http\Controllers\PayslipController;

Route::get('/payslip-records/{EmployeeID}', [PayslipController::class, 'show'])->name('payslip-records');

Route::get('/payroll-report', [PayrollController::class, 'showReport'])->name('payroll-report');

Route::redirect('/', '/admin/login');





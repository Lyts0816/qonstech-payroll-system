<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route for viewing DTR
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipController;


// Other routes...

Route::get('/dtr/show', [AttendanceController::class, 'showDtr'])->name('dtr.show');


// Route::get('/generate-payslips', [PayslipController::class, 'generatePayslips'])->name('generate.payslips');
Route::get('/generate-payslips', [PayslipController::class, 'generatePayslips'])->name('generate.payslips');

Route::get('/payroll-report', [PayrollController::class, 'showReport'])->name('payroll-report');

Route::redirect('/', '/admin/login');





<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route for viewing DTR
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TransferController;




// Other routes...

Route::get('/dtr/show', [AttendanceController::class, 'showDtr'])->name('dtr.show');


Route::get('/generate-payslips', [PayslipController::class, 'generatePayslips'])->name('generate.payslips');
Route::get('/generate-reports', [ReportsController::class, 'generateReports'])->name('generate.reports');

Route::get('/payroll-report', [PayrollController::class, 'showReport'])->name('payroll-report');
Route::get('/transfer', [TransferController::class, 'runTransfer']);

Route::redirect('/', '/admin/login');





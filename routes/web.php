<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route for viewing DTR
use App\Http\Controllers\AttendanceController;


// Other routes...

Route::get('/dtr/show', [AttendanceController::class, 'showDtr'])->name('dtr.show');
use App\Http\Controllers\PayslipController;

Route::get('/generate-payslips/{projectId}', [PayslipController::class, 'generatePayslips'])->name('generate.payslips');// Route::get('/payslip-records/{ProjectID}', [PayslipController::class, 'show'])->name('payslip-records');


Route::redirect('/', '/admin/login');




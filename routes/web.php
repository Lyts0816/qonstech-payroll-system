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

Route::get('/payslip-records/{EmployeeID}', [PayslipController::class, 'show'])->name('payslip-records');


Route::redirect('/', '/admin/login');




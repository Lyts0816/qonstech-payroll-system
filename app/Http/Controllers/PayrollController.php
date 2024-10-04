<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee; // Adjust based on your actual Employee model
use App\Models\Payroll; // Adjust based on your actual Payroll model

class PayrollController extends Controller
{
    // public function showPayrollRecords()
    // {
    //     // Retrieve payroll records from session
    //     $payrollRecords = session('payrollRecords', []);

    //     // Fetch the employees and payroll data accordingly
    //     // Assuming you have an Employee model and Payroll model
    //     $employees = Employee::whereIn('id', $payrollRecords->pluck('employee_id'))->get();

    //     // Fetch the payroll details (dates, etc.)
    //     $payroll = Payroll::with('dates')->find($payrollRecords->first()->payroll_id);

    //     // Pass the records to the view
    //     return view('payroll.records', compact('employees', 'payroll'));
    // }

    public function showPayrollRecords(Request $request)
    {
        // Retrieve the employee ID from the request (if needed)
        $employeeId = $request->get('EmployeeID');

        // Fetch the payroll records based on the employee ID
        $payrollRecords = Payroll::where('EmployeeID', $employeeId)->get();
        dd($payrollRecords);

        // Pass the records to the view
        return view('payroll.records', compact('payrollRecords'));
    }

}

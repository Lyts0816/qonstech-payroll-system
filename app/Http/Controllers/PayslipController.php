<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\WeekPeriod;

class PayslipController extends Controller
{
    public function show(Request $request, $EmployeeID, )
    {
        // Fetch employee details with position
        $employee = Employee::with('position') // Eager load the related position
            ->findOrFail($EmployeeID);

        // Ensure the employee has an associated position
        if ($employee->position) {
            // Prepare the payroll record data
            $payrollRecords = collect([
                [
                    'first_name' => $employee->first_name,
                    'middle_name' => $employee->middle_name,
                    'last_name' => $employee->last_name,
                    'position' => $employee->position->PositionName,
                    'monthlySalary' => $employee->position->MonthlySalary,
                    'hourlyRate' => $employee->position->HourlyRate,
                    'SalaryType' => 'OPEN',
                    'RegularStatus' => $employee->employment_type == 'Regular' ? 'YES' : 'NO',
                ]
            ]);
        } else {
            // Handle case where no position is associated
            $payrollRecords = collect([]);
        }

        // Fetch Earnings
        $getEarnings = \App\Models\Earnings::where('PeriodID', $request->weekPeriodID)
            ->where('EmployeeID', $EmployeeID)
            ->get();

        $earnings = $getEarnings->sum('Amount'); // Sum all earnings

        // Fetch Deductions
        $getDeductions = \App\Models\Deduction::where('PeriodID', $request->weekPeriodID)
            ->where('EmployeeID', $EmployeeID)
            ->get();

        $deductions = $getDeductions->sum('Amount'); // Sum all deductions

        // Fetch SSS, Pag-Ibig, and PhilHealth deductions
        $sss = $this->calculateSSS($employee, $request->PayrollFrequency);
        $pagIbig = $this->calculatePagIbig($employee, $request->PayrollFrequency);
        $philHealth = $this->calculatePhilHealth($employee, $request->PayrollFrequency);

        // Total deductions including government contributions
        $totalDeductions = $deductions + $sss + $pagIbig + $philHealth;

        // Calculate Net Pay (Total Earnings - Total Deductions)
        $netPay = $earnings - $totalDeductions;

        

        // Pass these values to the view
        return view('payslip.records', compact(
            'payrollRecords',
            'earnings',
            'deductions',
            'sss',
            'pagIbig',
            'philHealth',
            'totalDeductions',
            'netPay'
        ));
    }

    private function calculateSSS($employee, $payrollFrequency)
    {
        $getSSS = \App\Models\Sss::get();
        foreach ($getSSS as $sss) {
            if ($sss->MinSalary <= $employee->MonthlySalary && $sss->MaxSalary >= $employee->MonthlySalary) {
                return $payrollFrequency == 'Kinsenas' ? $sss->EmployeeShare / 2 : $sss->EmployeeShare / 4;
            }
        }
        return 0;
    }

    private function calculatePagIbig($employee, $payrollFrequency)
    {
        $getPagibig = \App\Models\Pagibig::get();
        foreach ($getPagibig as $pagibig) {
            if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
                $deduction = ($pagibig->EmployeeRate / 100) * $employee->MonthlySalary;
                return $payrollFrequency == 'Kinsenas' ? $deduction / 2 : $deduction / 4;
            }
        }
        return 0;
    }

    private function calculatePhilHealth($employee, $payrollFrequency)
    {
        $getPhilHealth = \App\Models\PhilHealth::get();
        foreach ($getPhilHealth as $philhealth) {
            if ($philhealth->MinSalary <= $employee->MonthlySalary && $philhealth->MaxSalary >= $employee->MonthlySalary) {
                if ($philhealth->PremiumRate == '0.00') {
                    return $payrollFrequency == 'Kinsenas' ? $philhealth->ContributionAmount / 2 : $philhealth->ContributionAmount / 4;
                } else {
                    $deduction = ($philhealth->PremiumRate / 100) * $employee->MonthlySalary;
                    return $payrollFrequency == 'Kinsenas' ? $deduction / 2 : $deduction / 4;
                }
            }
        }
        return 0;
    }



}

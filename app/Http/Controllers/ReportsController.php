<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Dompdf\Options;

class ReportsController extends Controller
{

    public function generateReports(Request $request)
    {
        $dompdf = new Dompdf();
        // Render each employee's payslip
        $payslipHtml = '';


        $employeesWPosition = \App\Models\Employee::where('employment_type', $request->EmployeeStatus)
            ->join('positions', 'employees.position_id', '=', 'positions.id')
            ->select('employees.*', 'positions.PositionName', 'positions.MonthlySalary', 'positions.HourlyRate'); // Only select needed fields
        $validator = Validator::make($request->all(), [
            'EmployeeStatus' => 'required|string',
            'assignment' => 'required|string',
            'ProjectID' => 'nullable|string|integer', // Adjust according to your requirement
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Query for employees with their position
        $employeesWPosition = \App\Models\Employee::where('employment_type', $request->EmployeeStatus)
            ->join('positions', 'employees.position_id', '=', 'positions.id')
            ->select('employees.*', 'positions.PositionName', 'positions.MonthlySalary', 'positions.HourlyRate');

        // Check if the assignment is project-based
        if ($request->assignment === 'Project Based') {
            // If project-based, filter by project
            $employeesWPosition = $employeesWPosition->whereNotNull('project_id') // Ensure the employee has a project
                ->where('project_id', $request->ProjectID); // Filter by specific project
        } else {
            // If not project-based, filter employees without a project
            $employeesWPosition = $employeesWPosition->whereNull('project_id'); // Ensure the employee does not have a project
        }
        // dd($employeesWPosition->get());
        // Execute the query and get results
        $employeesWPosition = $employeesWPosition->get();



        $payrollRecords = collect();

        foreach ($employeesWPosition as $employee) {
            $newRecord = $request->all();
            $newRecord['EmployeeID'] = $employee->id;
            $newRecord['first_name'] = $employee->first_name;
            $newRecord['middle_name'] = $employee->middle_name ?? Null;
            $newRecord['last_name'] = $employee->last_name;
            $newRecord['position'] = $employee->PositionName;
            $newRecord['monthlySalary'] = $employee->MonthlySalary;
            $newRecord['hourlyRate'] = $employee->HourlyRate;
            $newRecord['SalaryType'] = 'OPEN';
            $newRecord['RegularStatus'] = $employee->employment_type == 'Regular' ? 'YES' : 'NO';
            // Check if the employee has a project_id
            if ($employee->project_id) {
                // Retrieve the project name associated with the employee's project_id
                $project = \App\Models\Project::find($employee->project_id);

                // Store the project name in the newRecord if the project is found
                if ($project) {
                    $newRecord['ProjectName'] = $project->ProjectName; // Assuming 'name' is the field for the project name
                } else {
                    $newRecord['ProjectName'] = 'Main Office'; // Handle case where project is not found
                }
            } else {
                $newRecord['ProjectName'] = 'Main Office'; // Handle case where there is no project assigned
            }


            // Check if payroll frequency is Kinsenas or Weekly
            $weekPeriod = \App\Models\WeekPeriod::where('id', $request->weekPeriodID)->first();
            $newRecord['Period'] = $weekPeriod->StartDate . ' - ' . $weekPeriod->EndDate;

            // dd($newRecord['ProjectName'], $newRecord['Period']);
            if ($weekPeriod) {
                // For Kinsenas (1st Kinsena or 2nd Kinsena)
                if ($weekPeriod->Category == 'Kinsenas') {
                    if (in_array($weekPeriod->Type, ['1st Kinsena', '2nd Kinsena'])) {
                        $startDate = $weekPeriod->StartDate;
                        $endDate = $weekPeriod->EndDate;
                    } else {
                        // Default to the first half of the month if no specific Type is found
                        $startDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 1);
                        $endDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 15);
                    }

                    // Get attendance between startDate and endDate
                    $attendance = \App\Models\Attendance::where('Employee_ID', $employee->id)
                        ->whereBetween('Date', [$startDate, $endDate])
                        ->get();

                } elseif ($weekPeriod->Category == 'Weekly') {
                    // For Weekly (Week 1, Week 2, Week 3, or Week 4)
                    if (in_array($weekPeriod->Type, ['Week 1', 'Week 2', 'Week 3', 'Week 4'])) {
                        $startDate = $weekPeriod->StartDate;
                        $endDate = $weekPeriod->EndDate;
                    } else {
                        // Default to the first week if no specific period is found
                        $startDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 1);
                        $endDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 7);
                    }

                    // Get attendance between startDate and endDate
                    $attendance = \App\Models\Attendance::where('Employee_ID', $employee->id)
                        ->whereBetween('Date', [$startDate, $endDate])
                        ->orderBy('Date', 'ASC')
                        ->get();
                }
            }


            $SSSDeduction = 0;
            $PagIbigDeduction = 0;
            $PhilHealthDeduction = 0;
            $DeductionFee = 0;


            // For Deductions
            $GetDeductions = \App\Models\Deduction::where('PeriodID', $request->weekPeriodID)
                ->where('EmployeeID', $employee->id)
                ->get();
            $Deductions = $GetDeductions;

            if (count($Deductions) > 0) {
                $DeductionFee = $Deductions[0]->Amount;
                $newRecord['DeductionFee'] = $DeductionFee;
                // $TotalEarningPay = $EarningPay;
            }

            // Get the loan for the employee and period
            $loan = \App\Models\Loan::where('EmployeeID', $employee->id)
                ->where('PeriodID', $request->weekPeriodID)
                ->first();
            if ($loan) {
                // Check if payroll is already generated for this period and employee
                $existingPayroll = \App\Models\Payroll::where('weekPeriodID', $request->weekPeriodID)
                    ->exists();
                if ($existingPayroll) {
                    $newDeduction = new \App\Models\Deduction();
                    // Initialize variables for SSS and HDMF loan deductions
                    $SSSDeduction = 0;
                    $HDMFDeduction = 0;
                    // Deduct the number of payments based on LoanType
                    if ($loan->PaymentsRemaining > 0) {
                        switch ($loan->LoanType) {
                            case 'Monthly':
                                // For monthly loans, deduct 1 payment
                                $loan->PaymentsRemaining -= 1;
                                $loan->Balance -= $loan->MonthlyDeduction;
                                break;

                            case 'Kinsenas':
                                // For Kinsenas, deduct after 2 payroll periods in the same month
                                // Check how many payrolls have been generated for the month
                                $payrollCountKinsenas = \App\Models\Payroll::whereMonth('weekPeriodID', Carbon::now()->month)
                                    ->where('LoanType', 'Kinsenas')
                                    ->count();

                                if ($payrollCountKinsenas % 2 == 0) { // Deduct every 2 payrolls
                                    $loan->PaymentsRemaining -= 1;
                                    $loan->Balance -= $loan->KinsenaDeduction;

                                    // Store SSS and HDMF deductions
                                    $newRecord['$SSSDeduction'] = $loan->KinsenaDeduction;  // Assuming SSS loan for Kinsena
                                    $newRecord['$HDMFDeduction'] = $loan->KinsenaDeduction; // Assuming HDMF loan for Kinsena
                                }
                                break;

                            case 'Weekly':
                                // For weekly loans, deduct after 4 payroll periods in the same month
                                // Check how many payrolls have been generated for the month
                                $payrollCountWeekly = \App\Models\Payroll::whereMonth('weekPeriodID', Carbon::now()->month)
                                    ->where('LoanType', 'Weekly')
                                    ->count();

                                if ($payrollCountWeekly % 4 == 0) { // Deduct every 4 payrolls
                                    $loan->PaymentsRemaining -= 1;
                                    $loan->Balance -= $loan->WeeklyDeduction;

                                    // Store SSS and HDMF deductions
                                    $newRecord['$SSSDeduction'] = $loan->WeeklyDeduction;  // Assuming SSS loan for Weekly
                                    $newRecord['$HDMFDeduction'] = $loan->WeeklyDeduction; // Assuming HDMF loan for Weekly
                                }
                                break;
                        }

                        // Ensure the balance doesn't go below zero
                        if ($loan->Balance < 0) {
                            $loan->Balance = 0;
                        }

                        // Save the updated loan record
                        $loan->save();
                    }
                }
            }




            $GetSSS = \App\Models\sss::get();

            $GetPagibig = \App\Models\pagibig::get();

            $GetPhilHealth = \App\Models\philhealth::get();

            // $weekPeriod = \App\Models\WeekPeriod::where('id', $request->weekPeriodID)->first();

            if ($weekPeriod) {
                $deductionFactor = 1; // Initialize deductionFactor

                // Set deductionFactor based on week period category
                if ($weekPeriod->Category == 'Kinsenas') {
                    $deductionFactor = ($weekPeriod->Type == '1st Kinsena' || $weekPeriod->Type == '2nd Kinsena') ? 2 : 1;
                } elseif ($weekPeriod->Category == 'Weekly') {
                    $deductionFactor = 4; // Weekly deductions are typically divided into 4 parts
                }

                // Initialize Deduction to 0 to avoid undefined issues
                $newRecord['DeductionID'] = null;
                $newRecord['Deduction'] = 0;
                $newRecord['DeductionEmployer'] = 0;
                $newRecord['DeductionMonthly'] = 0;
                $newRecord['DeductionTotal'] = 0;

                // Determine the report type and calculate relevant deductions
                switch ($request->ReportType) {
                    case 'SSS Contribution':
                        $newRecord['DeductionID'] = $employee->SSSNumber;
                        foreach ($GetSSS as $sss) {
                            if ($sss->MinSalary <= $employee->MonthlySalary && $sss->MaxSalary >= $employee->MonthlySalary) {
                                $SSSDeduction = $sss->EmployeeShare / $deductionFactor;
                                $SSSDeductionMonthly = $sss->EmployeeShare;

                                $newRecord['Deduction'] = $SSSDeduction;
                                $newRecord['DeductionEmployer'] = $sss->EmployerShare;
                                $newRecord['DeductionMonthly'] = $SSSDeductionMonthly;
                                $newRecord['DeductionTotal'] = $SSSDeduction + $SSSDeductionMonthly;
                                break;
                            }
                        }
                        break;

                    case 'Philhealth Contribution':
                        $newRecord['DeductionID'] = $employee->PhilHealthNumber;
                        foreach ($GetPhilHealth as $philhealth) {
                            if ($philhealth->MinSalary <= $employee->MonthlySalary && $philhealth->MaxSalary >= $employee->MonthlySalary) {
                                if ($philhealth->PremiumRate == '0.00') {
                                    $PhilHealthDeduction = $philhealth->ContributionAmount / $deductionFactor;
                                    $PhilHealthDeductionMonthly = $philhealth->ContributionAmount;
                                } else {
                                    $PhilHealthDeduction = (($philhealth->PremiumRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                                    $PhilHealthDeductionMonthly = (($philhealth->PremiumRate / 100) * $employee->MonthlySalary);
                                }
                                $newRecord['Deduction'] = $PhilHealthDeduction;
                                $newRecord['DeductionEmployer'] = $PhilHealthDeduction;
                                $newRecord['DeductionMonthly'] = $PhilHealthDeductionMonthly;
                                $newRecord['DeductionTotal'] = $PhilHealthDeduction + $PhilHealthDeductionMonthly;
                                break;
                            }
                        }
                        break;

                    case 'Pagibig Contribution':
                        $newRecord['DeductionID'] = $employee->PagibigNumber;
                        foreach ($GetPagibig as $pagibig) {
                            if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
                                $PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                                $PagIbigDeductionEmployer = (($pagibig->EmployeeRate / 50) * $employee->MonthlySalary) / $deductionFactor;
                                $PagIbigDeductionMonthly = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary);

                                $newRecord['Deduction'] = $PagIbigDeduction;
                                $newRecord['DeductionEmployer'] = $PagIbigDeductionEmployer;
                                $newRecord['DeductionMonthly'] = $PagIbigDeductionMonthly;
                                $newRecord['DeductionTotal'] = $PagIbigDeduction + $PagIbigDeductionMonthly;
                                break;
                            }
                        }
                        break;

                    case 'Loan':
                        // Implement loan calculation logic if required
                        $newRecord['Loan'] = 'Loan calculation logic here';
                        break;

                    default:
                        // Handle error for invalid ReportType
                        $newRecord['error'] = 'Invalid ReportType';
                        break;
                }

                // Always store the report type to newRecord for reference
                $newRecord['ReportType'] = $request->ReportType;
            }



            
            $payrollRecords->push($newRecord);
            

        }
        $payslipHtml .= view('report-template', ['payrollData' => $payrollRecords])->render();
        $dompdf->loadHtml($payslipHtml);
        $dompdf->setPaper('Legal', 'portrait');
        $dompdf->render();


        // Output the generated PDF to Browser
        return $dompdf->stream('payslips.pdf', ['Attachment' => false]);
    }
}

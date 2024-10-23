<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Dompdf\Dompdf;
use App\Models\LoanDtl;
use Illuminate\Http\Request;
use Dompdf\Options;

class PayslipController extends Controller
{

    public function generatePayslips(Request $request)
    {
        $employeeId = $request->input('employee_id'); // Get employee_id from request
        $recordData = $request->input('record');

        $dompdf = new Dompdf();
        $payslipHtml = '';

        // Validate the input data


        // Query for employees with their position
        $employeesWPosition = \App\Models\Employee::where('employment_type', $recordData['EmployeeStatus'])
            ->join('positions', 'employees.position_id', '=', 'positions.id')
            ->select('employees.*', 'positions.PositionName', 'positions.MonthlySalary', 'positions.HourlyRate');

        // Check if the assignment is project-based
        if ($recordData['assignment'] === 'Project Based') {
            // If project-based, filter by project
            $employeesWPosition = $employeesWPosition->whereNotNull('project_id') // Ensure the employee has a project
                ->where('project_id', $recordData['ProjectID']); // Filter by specific project
        } else {
            // If not project-based, filter employees without a project
            $employeesWPosition = $employeesWPosition->whereNull('project_id'); // Ensure the employee does not have a project
        }

        // Execute the query and get results
        $employeesWPosition = $employeesWPosition->get();

        $payrollRecords = collect();


        foreach ($employeesWPosition as $employee) {
            if ($employeeId === 'All' || $employeeId == $employee->id) {
                $newRecord = $recordData;
                $newRecord['EmployeeID'] = $employee->id;
                $newRecord['first_name'] = $employee->first_name;
                $newRecord['middle_name'] = $employee->middle_name ?? null;
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
                $weekPeriod = \App\Models\WeekPeriod::where('id', $recordData['weekPeriodID'])->first();
                $newRecord['Period'] = $weekPeriod->StartDate . ' - ' . $weekPeriod->EndDate;
                if ($weekPeriod) {

                    // For Kinsenas (1st Kinsena or 2nd Kinsena)
                    if ($weekPeriod->Category == 'Kinsenas') {
                        if (in_array($weekPeriod->Type, ['1st Kinsena', '2nd Kinsena'])) {
                            $startDate = $weekPeriod->StartDate;
                            $endDate = $weekPeriod->EndDate;
                        } else {
                            // Default to the first half of the month if no specific Type is found
                            $startDate = Carbon::create($recordData['PayrollYear'], Carbon::parse($recordData['PayrollMonth'])->month, 1);
                            $endDate = Carbon::create($recordData['PayrollYear'], Carbon::parse($recordData['PayrollMonth'])->month, 15);
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
                            $startDate = Carbon::create($recordData->PayrollYear, Carbon::parse($recordData['PayrollMonth'])->month, 1);
                            $endDate = Carbon::create($recordData->PayrollYear, Carbon::parse($recordData['PayrollMonth'])->month, 7);
                        }

                        // Get attendance between startDate and endDate
                        $attendance = \App\Models\Attendance::where('Employee_ID', $employee->id)
                            ->whereBetween('Date', [$startDate, $endDate])
                            ->orderBy('Date', 'ASC')
                            ->get();
                    }
                }

                $finalAttendance = $attendance;
                $TotalHours = 0;
                $TotalHoursSunday = 0;
                $TotalHrsSpecialHol = 0;
                $TotalHrsRegularHol = 0;
                $TotalEarningPay = 0;
                $TotalDeductions = 0;
                $TotalGovDeductions = 0;
                $TotalOfficeDeductions = 0;
                $SSSDeduction = 0;
                $WTAXDeduction = 0;
                $PagIbigDeduction = 0;
                $PhilHealthDeduction = 0;
                $EarningPay = 0;
                $RegHolidayWorkedHours = 0; // initialize as zero
                $SpecialHolidayWorkedHours = 0;
                $TotalOvertimeHours = 0;
                $TotalOvertimePay = 0;
                $TotalTardiness = 0;
                $TotalUndertime = 0;
                $DeductionFee = 0;
                $totalWorkedHours = 0;
                $tardinessMorningMinutes = 0;
                $tardinessAfternoonMinutes = 0;
                $underTimeMorningMinutes = 0;
                $underTimeAfternoonMinutes = 0;

                foreach ($finalAttendance as $attendances) {
                    $attendanceDate = Carbon::parse($attendances['Date']);
                    $GetHoliday = \App\Models\Holiday::where('HolidayDate', substr($attendanceDate, 0, 10))->get();
                    $Holiday = $GetHoliday;

                    // Get the work schedule based on the Schedule assigned to the employee
                    $GetWorkSched = \App\Models\WorkSched::where('ScheduleName', $employee['schedule']->ScheduleName)->get();
                    $WorkSched = $GetWorkSched;

                    if (
                        ($WorkSched[0]->monday == $attendanceDate->isMonday() && $attendanceDate->isMonday() == 1)
                        || ($WorkSched[0]->tuesday == $attendanceDate->isTuesday() && $attendanceDate->isTuesday() == 1)
                        || ($WorkSched[0]->wednesday == $attendanceDate->isWednesday() && $attendanceDate->isWednesday() == 1)
                        || ($WorkSched[0]->thursday == $attendanceDate->isThursday() && $attendanceDate->isThursday() == 1)
                        || ($WorkSched[0]->friday == $attendanceDate->isFriday() && $attendanceDate->isFriday() == 1)
                        || ($WorkSched[0]->saturday == $attendanceDate->isSaturday() && $attendanceDate->isSaturday() == 1)
                        || ($WorkSched[0]->sunday == $attendanceDate->isSunday() && $attendanceDate->isSunday() == 1)
                    ) {
                        $In1 = $WorkSched[0]->CheckinOne;
                        $In1Array = explode(':', $In1);

                        $Out1 = $WorkSched[0]->CheckoutOne;
                        $Out1Array = explode(':', $Out1);

                        $In2 = $WorkSched[0]->CheckinTwo;
                        $In2Array = explode(':', $In2);

                        $Out2 = $WorkSched[0]->CheckoutTwo;
                        $Out2Array = explode(':', $Out2);

                        // Check if the attendance date is a Sunday
                        if ($attendanceDate->isSunday()) {
                            $morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]);
                            $morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);
                            $afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]);
                            $afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);

                            // Ensure Checkin_One is valid before creating a Carbon instance
                            if (isset($attendances["Checkin_One"]) && strlen($attendances["Checkin_One"]) >= 5) {
                                $checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
                            } else {
                                $checkinOne = null;
                            }

                            // Ensure Checkout_One is valid before creating a Carbon instance
                            if (isset($attendances["Checkout_One"]) && strlen($attendances["Checkout_One"]) >= 5) {
                                $checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));
                            } else {
                                $checkoutOne = null;
                            }

                            // Ensure Checkin_Two is valid before creating a Carbon instance
                            if (isset($attendances["Checkin_Two"]) && strlen($attendances["Checkin_Two"]) >= 5) {
                                $checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
                            } else {
                                $checkinTwo = null;
                            }

                            // Ensure Checkout_Two is valid before creating a Carbon instance
                            if (isset($attendances["Checkout_Two"]) && strlen($attendances["Checkout_Two"]) >= 5) {
                                $checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));
                            } else {
                                $checkoutTwo = null;
                            }

                            // Calculate morning and afternoon worked hours
                            if ($checkinOne && $checkoutOne) {
                                $effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
                                $effectiveCheckOutOne = $checkoutOne->lessThan($morningEnd) ? $checkoutOne : $morningEnd;
                                $workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($checkoutOne);
                                $underTimeMorningMinutes = $effectiveCheckOutOne->diffInMinutes($morningEnd);
                                $tardinessMorningMinutes = $morningStart->diffInMinutes($checkinOne);
                                $workedMorningHours = $workedMorningMinutes / 60;
                            } else {
                                $workedMorningHours = $underTimeMorningMinutes = $tardinessMorningMinutes = 0;
                            }

                            if ($checkinTwo && $checkoutTwo) {
                                $lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;
                                $effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
                                $effectiveCheckOutTwo = $checkoutTwo->lessThan($afternoonEnd) ? $checkoutTwo : $afternoonEnd;
                                $workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($checkoutTwo);
                                $underTimeAfternoonMinutes = $effectiveCheckOutTwo->diffInMinutes($afternoonEnd);
                                $tardinessAfternoonMinutes = $afternoonStart->diffInMinutes($checkinTwo);
                                $workedAfternoonHours = $workedAfternoonMinutes / 60;
                            } else {
                                $workedAfternoonHours = $underTimeAfternoonMinutes = $tardinessAfternoonMinutes = 0;
                            }

                            $totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
                            $SundayWorkedHours = $totalWorkedHours;

                            $TotalHoursSunday += $SundayWorkedHours; // Add to Sunday worked hours
                            $TotalTardiness += ($tardinessMorningMinutes > 0 ? $tardinessMorningMinutes : 0)
                                + ($tardinessAfternoonMinutes > 0 ? $tardinessAfternoonMinutes : 0);

                            $TotalUndertime += ($underTimeMorningMinutes > 0 ? $underTimeMorningMinutes : 0)
                                + ($underTimeAfternoonMinutes > 0 ? $underTimeAfternoonMinutes : 0);

                            $newRecord['TotalTardiness'] = $TotalTardiness;
                            $newRecord['TotalUndertime'] = $TotalUndertime;
                            $newRecord['TotalTardinessDed'] = $TotalTardiness * $employee->HourlyRate;
                            $newRecord['TotalUndertimeDed'] = $TotalUndertime * $employee->HourlyRate;
                            $newRecord['TotalHoursSunday'] = $TotalHoursSunday;
                        } else {
                            if (count($Holiday) > 0 && $Holiday[0]->ProjectID == $employee->project_id) {
                                // Similar calculation for holidays
                                $morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]);
                                $morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);
                                $afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]);
                                $afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);

                                // Process check-in and check-out times for holidays...
                                // The same logic applies here as in the Sunday calculation
                                // ...

                                // Check type of Holiday
                                if ($Holiday[0]->HolidayType == 'Regular') {
                                    $RegHolidayWorkedHours = $totalWorkedHours;
                                    $TotalHrsRegularHol += $RegHolidayWorkedHours;
                                    $newRecord['TotalHrsRegularHol'] = $TotalHrsRegularHol;
                                } else if ($Holiday[0]->HolidayType == 'Special') {
                                    $SpecialHolidayWorkedHours = $totalWorkedHours;
                                    $TotalHrsSpecialHol += $SpecialHolidayWorkedHours;
                                    $newRecord['TotalHrsSpecialHol'] = $TotalHrsSpecialHol;
                                }

                                $TotalTardiness += ($tardinessMorningMinutes > 0 ? $tardinessMorningMinutes : 0)
                                    + ($tardinessAfternoonMinutes > 0 ? $tardinessAfternoonMinutes : 0);

                                $TotalUndertime += ($underTimeMorningMinutes > 0 ? $underTimeMorningMinutes : 0)
                                    + ($underTimeAfternoonMinutes > 0 ? $underTimeAfternoonMinutes : 0);

                                $newRecord['TotalTardiness'] = $TotalTardiness;
                                $newRecord['TotalUndertime'] = $TotalUndertime;
                                $newRecord['TotalTardinessDed'] = $TotalTardiness * $employee->HourlyRate;
                                $newRecord['TotalUndertimeDed'] = $TotalUndertime * $employee->HourlyRate;
                            }
                        }
                    }
                }

                $OtDate = \App\Models\Overtime::where('EmployeeID', $newRecord['EmployeeID'])
                    ->where('Status', 'approved')
                    ->get();

                if (count($OtDate) > 0) {

                    foreach ($OtDate as $otRecord) {

                        $In1s = $otRecord->Checkin;
                        $InOT = explode(':', $In1s);

                        $Out1s = $otRecord->Checkout;
                        $OutOT = explode(':', $Out1s);


                        $OTStart = Carbon::createFromTime($InOT[0], $InOT[1], $InOT[2]);
                        $OTEnd = Carbon::createFromTime($OutOT[0], $OutOT[1], $OutOT[2]);


                        $workedOTMinutes = $OTStart->diffInMinutes($OTEnd);
                        $workedOTHours = $workedOTMinutes / 60;

                        $TotalOvertimeHours += $workedOTHours;
                    }

                    $newRecord['TotalOvertimeHours'] = $TotalOvertimeHours;
                }


                // For Earnings
                $GetEarnings = \App\Models\Earnings::where('PeriodID', $recordData['weekPeriodID'])
                    ->where('EmployeeID', $employee->id)
                    ->get();
                $Earnings = $GetEarnings;

                if (count($Earnings) > 0) {
                    $EarningPay = $Earnings[0]->Amount;
                    $newRecord['EarningPay'] = $EarningPay;
                }

                $GetDeductions = \App\Models\Deduction::where('PeriodID', $recordData['weekPeriodID'])
                    ->where('EmployeeID', $employee->id)
                    ->get();
                $Deductions = $GetDeductions;

                if (count($Deductions) > 0) {
                    $DeductionFee = $Deductions[0]->Amount;
                    $newRecord['DeductionFee'] = $DeductionFee;
                }


                $loan = \App\Models\Loan::where('EmployeeID', $employee->id)
                    ->where('PeriodID', $recordData['weekPeriodID'])
                    ->first();
                if ($loan) {

                    $existingPayroll = \App\Models\Payroll::where('weekPeriodID', $recordData['weekPeriodID'])
                        ->exists();
                    if ($existingPayroll) {
                        $newDeduction = new \App\Models\Deduction();

                        $SSSDeduction = 0;
                        $HDMFDeduction = 0;

                        if ($loan->PaymentsRemaining > 0) {
                            switch ($loan->LoanType) {
                                case 'Monthly':

                                    $loan->PaymentsRemaining -= 1;
                                    $loan->Balance -= $loan->MonthlyDeduction;
                                    break;

                                case 'Kinsenas':

                                    $payrollCountKinsenas = \App\Models\Payroll::whereMonth('weekPeriodID', Carbon::now()->month)
                                        ->where('LoanType', 'Kinsenas')
                                        ->count();

                                    if ($payrollCountKinsenas % 2 == 0) {
                                        $loan->PaymentsRemaining -= 1;
                                        $loan->Balance -= $loan->KinsenaDeduction;


                                        $newRecord['$SSSDeduction'] = $loan->KinsenaDeduction;  // Assuming SSS loan for Kinsena
                                        $newRecord['$HDMFDeduction'] = $loan->KinsenaDeduction; // Assuming HDMF loan for Kinsena
                                    }
                                    break;

                                case 'Weekly':

                                    $payrollCountWeekly = \App\Models\Payroll::whereMonth('weekPeriodID', Carbon::now()->month)
                                        ->where('LoanType', 'Weekly')
                                        ->count();

                                    if ($payrollCountWeekly % 4 == 0) {
                                        $loan->PaymentsRemaining -= 1;
                                        $loan->Balance -= $loan->WeeklyDeduction;

                                        $newRecord['$SSSDeduction'] = $loan->WeeklyDeduction;  // Assuming SSS loan for Weekly
                                        $newRecord['$HDMFDeduction'] = $loan->WeeklyDeduction; // Assuming HDMF loan for Weekly
                                    }
                                    break;
                            }

                            if ($loan->Balance < 0) {
                                $loan->Balance = 0;
                            }
                            $loan->save();
                        }
                    }
                }
                $loans = \App\Models\Loan::where('EmployeeID', $employee->id)
                    ->whereIn('LoanType', ['SSS Loan', 'Pagibig Loan', 'Salary Loan']) // Filter by loan types
                    ->get();

                $newRecord['SSSLoan'] = 0;
                $newRecord['PagibigLoan'] = 0;
                $newRecord['SalaryLoan'] = 0;

                foreach ($loans as $loan) {

                    $loanDetails = LoanDtl::where('LoanID', $loan->id)
                        ->where('PeriodID', $recordData['weekPeriodID'])
                        ->get();

                    $deductionAmount = 0;

                    foreach ($loanDetails as $detail) {

                        $deductionAmount += $detail->Amount;

                        if (!$detail->IsPaid) {

                            $detail->IsPaid = true;
                            $detail->save();


                            $loan->Balance -= $detail->Amount;
                        }
                    }

                    switch ($loan->LoanType) {
                        case 'SSS Loan':
                            $newRecord['SSSLoan'] += $deductionAmount;
                            break;
                        case 'Pagibig Loan':
                            $newRecord['PagibigLoan'] += $deductionAmount;
                            break;
                        case 'Salary Loan':
                            $newRecord['SalaryLoan'] += $deductionAmount;
                            break;
                    }


                    if ($loan->Balance < 0) {
                        $loan->Balance = 0;
                    }
                    $loan->save();
                }

                $GetSSS = \App\Models\sss::get();

                $GetPagibig = \App\Models\pagibig::get();

                $GetPhilHealth = \App\Models\philhealth::get();

                $GetWTAX = \App\Models\Tax::get();
                // $weekPeriod = \App\Models\WeekPeriod::where('id', $recordData->weekPeriodID)->first();

                if ($weekPeriod) {
                    // For Kinsenas (1st Kinsena or 2nd Kinsena)
                    if ($weekPeriod->Category == 'Kinsenas') {
                        $deductionFactor = ($weekPeriod->Type == '1st Kinsena' || $weekPeriod->Type == '2nd Kinsena') ? 2 : 1;

                        // SSS Deduction for Kinsenas
                        foreach ($GetSSS as $sss) {
                            if ($sss->MinSalary <= $employee->MonthlySalary && $sss->MaxSalary >= $employee->MonthlySalary) {
                                $SSSDeduction = $sss->EmployeeShare / $deductionFactor;
                                $newRecord['SSSDeduction'] = $SSSDeduction;
                                break;
                            }
                        }

                        // PagIbig Deduction for Kinsenas
                        foreach ($GetPagibig as $pagibig) {
                            if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
                                // Set a static amount for employee and employer share
                                $PagIbigDeduction = 200 / $deductionFactor; // Divide by deduction factor for Kinsenas or Weekly
                                $newRecord['PagIbigDeduction'] = $PagIbigDeduction;
                                break;
                            }
                        }

                        // // PagIbig Deduction for Kinsenas
                        // foreach ($GetPagibig as $pagibig) {
                        //     if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
                        //         $PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                        //         $newRecord['PagIbigDeduction'] = $PagIbigDeduction;
                        //         break;
                        //     }
                        // }

                        // PhilHealth Deduction for Kinsenas
                        foreach ($GetPhilHealth as $philhealth) {
                            if ($philhealth->MinSalary <= $employee->MonthlySalary && $philhealth->MaxSalary >= $employee->MonthlySalary) {
                                if ($philhealth->PremiumRate == '0.00') {
                                    $PhilHealthDeduction = $philhealth->ContributionAmount / $deductionFactor;
                                } else {
                                    $PhilHealthDeduction = (($philhealth->PremiumRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                                }
                                $newRecord['PhilHealthDeduction'] = $PhilHealthDeduction;
                                break;
                            }
                        }

                        // WTAX Deduction for Kinsenas
                        foreach ($GetWTAX as $wTax) {
                            if ($wTax->MinSalary <= $employee->MonthlySalary && $wTax->MaxSalary >= $employee->MonthlySalary) {
                                $excess = $employee->MonthlySalary - $wTax->MinSalary;
                                $WTAXAnnual = $wTax->base_rate + ($excess * ($wTax->exceess_percent / 100));
                                $WTAXDeduction = $WTAXAnnual / $deductionFactor; // Dividing by 12 for monthly and deductionFactor for Kinsenas
                                $newRecord['WTAXDeduction'] = $WTAXDeduction;
                                break;
                            }
                        }
                    } elseif ($weekPeriod->Category == 'Weekly') {
                        // For Weekly (Week 1, Week 2, Week 3, or Week 4)
                        $deductionFactor = 4; // Weekly deductions are typically divided into 4 parts

                        // // SSS Deduction for Weekly
                        // foreach ($GetSSS as $sss) {
                        //     if ($sss->MinSalary <= $employee->MonthlySalary && $sss->MaxSalary >= $employee->MonthlySalary) {
                        //         $SSSDeduction = $sss->EmployeeShare / $deductionFactor;
                        //         $newRecord['SSSDeduction'] = $SSSDeduction;
                        //         break;
                        //     }
                        // }

                        // // PagIbig Deduction for Weekly
                        // foreach ($GetPagibig as $pagibig) {
                        //     if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
                        //         $PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                        //         $newRecord['PagIbigDeduction'] = $PagIbigDeduction;
                        //         break;
                        //     }
                        // }

                        // PhilHealth Deduction for Weekly
                        foreach ($GetPhilHealth as $philhealth) {
                            if ($philhealth->MinSalary <= $employee->MonthlySalary && $philhealth->MaxSalary >= $employee->MonthlySalary) {
                                if ($philhealth->PremiumRate == '0.00') {
                                    $PhilHealthDeduction = $philhealth->ContributionAmount / $deductionFactor;
                                } else {
                                    $PhilHealthDeduction = (($philhealth->PremiumRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                                }
                                $newRecord['PhilHealthDeduction'] = $PhilHealthDeduction;
                                break;
                            }
                        }

                        // WTAX Deduction for Weekly
                        foreach ($GetWTAX as $wTax) {
                            if ($wTax->MinSalary <= $employee->MonthlySalary && $wTax->MaxSalary >= $employee->MonthlySalary) {
                                $excess = $employee->MonthlySalary - $wTax->MinSalary;
                                $WTAXAnnual = $wTax->BaseRate + ($excess * ($wTax->ExcessPercent / 100));
                                $WTAXDeduction = $WTAXAnnual / 12 / $deductionFactor; // Dividing by 12 for monthly and deductionFactor for Weekly
                                $newRecord['WTAXDeduction'] = $WTAXDeduction;
                                break;
                            }
                        }
                    }
                }



                $newRecord['WTAXDeduction'] = $newRecord['WTAXDeduction'] ?? 0;

                $BasicPay = $TotalHours * $employee->HourlyRate;
                $newRecord['BasicPay'] = $BasicPay;

                $TotalOvertimePay = $TotalOvertimeHours * $employee->HourlyRate * 1.25;
                $newRecord['TotalOvertimePay'] = $TotalOvertimePay;

                $SundayPay = $TotalHoursSunday * $employee->HourlyRate * 1.30;
                $newRecord['SundayPay'] = $SundayPay;

                $SpecialHolidayPay = $TotalHrsSpecialHol ? $TotalHrsSpecialHol * $employee->HourlyRate * 1.30 : 0;
                $newRecord['SpecialHolidayPay'] = $SpecialHolidayPay;

                $RegularHolidayPay = $TotalHrsRegularHol ? $TotalHrsRegularHol * $employee->HourlyRate : 0;
                $newRecord['RegularHolidayPay'] = $RegularHolidayPay;

                $GrossPay = $EarningPay + $BasicPay + $SundayPay + $SpecialHolidayPay + $RegularHolidayPay + $TotalOvertimePay;
                $newRecord['GrossPay'] = $GrossPay;
                $TotalDeductions = $PagIbigDeduction + $SSSDeduction + $PhilHealthDeduction + $DeductionFee + $newRecord['SSSLoan'] + $newRecord['PagibigLoan'] + $newRecord['SalaryLoan'] + $newRecord['WTAXDeduction'];
                $newRecord['TotalDeductions'] = $TotalDeductions;

                $TotalGovDeductions = $PagIbigDeduction + $SSSDeduction + $PhilHealthDeduction;
                $newRecord['TotalGovDeductions'] = $TotalGovDeductions;

                $TotalOfficeDeductions = $DeductionFee;
                $newRecord['TotalOfficeDeductions'] = $TotalOfficeDeductions;

                $NetPay = $GrossPay - $TotalDeductions;
                $newRecord['NetPay'] = $NetPay;


                $payrollRecords->push($newRecord);
                // dd( $payrollRecords->toArray());
                $payslipHtml .= view('payslip-template', ['payrollRecords' => $payrollRecords->toArray()])->render();
            }
        }
        $dompdf->loadHtml($payslipHtml);
        $dompdf->setPaper('Legal', 'portrait');
        $dompdf->render();


        // Output the generated PDF to Browser
        return $dompdf->stream('payslips.pdf', ['Attachment' => false]);

    }
}

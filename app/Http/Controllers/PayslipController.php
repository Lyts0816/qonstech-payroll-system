<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\{Employee, Attendance, Holiday, WorkSched, Overtime, Earnings, Deduction, Sss, Pagibig, PhilHealth, WeekPeriod, Payslip};

class PayslipController extends Controller
{
    public function show(Request $request, $EmployeeID)
    {
        // Find the specific employee based on EmployeeID in the record


        $selectedEmployee = Employee::with('position')->findOrFail($EmployeeID);

        $records = Payslip::where('EmployeeID', $EmployeeID)->get();

        if ($records->isEmpty()) {
            return redirect()->back()->withErrors(['No payslips found for this employee.']);
        }

        if (!$selectedEmployee) {
            return redirect()->back()->withErrors(['Employee not found']);
        }

        $newRecord = new \stdClass();
        $newRecord->EmployeeID = $selectedEmployee->id;
        $newRecord->first_name = $selectedEmployee->first_name;
        $newRecord->middle_name = $selectedEmployee->middle_name ?? null;
        $newRecord->last_name = $selectedEmployee->last_name;
        $newRecord->position = $selectedEmployee->position->PositionName; // Accessing the position relation
        $newRecord->monthlySalary = $selectedEmployee->position->MonthlySalary;
        $newRecord->hourlyRate = $selectedEmployee->position->HourlyRate;
        $newRecord->SalaryType = 'OPEN';
        $newRecord->RegularStatus = $selectedEmployee->employment_type == 'Regular' ? 'YES' : 'NO';

        // Check payroll frequency
        $weekPeriod = WeekPeriod::find($request->weekPeriodID);
        $attendance = $this->getAttendance($weekPeriod, $selectedEmployee, $request, $records);

        $this->calculateAttendanceDetails($attendance, $newRecord, $selectedEmployee);

        // Get Overtime Records
        $this->calculateOvertime($newRecord);

        // Calculate Earnings and Deductions
        $this->calculateEarningsAndDeductions($newRecord, $request, $selectedEmployee, $records);

        // Return the view with the selected employee's payroll record
        return view('payslip.records', [
            'payrollRecords' => collect([$newRecord]), // Return a collection with the single employee record
            'earnings' => $newRecord->EarningPay,
            'sss' => $newRecord->SSSDeduction,
            'philHealth' => $newRecord->PhilHealthDeduction,
            'pagIbig' => $newRecord->PagIbigDeduction,
            'deductions' => $newRecord->DeductionFee,
            'totalDeductions' => $newRecord->SSSDeduction + $newRecord->PagIbigDeduction + $newRecord->PhilHealthDeduction + $newRecord->DeductionFee,
            'totalGrossPay' => $newRecord->EarningPay,
            'BasicPay' => $newRecord->TotalHours * $newRecord->hourlyRate,
            'netPay' => $newRecord->EarningPay - ($newRecord->SSSDeduction + $newRecord->PagIbigDeduction + $newRecord->PhilHealthDeduction + $newRecord->DeductionFee),
        ]);
    }

    // Process and calculate attendance hours
    protected function calculateAttendanceDetails($attendance, &$newRecord, $selectedEmployee)
    {
        $TotalHours = 0;
        $TotalHoursSunday = 0;
        $TotalHrsRegularHol = 0;
        $TotalHrsSpecialHol = 0;

        foreach ($attendance as $att) {
            $this->processAttendance($att, $selectedEmployee, $newRecord, $TotalHoursSunday, $TotalHrsRegularHol, $TotalHrsSpecialHol, $TotalHours);
        }
        $newRecord->TotalHours = $TotalHours;
        $newRecord->TotalHoursSunday = $TotalHoursSunday;
    }

    // Calculate overtime hours
    private function calculateOvertime($newRecord)
    {
        $overtimeRecords = Overtime::where('EmployeeID', $newRecord->EmployeeID)
            ->where('Status', 'approved')
            ->get();

        $TotalOvertimeHours = 0;

        foreach ($overtimeRecords as $otRecord) {
            $workedOTHours = Carbon::parse($otRecord->Checkin)->diffInHours(Carbon::parse($otRecord->Checkout));
            $TotalOvertimeHours += $workedOTHours;
        }

        $newRecord->TotalOvertimeHours = $TotalOvertimeHours;
    }


    private function getEmployeeById($EmployeeID)
    {
        return \App\Models\Employee::find($EmployeeID);
    }

    private function getAttendance($weekPeriod, $employee, $request, $records)
    {
        $startDate = null;
        $endDate = null;

        // Get the first record from the $records collection
        $firstRecord = $records->first();

        // Check if the first record exists
        if (!$firstRecord) {
            // Handle case where there are no records
            return null; // or return an empty collection if that's the desired behavior
        }

        // Retrieve the weekPeriod based on the weekPeriodID from the first record
        $weekPeriod = WeekPeriod::where('id', $firstRecord->weekPeriodID)->first();

        if ($weekPeriod) {
            if ($weekPeriod->Category == 'Kinsenas') {
                $startDate = $weekPeriod->StartDate ?? Carbon::create($firstRecord->PayrollYear, Carbon::parse($firstRecord->PayrollMonth)->month, 1);
                $endDate = $weekPeriod->EndDate ?? Carbon::create($firstRecord->PayrollYear, Carbon::parse($firstRecord->PayrollMonth)->month, 15);
            } elseif ($weekPeriod->Category == 'Weekly') {
                $startDate = $weekPeriod->StartDate ?? Carbon::create($firstRecord->PayrollYear, Carbon::parse($firstRecord->PayrollMonth)->month, 1);
                $endDate = $weekPeriod->EndDate ?? Carbon::create($firstRecord->PayrollYear, Carbon::parse($firstRecord->PayrollMonth)->month, 7);
            }
        }

        return Attendance::where('Employee_ID', $employee->id)
            ->whereBetween('Date', [$startDate, $endDate])
            ->get();
    }

    protected function processAttendance($attendances, $employee, &$newRecord, &$TotalHoursSunday, &$TotalHrsRegularHol, &$TotalHrsSpecialHol, &$TotalHours)
    {
        $attendanceDate = Carbon::parse($attendances['Date']);

        // Get holidays for the given date
        $GetHoliday = Holiday::where('HolidayDate', substr($attendanceDate, 0, 10))->get();
        $Holiday = $GetHoliday;

        // Get the work schedule assigned to the employee
        $GetWorkSched = WorkSched::where('ScheduleName', $employee['schedule']->ScheduleName)->get();
        $WorkSched = $GetWorkSched;

        // Process based on the employee's work schedule and attendance date
        if (
            ($WorkSched[0]->monday == $attendanceDate->isMonday() && $attendanceDate->isMonday() == 1)
            || ($WorkSched[0]->tuesday == $attendanceDate->isTuesday() && $attendanceDate->isTuesday() == 1)
            || ($WorkSched[0]->wednesday == $attendanceDate->isWednesday() && $attendanceDate->isWednesday() == 1)
            || ($WorkSched[0]->thursday == $attendanceDate->isThursday() && $attendanceDate->isThursday() == 1)
            || ($WorkSched[0]->friday == $attendanceDate->isFriday() && $attendanceDate->isFriday() == 1)
            || ($WorkSched[0]->saturday == $attendanceDate->isSaturday() && $attendanceDate->isSaturday() == 1)
            || ($WorkSched[0]->sunday == $attendanceDate->isSunday() && $attendanceDate->isSunday() == 1)
        ) {
            // Split the schedule for morning and afternoon shifts
            $In1Array = explode(':', $WorkSched[0]->CheckinOne);
            $Out1Array = explode(':', $WorkSched[0]->CheckoutOne);
            $In2Array = explode(':', $WorkSched[0]->CheckinTwo);
            $Out2Array = explode(':', $WorkSched[0]->CheckoutTwo);

            // If Sunday, calculate Sunday hours
            if ($attendanceDate->isSunday()) {
                $this->calculateSundayHours($attendances, $In1Array, $Out1Array, $In2Array, $Out2Array, $TotalHoursSunday, $newRecord);
            } else {
                // If it's a regular day or a holiday, process accordingly
                if (count($Holiday) > 0) {
                    $this->calculateHolidayHours($attendances, $In1Array, $Out1Array, $In2Array, $Out2Array, $Holiday, $TotalHrsRegularHol, $TotalHrsSpecialHol, $newRecord);
                } else {
                    $this->calculateRegularHours($attendances, $In1Array, $Out1Array, $In2Array, $Out2Array, $TotalHours, $newRecord);
                }
            }
        }
    }
    protected function calculateHolidayHours($attendances, $In1Array, $Out1Array, $In2Array, $Out2Array, $Holiday, &$TotalHrsRegularHol, &$TotalHrsSpecialHol, &$newRecord)
    {
        // Initialize regular and special holiday hours
        $regularHolidayHours = 0;
        $specialHolidayHours = 0;

        // Extract check-in and check-out times from the attendance data
        $checkInTime = Carbon::parse($attendances['CheckIn']);
        $checkOutTime = Carbon::parse($attendances['CheckOut']);

        // Determine if the holiday is a regular or special holiday
        $isRegularHoliday = false;
        $isSpecialHoliday = false;

        foreach ($Holiday as $holiday) {
            if ($holiday->HolidayType == 'Regular') {
                $isRegularHoliday = true;
            } elseif ($holiday->HolidayType == 'Special') {
                $isSpecialHoliday = true;
            }
        }

        // Calculate the scheduled morning shift hours
        $scheduledIn1 = Carbon::createFromTime($In1Array[0], $In1Array[1]);
        $scheduledOut1 = Carbon::createFromTime($Out1Array[0], $Out1Array[1]);

        // Calculate the scheduled afternoon shift hours (if applicable)
        $scheduledIn2 = !empty($In2Array) ? Carbon::createFromTime($In2Array[0], $In2Array[1]) : null;
        $scheduledOut2 = !empty($Out2Array) ? Carbon::createFromTime($Out2Array[0], $Out2Array[1]) : null;

        // Check if the check-in time is within the scheduled morning shift
        if ($checkInTime->between($scheduledIn1, $scheduledOut1)) {
            // Check-out time should not exceed the scheduled check-out
            if ($checkOutTime->lt($scheduledOut1)) {
                $regularHolidayHours += $checkOutTime->diffInHours($checkInTime);
            } else {
                $regularHolidayHours += $scheduledOut1->diffInHours($checkInTime);
            }
        }

        // Check if there's an afternoon shift and the employee checked in for that shift
        if ($scheduledIn2 && $checkInTime->greaterThan($scheduledOut1)) {
            if ($checkInTime->between($scheduledIn2, $scheduledOut2)) {
                // Check-out time should not exceed the scheduled check-out for the afternoon shift
                if ($checkOutTime->lt($scheduledOut2)) {
                    $regularHolidayHours += $checkOutTime->diffInHours($checkInTime);
                } else {
                    $regularHolidayHours += $scheduledOut2->diffInHours($checkInTime);
                }
            }
        }

        // If it's a regular holiday, calculate additional pay
        if ($isRegularHoliday) {
            $newRecord->RegularHolidayHours = $regularHolidayHours * 2; // Typically, regular holidays pay double
            $TotalHrsRegularHol += $newRecord->RegularHolidayHours; // Update total regular holiday hours
        }

        // If it's a special holiday, calculate additional pay
        if ($isSpecialHoliday) {
            $newRecord->SpecialHolidayHours = $specialHolidayHours * 1.5; // Typically, special holidays pay time and a half
            $TotalHrsSpecialHol += $newRecord->SpecialHolidayHours; // Update total special holiday hours
        }

        // Update the newRecord with total holiday hours worked
        $newRecord->HolidayHours = $regularHolidayHours + $specialHolidayHours;
    }

    protected function calculateSundayHours($attendances, $In1Array, $Out1Array, $In2Array, $Out2Array, &$TotalHoursSunday, &$newRecord)
    {
        $morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]);
        $morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);
        $afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]);
        $afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);

        $checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
        $effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
        $workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($morningEnd);
        $workedMorningHours = $workedMorningMinutes / 60;

        $checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
        $effectiveCheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
        $workedAfternoonMinutes = $effectiveCheckinTwo->diffInMinutes($afternoonEnd);
        $workedAfternoonHours = $workedAfternoonMinutes / 60;

        $totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
        $TotalHoursSunday += $totalWorkedHours;
        $newRecord->TotalHoursSunday = $TotalHoursSunday;
    }
    protected function calculateRegularHours($attendances, $In1Array, $Out1Array, $In2Array, $Out2Array, &$TotalHours, &$newRecord)
    {
        // Initialize hours worked
        $hoursWorked = 0;

        // Extract check-in and check-out times from the attendance data
        $checkInTime = Carbon::parse($attendances['CheckIn']);
        $checkOutTime = Carbon::parse($attendances['CheckOut']);

        // Calculate the scheduled morning shift hours
        $scheduledIn1 = Carbon::createFromTime($In1Array[0], $In1Array[1]);
        $scheduledOut1 = Carbon::createFromTime($Out1Array[0], $Out1Array[1]);

        // Calculate the scheduled afternoon shift hours (if applicable)
        $scheduledIn2 = !empty($In2Array) ? Carbon::createFromTime($In2Array[0], $In2Array[1]) : null;
        $scheduledOut2 = !empty($Out2Array) ? Carbon::createFromTime($Out2Array[0], $Out2Array[1]) : null;

        // Check if the check-in time is within the scheduled morning shift
        if ($checkInTime->between($scheduledIn1, $scheduledOut1)) {
            // Check-out time should not exceed the scheduled check-out
            if ($checkOutTime->lt($scheduledOut1)) {
                $hoursWorked += $checkOutTime->diffInHours($checkInTime);
            } else {
                $hoursWorked += $scheduledOut1->diffInHours($checkInTime);
            }
        }

        // Check if there's an afternoon shift and the employee checked in for that shift
        if ($scheduledIn2 && $checkInTime->greaterThan($scheduledOut1)) {
            if ($checkInTime->between($scheduledIn2, $scheduledOut2)) {
                // Check-out time should not exceed the scheduled check-out for the afternoon shift
                if ($checkOutTime->lt($scheduledOut2)) {
                    $hoursWorked += $checkOutTime->diffInHours($checkInTime);
                } else {
                    $hoursWorked += $scheduledOut2->diffInHours($checkInTime);
                }
            }
        }

        // Update the total hours in the newRecord
        $newRecord->RegularHours = $hoursWorked; // Store calculated hours in newRecord
        $TotalHours += $hoursWorked; // Add to the total hours
    }





    private function calculateEarningsAndDeductions($newRecord, $record, $employee, $records)
    {
        // Initialize values
        // $newRecord->EarningPay = 0;
        $firstRecord = $records->first();
        if (!$firstRecord) {
            // Handle case where there are no records
            return;
        }


        // Calculate Earnings
        $earnings = Earnings::where('PeriodID', $firstRecord->weekPeriodID)
            ->where('EmployeeID', $employee->id)
            ->first();

        // Set EarningPay if earnings found
        $newRecord->EarningPay = $earnings ? $earnings->Amount : 0;

        // Calculate Deductions
        $deductions = Deduction::where('PeriodID', $firstRecord->weekPeriodID)
            ->where('EmployeeID', $employee->id)
            ->first();

        // Set DeductionFee if deductions found
        $newRecord->DeductionFee = $deductions ? $deductions->Amount : 0;

        // Fetch SSS, PagIbig, PhilHealth settings
        $GetSSS = \App\Models\SSS::all();
        $GetPagibig = \App\Models\PagIbig::all();
        $GetPhilHealth = \App\Models\PhilHealth::all();
        $weekPeriod = \App\Models\WeekPeriod::where('id', $firstRecord->weekPeriodID)->first(); // Adjusted to get specific weekPeriod

        // Calculate deductions based on week period category
        if ($weekPeriod) {
            // Determine the deduction factor based on the week period type
            $deductionFactor = ($weekPeriod->Category == 'Kinsenas') ?
                (($weekPeriod->Type == '1st Kinsena' || $weekPeriod->Type == '2nd Kinsena') ? 2 : 1) :
                4; // Default for Weekly

            // Function to calculate SSS, PagIbig, and PhilHealth deductions
            $this->calculateDeductions($newRecord, $employee, $deductionFactor, $GetSSS, $GetPagibig, $GetPhilHealth);
        }
    }

    // New private function to handle deduction calculations
    private function calculateDeductions($newRecord, $employee, $deductionFactor, $GetSSS, $GetPagibig, $GetPhilHealth)
    {
        $newRecord->SSSDeduction = 0;
        $newRecord->PhilHealthDeduction = 0;
        $newRecord->PagIbigDeduction = 0;
        $newRecord->DeductionFee = 0;
        // SSS Deduction calculation
        foreach ($GetSSS as $sss) {
            if ($sss->MinSalary <= $employee->MonthlySalary && $sss->MaxSalary >= $employee->MonthlySalary) {
                $newRecord->SSSDeduction = $sss->EmployeeShare / $deductionFactor;
                break; // Exit loop after finding the correct SSS
            }
        }

        // PagIbig Deduction calculation
        foreach ($GetPagibig as $pagibig) {
            if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
                $newRecord->PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                break; // Exit loop after finding the correct PagIbig
            }
        }

        // PhilHealth Deduction calculation
        foreach ($GetPhilHealth as $philhealth) {
            if ($philhealth->MinSalary <= $employee->MonthlySalary && $philhealth->MaxSalary >= $employee->MonthlySalary) {
                if ($philhealth->PremiumRate == '0.00') {
                    $newRecord->PhilHealthDeduction = $philhealth->ContributionAmount / $deductionFactor;
                } else {
                    $newRecord->PhilHealthDeduction = (($philhealth->PremiumRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                }
                break; // Exit loop after finding the correct PhilHealth
            }
        }
    }

}
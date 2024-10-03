<?php

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

$Employee_ID = $_POST['Employee_ID'] ?? null;
$Checkin_One = $_POST['Checkin_One'] ?? null;

try {
    if ($Employee_ID !== null && $Checkin_One !== null) {
        $checkinTime = new DateTime($Checkin_One);
        $isMorning = $checkinTime->format('H') < 12;
        $isAfternoon = !$isMorning;

        $employee = Employee::find($Employee_ID);

        if (!$employee) {
            throw new Exception("Employee not found.");
        }
        $isOvertime = $employee->overtime_id !== null;

        $attendance = Attendance::where('Employee_ID', $Employee_ID)
                                ->whereDate('Date', DB::raw('CURDATE()'))
                                ->first();

       
        $totalMinutes = 0;

     
        $currentTime = date('H:i:s');

        if ($attendance) {
          
            if ($isMorning) {
                if (!$attendance->Checkin_One) {
                  
                    $attendance->Checkin_One = $currentTime;
                } elseif (!$attendance->Checkout_One) {
                   
                    $attendance->Checkout_One = $currentTime;
                }
            } else {
                if (!$attendance->Checkin_Two) {
                  
                    $attendance->Checkin_Two = $currentTime;
                } elseif (!$attendance->Checkout_Two) {
                  
                    $attendance->Checkout_Two = $currentTime;
                }
            }


            if ($attendance->Checkin_One && $attendance->Checkout_One) {
                $checkinOne = new DateTime($attendance->Checkin_One);
                $checkoutOne = new DateTime($attendance->Checkout_One);

          
                if ($checkoutOne > $checkinOne) {
                    $minutesOne = $checkoutOne->diff($checkinOne)->i; 
                    $totalMinutes += $minutesOne;
                } else {
                    error_log('Checkout time is earlier than checkin time for morning shift.');
                }
            }

            if ($attendance->Checkin_Two && $attendance->Checkout_Two) {
                $checkinTwo = new DateTime($attendance->Checkin_Two);
                $checkoutTwo = new DateTime($attendance->Checkout_Two);

                
                if ($checkoutTwo > $checkinTwo) {
                    $minutesTwo = $checkoutTwo->diff($checkinTwo)->i; 
                    $totalMinutes += $minutesTwo;
                } else {
                    error_log('Checkout time is earlier than checkin time for afternoon shift.');
                }
            }

            $attendance->Total_Hours = $totalMinutes / 60;

            // Handle overtime
            if ($isOvertime) {
                if (!$attendance->overtime_in) {
                    $attendance->overtime_in = $currentTime;
                } elseif (!$attendance->overtime_out) {
                    $attendance->overtime_out = $currentTime;
                }
            }

     
            $attendance->save();
        } else {
     
            $attendance = new Attendance([
                'Employee_ID' => $Employee_ID,
                'Date' => date('Y-m-d'),
                'Checkin_One' => $isMorning ? $currentTime : null,
                'Checkin_Two' => $isAfternoon ? $currentTime : null,
                'Total_Hours' => 0,
                'overtime_in' => $isOvertime ? $currentTime : null,
                'overtime_out' => null
            ]);
            $attendance->save();
        }

        echo "Data transferred successfully.";
    } else {
        echo "Invalid data.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Dompdf\Dompdf;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
	public function showDtr(Request $request)
	{
		// Get the employee ID from the query parameters

		$employeeId = $request->query('employee_id');
		$projectId = $request->query('project_id');

		$dompdf = new Dompdf();
        // Render each employee's payslip
        $payslipHtml = '';


		// Find the employee by ID
		$employee = Employee::findOrFail($employeeId);
		$project = Project::findOrFail($projectId);

		// Retrieve DTR data for the employee
		// Example: replace with your actual DTR fetching logic
		$data = Attendance::where('employee_id', $employeeId)
			->where('ProjectID', $projectId)
			->orderBy('Date', 'asc')
			->get();

		if (count($data) > 0) {
			$TotalHours = 0;
			foreach ($data as $attendances) {
				$attendanceDate = Carbon::parse($attendances['Date']);

				//Get the workschedule based on Schedule assign to employee
				$GetWorkSched = \App\Models\WorkSched::where('id', $employee->schedule_id)->get();
				$WorkSched = $GetWorkSched;

				$In1 = $WorkSched[0]->CheckinOne;
				$In1Array = explode(':', $In1);

				$Out1 = $WorkSched[0]->CheckoutOne;
				$Out1Array = explode(':', $Out1);

				$In2 = $WorkSched[0]->CheckinTwo;
				$In2Array = explode(':', $In2);

				$Out2 = $WorkSched[0]->CheckoutTwo;
				$Out2Array = explode(':', $Out2);

				$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
				$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
				$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
				$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM

				$checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
				$checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));

				// $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningStart) / 60 : 0;

				$effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
				$effectiveCheckOutOne = $checkoutOne->lessThan($morningEnd) ? $checkoutOne : $morningEnd;
				$workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($checkoutOne);
				$underTimeMorningMinutes = $effectiveCheckOutOne->diffInMinutes($morningEnd);
				$tardinessMorningMinutes = $morningStart->diffInMinutes($checkinOne);
				$workedMorningHours = $workedMorningMinutes / 60;
				// $workedMorningHours = $checkinOne->diffInMinutes($morningEnd) / 60;

				$checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
				$checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

				// $lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;

				$effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
				$effectiveCheckOutTwo = $checkoutTwo->lessThan($afternoonEnd) ? $checkoutTwo : $afternoonEnd;
				$workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($checkoutTwo);
				$underTimeAfternoonMinutes = $effectiveCheckOutTwo->diffInMinutes($afternoonEnd);
				$tardinessAfternoonMinutes = $afternoonStart->diffInMinutes($checkinTwo);
				$workedAfternoonHours = $workedAfternoonMinutes / 60;

				$totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
				// $totalLateHours = $lateMorningHours + $lateAfternoonHours;
				$netWorkedHours = $totalWorkedHours
				;
				// $netWorkedHours = $totalWorkedHours - $totalLateHours;
				// $SundayWorkedHours = $totalSundayWorkedHours - $totalSundayLateHours;

				$TotalHours += $netWorkedHours;
				$attendances['TotalHours'] = $TotalHours;
				$attendances['MorningTardy'] = $tardinessMorningMinutes > 0 ? $tardinessMorningMinutes : 0;
				$attendances['MorningUndertime'] = $underTimeMorningMinutes > 0 ? $underTimeMorningMinutes : 0;
				$attendances['AfternoonTardy'] = $tardinessAfternoonMinutes > 0 ? $tardinessAfternoonMinutes : 0;
				$attendances['AfternoonUndertime'] = $underTimeAfternoonMinutes > 0 ? $underTimeAfternoonMinutes : 0;
				// $payslipHtml .= view('dtr.show', ['employee' => $employee, 'data' => $data->toArray()])->render();
				
			}
			$payslipHtml .= view('dtr.show', ['employee' => $employee, 'data' => $data->toArray()])->render();
		}
		$dompdf->loadHtml($payslipHtml);
        $dompdf->setPaper('Legal', 'portrait');
        $dompdf->render();

		return $dompdf->stream('Dtr.pdf', ['Attachment' => false]);
		// dd($data);
		// return view('dtr.show', compact('employee', 'data'));
	}

	private function getDTRData($employeeId)
	{
		return []; 
	}

}
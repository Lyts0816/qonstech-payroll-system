<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages;
use App\Filament\Resources\PayrollResource\RelationManagers;
use App\Models\Payroll;
use App\Models\WeekPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Project;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollExport;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollResource extends Resource
{
	protected static ?string $model = Payroll::class;

	protected static ?string $navigationIcon = 'heroicon-c-bars-3-center-left';

	protected static ?string $title = 'PHILHEALTH';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				Grid::make(2) // Create a two-column grid layout for the first two fields
					->schema([
						// EmployeeStatus Select Field
						// EmployeeStatus Select Field
						Select::make('EmployeeStatus')
							->label('Employee Status')
							->required(fn(string $context) => $context === 'create' || $context === 'edit')
							->options([
								'Regular' => 'Regular',
								'Non-Regular' => 'Non-Regular',
								'Project Based' => 'Project Based',
							])
							->default(request()->query('employee'))
							->reactive()
							->afterStateUpdated(function (callable $set, $state) {
								// Automatically set PayrollFrequency based on EmployeeStatus
								if ($state === 'Regular') {
									$set('PayrollFrequency', 'Kinsenas');
								} elseif ($state === 'Non-Regular' || $state === 'Project Based') {
									$set('PayrollFrequency', 'Weekly');
								}

								// If EmployeeStatus is 'Regular' or 'Non-Regular', set ProjectID to null
								if ($state === 'Regular' || $state === 'Non-Regular') {
									$set('ProjectID', null);
								}
							}),
						Select::make('ProjectID')
							->label('Project')
							->required(fn(callable $get) => $get('EmployeeStatus') === 'Project Based')
							->options(function (callable $get) {
								return $get('EmployeeStatus') === 'Project Based'
									? Project::query()->pluck('ProjectName', 'id')->toArray()
									: [];
							})
							->disabled(fn(callable $get) => $get('EmployeeStatus') !== 'Project Based')
							->default(null) // Reset default if not project based
							->nullable(),
					]),

				// PayrollFrequency Select Field
				Select::make('PayrollFrequency')
					->label('Payroll Frequency')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options([
						'Kinsenas' => 'Kinsenas (Bi-monthly)',
						'Weekly' => 'Weekly',
					])
					->native(false)
					->disabled()
					->reactive(),

				// PayrollDate Select Field
				Select::make('PayrollDate2')
					->label('Payroll Date')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options(function (callable $get) {
						$frequency = $get('PayrollFrequency');

						if ($frequency == 'Kinsenas') {
							return [
								'1st Kinsena' => '1st-15th',
								'2nd Kinsena' => '16th-End of the Month',
							];
						} elseif ($frequency == 'Weekly') {
							return [
								'Week 1' => 'Week 1',
								'Week 2' => 'Week 2',
								'Week 3' => 'Week 3',
								'Week 4' => 'Week 4',
							];
						}

						return [];
					})
					->reactive(),

				// PayrollMonth Select Field
				Select::make('PayrollMonth')
					->label('Payroll Month')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options([
						'January' => 'January',
						'February' => 'February',
						'March' => 'March',
						'April' => 'April',
						'May' => 'May',
						'June' => 'June',
						'July' => 'July',
						'August' => 'August',
						'September' => 'September',
						'October' => 'October',
						'November' => 'November',
						'December' => 'December',
					])
					->native(false)
					->reactive(),

				// PayrollYear Select Field
				Select::make('PayrollYear')
					->label('Payroll Year')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options(function () {
						$currentYear = date('Y');
						$years = [];
						for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
							$years[$i] = $i;
						}
						return $years;
					})
					->native(false)
					->reactive(),

				// weekPeriodID Select Field
				Select::make('weekPeriodID')
					->label('Period')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options(function (callable $get) {
						// Fetch selected values from other fields
						$month = $get('PayrollMonth');
						$frequency = $get('PayrollFrequency');
						$payrollDate = $get('PayrollDate2');
						$year = $get('PayrollYear');

						// Ensure that all necessary fields are filled before proceeding
						if ($month && $frequency && $payrollDate && $year) {
							try {
								// Convert month name to month number (e.g., 'January' to '01')
								$monthId = Carbon::createFromFormat('F', $month)->format('m');

								// Fetch WeekPeriod entries based on the selected criteria
								return WeekPeriod::where('Month', $monthId)
									->where('Category', $frequency)
									->where('Type', $payrollDate)
									->where('Year', $year)
									->get()
									->mapWithKeys(function ($period) {
									return [
										$period->id => $period->StartDate . ' - ' . $period->EndDate,
									];
								});
							} catch (\Exception $e) {
								// In case there is an issue with parsing the date or any other issue
								return [];
							}
						}

						// If any of the fields are not set, return an empty array
						return [];
					})
					->reactive() // Make this field reactive to other fields
					->placeholder('Select the payroll period'),

			]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('EmployeeStatus')
					->label('Employee Type')
					->searchable()
					->sortable(),

				Tables\Columns\TextColumn::make('PayrollMonth')
					->Label('Payroll Month'),

				Tables\Columns\TextColumn::make('PayrollYear')
					->Label('Payroll Year'),

				Tables\Columns\TextColumn::make('PayrollFrequency')
					->Label('Payroll Frequency'),

				Tables\Columns\TextColumn::make('PayrollDate2')
					->label('Payroll Dates')
					->searchable()
					->sortable(),

				Tables\Columns\TextColumn::make('project.ProjectName')
					->label('Project Name')
					->searchable()
					->sortable(),
			])
			->filters([
				SelectFilter::make('project_id')
					->label('Select Project')
					->options(Project::all()->pluck('ProjectName', 'id'))
					->query(function (Builder $query, array $data) {
						if (empty($data['value'])) {
							return $query;
						}
						return $query->whereHas('employee.project', function (Builder $query) use ($data) {
							$query->where('id', $data['value']);
						});
					}),
			], layout: FiltersLayout::AboveContent)
			->actions([
				Tables\Actions\EditAction::make(),
				// Tables\Actions\ViewAction::make(),
				Tables\Actions\Action::make('calculatePayroll')
					->label('Calculate & Export Payroll')
					->icon('heroicon-o-calculator')
					->color('success')
					->requiresConfirmation()
					->action(function ($record) {
						// Retrieve employees with their positions based on the employment type from the record
						// $employeesWPosition = \App\Models\Employee::where('employment_type', $record->EmployeeStatus)
						// 	->join('positions', 'employees.position_id', '=', 'positions.id')
						// 	->select('employees.id as EmployeeID', 'employees.first_name', 'employees.middle_name', 'employees.last_name', 'positions.PositionName', 'positions.MonthlySalary', 'positions.HourlyRate')
						// 	->get();
						$employeesWPosition = \App\Models\Employee::where('employment_type', $record->EmployeeStatus)
							->join('positions', 'employees.position_id', '=', 'positions.id')
							->select('employees.*', 'positions.PositionName', 'positions.MonthlySalary', 'positions.HourlyRate') // Only select needed fields
							->get();
						// if (!$record->ProjectID) {
						// $employeesWPosition = \App\Models\Employee::where('employment_type', $record->EmployeeStatus)
						// 	->join('positions', 'employees.position_id', '=', 'positions.id') // Assuming 'positions.id' is the correct field for joining
						// 	//->with(['schedule']) // Eager load 'attendance' and 'schedule'
						// 	->get();
			
						// $payrollRecords = collect();
						$payrollRecords = collect();
						foreach ($employeesWPosition as $employee) {
							$newRecord = $record->replicate();
							// dd($employee->first_name, $employee->middle_name, $employee->last_name);
							$newRecord->EmployeeID = $employee->id;
							$newRecord->first_name = $employee->first_name;
							$newRecord->middle_name = $employee->middle_name ?? Null;
							$newRecord->last_name = $employee->last_name;
							$newRecord->position = $employee->PositionName;
							$newRecord->monthlySalary = $employee->MonthlySalary;
							$newRecord->hourlyRate = $employee->HourlyRate;
							$newRecord->SalaryType = 'OPEN';
							$newRecord->RegularStatus = $employee->employment_type == 'Regular' ? 'YES' : 'NO';

							// Check if payroll frequency is Kinsenas or Weekly
							$weekPeriod = \App\Models\WeekPeriod::where('id', $record->weekPeriodID)->first();

							if ($weekPeriod) {
								// For Kinsenas (1st Kinsena or 2nd Kinsena)
								if ($weekPeriod->Category == 'Kinsenas') {
									if (in_array($weekPeriod->Type, ['1st Kinsena', '2nd Kinsena'])) {
										$startDate = $weekPeriod->StartDate;
										$endDate = $weekPeriod->EndDate;
									} else {
										// Default to the first half of the month if no specific Type is found
										$startDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month, 1);
										$endDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month, 15);
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
										$startDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month, 1);
										$endDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month, 7);
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
							$PagIbigDeduction = 0;
							$PhilHealthDeduction = 0;
							$EarningPay = 0;
							$RegHolidayWorkedHours = 0; // initialize as zero
							$SpecialHolidayWorkedHours = 0;
							$TotalOvertimeHours = 0;
							$TotalOvertimePay = 0;
							$DeductionFee = 0;
							foreach ($finalAttendance as $attendances) {
								// dd($attendances);
								$attendanceDate = Carbon::parse($attendances['Date']);
								$GetHoliday = \App\Models\Holiday::where('HolidayDate', substr($attendanceDate, 0, 10))->get();
								$Holiday = $GetHoliday;

								//Get the workschedule based on Schedule assign to employee
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
										// Set official work start and end times
										$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
										$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
										$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
										$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM
			
										// Calculate morning shift times (ignoring seconds)
										$checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
										$checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));

										// Calculate late time for the morning (in hours)
										// $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningEnd) / 60 : 0;
			
										// Calculate worked hours for morning shift (in hours)
										$effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
										$workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($morningEnd);
										$workedMorningHours = $workedMorningMinutes / 60;
										// $workedMorningHours = $checkinOne->diffInMinutes($checkoutOne) / 60;
			
										// Calculate afternoon shift times (ignoring seconds)
										$checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
										$checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

										// Calculate late time for the afternoon (in hours)
										$lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;

										// Calculate worked hours for afternoon shift (in hours)
										$effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
										$workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($afternoonEnd);
										$workedAfternoonHours = $workedAfternoonMinutes / 60;
										// $workedAfternoonHours = $checkinTwo->diffInMinutes($checkoutTwo) / 60;
			
										// Total worked hours minus late hours
										$totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
										// $totalLateHours = $lateMorningHours + $lateAfternoonHours;
										$SundayWorkedHours = $totalWorkedHours;
										// $SundayWorkedHours = $totalWorkedHours - $totalLateHours;
										// $SundayWorkedHours = $totalSundayWorkedHours - $totalSundayLateHours;
			
										// $TotalHours += $netWorkedHours;
										$TotalHoursSunday += $SundayWorkedHours; // Add to Sunday worked hours
										$newRecord->TotalHoursSunday = $TotalHoursSunday;
									} else { // regular day monday to saturday
										// If date is Holiday
										//dd(count(value: $Holiday));
										if (count(value: $Holiday) > 0) {
											$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
											$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
											$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
											$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM
			
											$checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
											$checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));

											// $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningEnd) / 60 : 0;
			
											$effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
											$workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($morningEnd);
											$workedMorningHours = $workedMorningMinutes / 60;
											// $workedMorningHours = $checkinOne->diffInMinutes($checkoutOne) / 60;
			
											$checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
											$checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

											// $lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;
			
											$effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
											$workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($afternoonEnd);
											$workedAfternoonHours = $workedAfternoonMinutes / 60;
											// $workedAfternoonHours = $checkinTwo->diffInMinutes($checkoutTwo) / 60;
			
											$totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
											// $totalLateHours = $lateMorningHours + $lateAfternoonHours;
			
											// Check type of Holiday
											if ($Holiday[0]->HolidayType == 'Regular') {
												$RegHolidayWorkedHours = $totalWorkedHours;
												// $RegHolidayWorkedHours = $totalWorkedHours - $totalLateHours;
												$TotalHrsRegularHol += $RegHolidayWorkedHours;
												$newRecord->TotalHrsRegularHol = $TotalHrsRegularHol;

											} else if ($Holiday[0]->HolidayType == 'Special') {
												$SpecialHolidayWorkedHours = $totalWorkedHours;
												// $SpecialHolidayWorkedHours = $totalWorkedHours - $totalLateHours;
												$TotalHrsSpecialHol += $SpecialHolidayWorkedHours;
												$newRecord->TotalHrsSpecialHol = $TotalHrsSpecialHol;

											}
											// else {
											// 	$netWorkedHours = $totalWorkedHours - $totalLateHours;
											// }
			
											// $TotalHours += $netWorkedHours;
										} else { // regular Day
											$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
											$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
											$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
											$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM
			
											$checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
											$checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));

											// $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningStart) / 60 : 0;
			
											$effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
											$workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($morningEnd);
											$workedMorningHours = $workedMorningMinutes / 60;
											// $workedMorningHours = $checkinOne->diffInMinutes($morningEnd) / 60;
			
											$checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
											$checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

											// $lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;
			
											$effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
											$workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($afternoonEnd);
											$workedAfternoonHours = $workedAfternoonMinutes / 60;

											$totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
											// $totalLateHours = $lateMorningHours + $lateAfternoonHours;
											$netWorkedHours = $totalWorkedHours
											;
											// $netWorkedHours = $totalWorkedHours - $totalLateHours;
											// $SundayWorkedHours = $totalSundayWorkedHours - $totalSundayLateHours;
			
											$TotalHours += $netWorkedHours;
											$newRecord->TotalHours = $TotalHours;
										}
									}
								}

								// FOR OVERTIME WORKED HOUR
								// $checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
								// $checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));
								// $OtDate = \App\Models\Overtime::where('Date', substr($attendanceDate, 0, 10))
								// 	->where('EmployeeID', $employee->id)
								// 	->get();
			
								// if (count($OtDate) > 0 && $attendanceDate == $OtDate[0]->Date) {
			
								// 	$In1s = $OtDate[0]->Checkin;
								// 	$InOT = explode(':', $In1s);
			
								// 	$Out1s = $OtDate[0]->Checkout;
								// 	$OutOT = explode(':', $Out1s);
			
								// 	$OTStart = Carbon::createFromTime($InOT[0], $InOT[1], $InOT[2]); // 8:00 AM
								// 	$OTEnd = Carbon::createFromTime($OutOT[0], $OutOT[1], $OutOT[2]);  // 12:00 PM
			
								// 	$checkinOT = Carbon::createFromFormat('H:i', substr($attendances["Overtime_In"], 0, 5));
								// 	$checkoutOT = Carbon::createFromFormat('H:i', substr($attendances["Overtime_Out"], 0, 5));
			
								// 	// $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningEnd) / 60 : 0;
			
								// 	$effectiveCheckinOT = $checkinOT->greaterThan($OTStart) ? $checkinOT : $OTStart;
								// 	$workedOTMinutes = $effectiveCheckinOT->diffInMinutes($OTEnd);
								// 	$workedOTHours = $workedOTMinutes / 60;
			
								// 	$TotalOvertimeHours += $workedOTHours;
								// 	$newRecord->TotalOvertimeHours = $TotalOvertimeHours;
			
								// }
			
						

							}
							// dd($newRecord->EmployeeID);
							$OtDate = \App\Models\Overtime::where('EmployeeID', $newRecord->EmployeeID)
							->where('Status', 'approved') // Only consider approved overtime
							->get();

						if (count($OtDate) > 0) {

							foreach ($OtDate as $otRecord) {
								// Extract the check-in and check-out times from the overtime record
								$In1s = $otRecord->Checkin;
								$InOT = explode(':', $In1s);

								$Out1s = $otRecord->Checkout;
								$OutOT = explode(':', $Out1s);

								// Create Carbon instances for the check-in and check-out times
								$OTStart = Carbon::createFromTime($InOT[0], $InOT[1], $InOT[2]);
								$OTEnd = Carbon::createFromTime($OutOT[0], $OutOT[1], $OutOT[2]);

								// Calculate the overtime worked in minutes, then convert to hours
								$workedOTMinutes = $OTStart->diffInMinutes($OTEnd);
								$workedOTHours = $workedOTMinutes / 60;

								// Add to the total overtime hours
								$TotalOvertimeHours += $workedOTHours;
							}

							// Store the total overtime hours in the new record
							$newRecord->TotalOvertimeHours = $TotalOvertimeHours;
						}

							// For Earnings
							$GetEarnings = \App\Models\Earnings::where('PeriodID', $record->weekPeriodID)
								->where('EmployeeID', $employee->id)
								->get();
							$Earnings = $GetEarnings;

							if (count($Earnings) > 0) {
								$EarningPay = $Earnings[0]->Amount;
								$newRecord->EarningPay = $EarningPay;
								// $TotalEarningPay = $EarningPay;
							}


							// For Deductions
							$GetDeductions = \App\Models\Deduction::where('PeriodID', $record->weekPeriodID)
								->where('EmployeeID', $employee->id)
								->get();
							$Deductions = $GetDeductions;

							if (count($Deductions) > 0) {
								$DeductionFee = $Deductions[0]->Amount;
								$newRecord->DeductionFee = $DeductionFee;
								// $TotalEarningPay = $EarningPay;
							}

							$GetSSS = \App\Models\sss::get();

							$GetPagibig = \App\Models\pagibig::get();

							$GetPhilHealth = \App\Models\philhealth::get();

							// $weekPeriod = \App\Models\WeekPeriod::where('id', $record->weekPeriodID)->first();
			
							if ($weekPeriod) {
								// For Kinsenas (1st Kinsena or 2nd Kinsena)
								if ($weekPeriod->Category == 'Kinsenas') {
									$deductionFactor = $weekPeriod->Type == '1st Kinsena' || $weekPeriod->Type == '2nd Kinsena' ? 2 : 1;

									// SSS Deduction for Kinsenas (1st or 2nd half of the month)
									foreach ($GetSSS as $sss) {
										if ($sss->MinSalary <= $employee->MonthlySalary && $sss->MaxSalary >= $employee->MonthlySalary) {
											$SSSDeduction = $sss->EmployeeShare / $deductionFactor;
											$newRecord->SSSDeduction = $SSSDeduction;
											break;
										}
									}

									// PagIbig Deduction for Kinsenas
									foreach ($GetPagibig as $pagibig) {
										if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
											$PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / $deductionFactor;
											$newRecord->PagIbigDeduction = $PagIbigDeduction;
											break;
										}
									}

									// PhilHealth Deduction for Kinsenas
									foreach ($GetPhilHealth as $philhealth) {
										if ($philhealth->MinSalary <= $employee->MonthlySalary && $philhealth->MaxSalary >= $employee->MonthlySalary) {
											if ($philhealth->PremiumRate == '0.00') {
												$PhilHealthDeduction = $philhealth->ContributionAmount / $deductionFactor;
											} else {
												$PhilHealthDeduction = (($philhealth->PremiumRate / 100) * $employee->MonthlySalary) / $deductionFactor;
											}
											$newRecord->PhilHealthDeduction = $PhilHealthDeduction;
											break;
										}
									}

								} elseif ($weekPeriod->Category == 'Weekly') {
									// For Weekly (Week 1, Week 2, Week 3, or Week 4)
									$deductionFactor = 4; // Weekly deductions are typically divided into 4 parts
			
									// SSS Deduction for Weekly
									foreach ($GetSSS as $sss) {
										if ($sss->MinSalary <= $employee->MonthlySalary && $sss->MaxSalary >= $employee->MonthlySalary) {
											$SSSDeduction = $sss->EmployeeShare / $deductionFactor;
											$newRecord->SSSDeduction = $SSSDeduction;
											break;
										}
									}

									// PagIbig Deduction for Weekly
									foreach ($GetPagibig as $pagibig) {
										if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
											$PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / $deductionFactor;
											$newRecord->PagIbigDeduction = $PagIbigDeduction;
											break;
										}
									}

									// PhilHealth Deduction for Weekly
									foreach ($GetPhilHealth as $philhealth) {
										if ($philhealth->MinSalary <= $employee->MonthlySalary && $philhealth->MaxSalary >= $employee->MonthlySalary) {
											if ($philhealth->PremiumRate == '0.00') {
												$PhilHealthDeduction = $philhealth->ContributionAmount / $deductionFactor;
											} else {
												$PhilHealthDeduction = (($philhealth->PremiumRate / 100) * $employee->MonthlySalary) / $deductionFactor;
											}
											$newRecord->PhilHealthDeduction = $PhilHealthDeduction;
											break;
										}
									}
								}
							}

							$BasicPay = $TotalHours * $employee->HourlyRate;
							$newRecord->BasicPay = $BasicPay;

							$TotalOvertimePay = $TotalOvertimeHours * $employee->HourlyRate * 1.25;
							$newRecord->TotalOvertimePay = $TotalOvertimePay;

							$SundayPay = $TotalHoursSunday * $employee->HourlyRate * 1.30;
							$newRecord->SundayPay = $SundayPay;

							$SpecialHolidayPay = $TotalHrsSpecialHol ? $TotalHrsSpecialHol * $employee->HourlyRate * 1.30 : 0;
							$newRecord->SpecialHolidayPay = $SpecialHolidayPay;

							$RegularHolidayPay = $TotalHrsRegularHol ? $TotalHrsRegularHol * $employee->HourlyRate : 0;
							$newRecord->RegularHolidayPay = $RegularHolidayPay;

							$GrossPay = $EarningPay + $BasicPay + $SundayPay + $SpecialHolidayPay + $RegularHolidayPay + $TotalOvertimePay;
							$newRecord->GrossPay = $GrossPay;
							$TotalDeductions = $PagIbigDeduction + $SSSDeduction + $PhilHealthDeduction + $DeductionFee;
							$newRecord->TotalDeductions = $TotalDeductions;

							$TotalGovDeductions = $PagIbigDeduction + $SSSDeduction + $PhilHealthDeduction;
							$newRecord->TotalGovDeductions = $TotalGovDeductions;

							$TotalOfficeDeductions = $DeductionFee;
							$newRecord->TotalOfficeDeductions = $TotalOfficeDeductions;

							$NetPay = $GrossPay - $TotalDeductions;
							$newRecord->NetPay = $NetPay;
							// dd(
							// 	$TotalHours,
							// 	'TotalHours',
							// 	$employee->HourlyRate,
							// 	'empRate',
							// 	$BasicPay,
							// 	'bscPay',
							// 	$GrossPay,
							// 	'grsPay',
							// 	$TotalHoursSunday,
							// 	'hrsSun',
							// 	$TotalHrsRegularHol,
							// 	'regHrdHol',
							// 	$TotalHrsSpecialHol,
							// 	'spcHrdHol',
							// 	$SpecialHolidayWorkedHours,
							// 	'spclpay',
							// 	$RegularHolidayPay,
							// 	'regpay',
							// 	$EarningPay,
							// 	'Earningpay',
							// 	$record->$TotalDeductions,
							// 	'ttlDeduct',
							// 	$TotalOvertimeHours,
							// 	'totalOvertimeHrs',
							// 	$NetPay,
							// 	'netPay'
							// );
			
							// dd($GrossPay, $NetPay);
							// ================
							// 
							// Add the new record to the payrollRecords collection
							$payrollRecords->push($newRecord->toArray());
							// $record->NetPay = self::calculateNetPay($record);
							// $record->save();
							// return Excel::download(new PayrollExport($payrollRecords), 'payroll_' . $record->id . '.xlsx');
			
						}
						// dd($payrollRecords);
						return Excel::download(new PayrollExport($payrollRecords), 'payroll_' . $record->EmployeeID . '.xlsx');
						// } 
						// else {
						// 	// dd($record->toArray());
			
						// 	$employees = \App\Models\Employee::where('employment_type', $record->EmployeeStatus)
						// 		->where('project_id', $record->ProjectID)
						// 		->join('positions', 'employees.position_id', '=', 'position_id')
						// 		->get();
			
						// 	// return Excel::download(new PayrollExport($record), 'payroll_' . $record->id . '.xlsx');
						// }
					}),
			])
			->bulkActions([
				Tables\Actions\BulkActionGroup::make([
					Tables\Actions\DeleteBulkAction::make(),
				]),
			]);
	}

	/**
	 * Example payroll calculation method.
	 *
	 * @param Payroll|array $data
	 * @return float
	 */
	protected static function calculateNetPay($data)
	{
		// Implement your payroll calculation logic here.
		// This is a dummy implementation.
		// Replace with your actual calculation logic.
		if ($data instanceof Payroll) {
			// If $data is a Payroll model instance
			return $data->GrossPay - $data->TotalDeductions;
		} elseif (is_array($data)) {
			// If $data is an array from the form
			// Example calculation based on form data
			// Adjust as necessary
			$grossPay = isset($data['GrossPay']) ? floatval($data['GrossPay']) : 0;
			$totalDeductions = isset($data['TotalDeductions']) ? floatval($data['TotalDeductions']) : 0;
			return $grossPay - $totalDeductions;
		}

		return 0;
	}
	public static function getRelations(): array
	{
		return [
			//
		];
	}

	public static function getPages(): array
	{
		return [
			'index' => Pages\ListPayrolls::route('/'),
			'create' => Pages\CreatePayroll::route('/create'),
			'edit' => Pages\EditPayroll::route('/{record}/edit'),
		];
	}
}
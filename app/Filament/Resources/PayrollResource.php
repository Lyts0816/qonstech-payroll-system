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
						Select::make('EmployeeStatus')
							->label('Employee Status')
							->required(fn(string $context) => $context === 'create' || $context === 'edit')
							->options([
								'Regular' => 'Regular',
								'Non-Regular' => 'Non-Regular',
								'Project Based' => 'Project Based',
							])
							// ->native(false)
							->default(request()->query('employee'))
							->reactive()
							->afterStateUpdated(function (callable $set, $state) {
								if ($state === 'Regular' || $state === 'Non-Regular') {
									// If so, set ProjectID to null
									$set('ProjectID', null);
								}
							}),

						Select::make('ProjectID')
							->label('Project')
							->required(fn(callable $get) => $get('EmployeeStatus') === 'Project Based')
							->options(
								function (callable $get) {
									// dd($get('EmployeeStatus') === 'Project Based');
									return $get('EmployeeStatus') === 'Project Based'
										? Project::query()->pluck('ProjectName', 'id')->toArray()
										: [];
								}
							)
							// ->options(
							// 		Project::query()
							// 		->pluck('ProjectName', 'id')
							// 		->toArray()
							// 		)
							// ->native(false)
							->disabled(fn(callable $get) => $get('EmployeeStatus') !== 'Project Based')
							->default(null) // Reset default if not project based
							->nullable()
						,

					]),

				// The rest of the fields below in a single-column layout
				Select::make('PayrollFrequency')
					->label('Payroll Frequency')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options([
						'Kinsenas' => 'Kinsenas (Bi-monthly)',
						'Weekly' => 'Weekly',
					])
					->default('Kinsenas')
					->native(false)
					->reactive(),

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
					->disabled(false)
					->default(request()->query('date')),

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
					->default(date('F')),

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
					->default(date('Y')),

				Select::make('weekPeriodID')
					->label('Period')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options(function (callable $get) {
						// Ensure PeriodID is reactive to EmployeeID
						$month = $get('PayrollMonth');

						if ($month) {
							$monthId = DateTime::createFromFormat('F', $month)->format('m');
							return WeekPeriod::where('Month', $monthId)
								->where('Category', $get('PayrollFrequency'))
								->where('Type', $get('PayrollDate2'))
								->where('Year', $get('PayrollYear'))
								->get()
								->mapWithKeys(function ($period) {
									return [
										$period->id => $period->StartDate . ' - ' . $period->EndDate
									];
								});
						}
						return [];
					})
					->reactive() // Add reactivity here,

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
						// if (!$record->ProjectID) {
							$employeesWPosition = \App\Models\Employee::where('employment_type', $record->EmployeeStatus)
								->join('positions', 'employees.position_id', '=', 'position_id')
								->with('schedule')
								->get();

							foreach ($employeesWPosition as $employee) {
								$record->first_name = $employee->first_name;
								$record->middle_name = $employee->middle_name;
								$record->last_name = $employee->last_name;
								$record->first_name = $employee->first_name;
								$record->position = $employee->PositionName;
								$record->monthlySalary = $employee->MonthlySalary;
								$record->hourlyRate = $employee->HourlyRate;
								$record->SalaryType = 'OPEN';
								$record->RegularStatus = $employee->employment_type == 'Regular' ? 'YES' : 'NO';

								if ($record->PayrollFrequency == 'Kinsenas') {
									switch ($record->PayrollDate2) {
										case '1st Kinsena':
											// Set start date for the 1st to 15th
											$StartEndDates = \App\Models\WeekPeriod::where('id', $record->weekPeriodID)
												->get();
											// Set start date for the 1st to 7th of the month
											$startDate = $StartEndDates[0]->StartDate;
											$endDate = $StartEndDates[0]->EndDate;
											// $startDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month, 1);
											// $endDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month, 15);
											break;

										case '2nd Kinsena':
											// Set start date for the 16th to the end of the month
											$StartEndDates = \App\Models\WeekPeriod::where('id', $record->weekPeriodID)
												->get();
											// Set start date for the 1st to 7th of the month
											$startDate = $StartEndDates[0]->StartDate;
											$endDate = $StartEndDates[0]->EndDate;
											// $startDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month, 16);
											// $endDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month)->endOfMonth();
											break;

										default:
											$startDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month, 1);
											$endDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month, 15);
									}

									$attendance = \App\Models\Attendance::where('Employee_ID', $employee->id)
										->whereBetween('Date', [$startDate, $endDate])
										->get();

								} else if ($record->PayrollFrequency == 'Weekly') {
									switch ($record->PayrollDate2) {
										case 'Week 1':
											$StartEndDates = \App\Models\WeekPeriod::where('id', $record->weekPeriodID)
												->get();

											// Set start date for the 1st to 7th of the month
											$startDate = $StartEndDates[0]->StartDate;
											$endDate = $StartEndDates[0]->EndDate;
											break;

										case 'Week 2':
											// Set start date for the 8th to 14th of the month
											$StartEndDates = \App\Models\WeekPeriod::where('id', $record->weekPeriodID)
												->get();
											// Set start date for the 1st to 7th of the month
											$startDate = $StartEndDates[0]->StartDate;
											$endDate = $StartEndDates[0]->EndDate;
											// // Set start date for the 8th to 14th of the month
											break;

										case 'Week 3':
											// Set start date for the 15th to 21st of the month
											$StartEndDates = \App\Models\WeekPeriod::where('id', $record->weekPeriodID)
												->get();
											// Set start date for the 1st to 7th of the month
											$startDate = $StartEndDates[0]->StartDate;
											$endDate = $StartEndDates[0]->EndDate;
											// // Set start date for the 15th to 21st of the month
											break;

										case 'Week 4':
											// Set start date for the 22nd to the end of the month
											$StartEndDates = \App\Models\WeekPeriod::where('id', $record->weekPeriodID)
												->get();
											// Set start date for the 1st to 7th of the month
											$startDate = $StartEndDates[0]->StartDate;
											$endDate = $StartEndDates[0]->EndDate;
											// // Set start date for the 22nd to the end of the month
											break;

										default:
											// Default to the first week in case of an unexpected value
											$startDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month, 1);
											$endDate = Carbon::create($record->PayrollYear, Carbon::parse($record->PayrollMonth)->month, 7);
									}

									$attendance = \App\Models\Attendance::where('Employee_ID', $employee->id)
										->whereBetween('Date', [$startDate, $endDate])
										->orderBy('Date', 'ASC')
										->get();

								}

								$finalAttendance = $attendance;
								$TotalHours = 0;
								$TotalHoursSunday = 0;
								$TotalHrsSpecialHol = 0;
								$TotalHrsRegularHol = 0;
								$TotalEarningPay = 0;
								$SpecialHolidayWorkedHours = 0;
								$RegHolidayWorkedHours = 0;
								$TotalDeductions = 0;
								$SSSDeduction = 0;
								$PagIbigDeduction = 0;
								$PhilHealthDeduction = 0;
								$EarningPay = 0;
								$RegHolidayWorkedHours = 0; // initialize as zero
								$SpecialHolidayWorkedHours = 0;
								$TotalOvertimeHours = 0;
								$DeductionFee = 0;
								foreach ($finalAttendance as $attendances) {

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
											$record->TotalHoursSunday = $TotalHoursSunday;
										} else { // regular day monday to saturday
											// If date is Holiday
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
													$record->TotalHrsRegularHol = $TotalHrsRegularHol;

												} else if ($Holiday[0]->HolidayType == 'Special') {
													$SpecialHolidayWorkedHours = $totalWorkedHours;
													// $SpecialHolidayWorkedHours = $totalWorkedHours - $totalLateHours;
													$TotalHrsSpecialHol += $SpecialHolidayWorkedHours;
													$record->TotalHrsSpecialHol = $TotalHrsSpecialHol;

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
												$record->TotalHours = $TotalHours;
											}
										}
									}

									// FOR OVERTIME WORKED HOUR
									// $checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
									// $checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));
									$OtDate = \App\Models\Overtime::where('Date', substr($attendanceDate, 0, 10))
										->where('EmployeeID', $employee->id)
										->get();

									if (count($OtDate) > 0 && $attendanceDate == $OtDate[0]->Date) {

										$In1s = $OtDate[0]->Checkin;
										$InOT = explode(':', $In1s);

										$Out1s = $OtDate[0]->Checkout;
										$OutOT = explode(':', $Out1s);

										$OTStart = Carbon::createFromTime($InOT[0], $InOT[1], $InOT[2]); // 8:00 AM
										$OTEnd = Carbon::createFromTime($OutOT[0], $OutOT[1], $OutOT[2]);  // 12:00 PM
			
										$checkinOT = Carbon::createFromFormat('H:i', substr($attendances["Overtime_In"], 0, 5));
										$checkoutOT = Carbon::createFromFormat('H:i', substr($attendances["Overtime_Out"], 0, 5));

										// $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningEnd) / 60 : 0;
			
										$effectiveCheckinOT = $checkinOT->greaterThan($OTStart) ? $checkinOT : $OTStart;
										$workedOTMinutes = $effectiveCheckinOT->diffInMinutes($OTEnd);
										$workedOTHours = $workedOTMinutes / 60;

										$TotalOvertimeHours += $workedOTHours;
										$record->TotalOvertimeHours = $TotalOvertimeHours;

									}
									
								}

								// For Earnings
								$GetEarnings = \App\Models\Earnings::where('PeriodID', $record->weekPeriodID)
									->where('EmployeeID', $employee->id)
									->get();
								$Earnings = $GetEarnings;

								if (count($Earnings) > 0) {
									$EarningPay = $Earnings[0]->Amount;
									$record->EarningPay = $EarningPay;
									// $TotalEarningPay = $EarningPay;
								}


								// For Deductions
								$GetDeductions = \App\Models\Deduction::where('PeriodID', $record->weekPeriodID)
									->where('EmployeeID', $employee->id)
									->get();
								$Deductions = $GetDeductions;

								if (count($Deductions) > 0) {
									$DeductionFee = $Deductions[0]->Amount;
									$record->DeductionFee = $DeductionFee;
									// $TotalEarningPay = $EarningPay;
								}

								$GetSSS = \App\Models\sss::get();

								$GetPagibig = \App\Models\pagibig::get();

								$GetPhilHealth = \App\Models\philhealth::get();

								if ($record->PayrollFrequency == 'Kinsenas') {
									foreach ($GetSSS as $sss) {
										if ($sss->MinSalary >= $employee->MonthlySalary && $sss->MaxSalary <= $employee->MonthlySalary) {
											$SSSDeduction = $sss->EmployeeShare / 2;
											$record->SSSDeduction = $SSSDeduction;
											break;
										}
									}
									foreach ($GetPagibig as $pagibig) {
										if ($pagibig->MinimumSalary >= $employee->MonthlySalary && $pagibig->MaximumSalary <= $employee->MonthlySalary) {
											$PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / 2;
											$record->PagIbigDeduction = $PagIbigDeduction;
											break;
										}
									}
									foreach ($GetPhilHealth as $philhealth) {
										if ($philhealth->MinSalary >= $employee->MonthlySalary && $philhealth->MaxSalary <= $employee->MonthlySalary) {
											if ($philhealth->PremiumRate == '0.00') {
												$PhilHealthDeduction = $philhealth->ContributionAmount / 2;
												$record->PhilHealthDeduction = $PhilHealthDeduction;
											} else {
												$PhilHealthDeduction = (($philhealth->PremiumRate / 100) * $employee->MonthlySalary) / 2;
												$record->PhilHealthDeduction = $PhilHealthDeduction;
											}
											break;
										}
									}
								} else if ($record->PayrollFrequency == 'Weekly') {		// FOR WEEKLY DEDUCTIONS
									foreach ($GetSSS as $sss) {
										if ($sss->MinSalary >= $employee->MonthlySalary && $sss->MaxSalary <= $employee->MonthlySalary) {
											$SSSDeduction = $sss->EmployeeShare / 4;
											$record->SSSDeduction = $SSSDeduction;
											break;
										}
									}
									foreach ($GetPagibig as $pagibig) {
										if ($pagibig->MinimumSalary >= $employee->MonthlySalary && $pagibig->MaximumSalary <= $employee->MonthlySalary) {
											$PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / 4;
											$record->PagIbigDeduction = $PagIbigDeduction;
											break;
										}
									}
									foreach ($GetPhilHealth as $philhealth) {
										if ($philhealth->MinSalary >= $employee->MonthlySalary && $philhealth->MaxSalary <= $employee->MonthlySalary) {
											if ($philhealth->PremiumRate == '0.00') {
												$PhilHealthDeduction = $philhealth->ContributionAmount / 4;
												$record->PhilHealthDeduction = $PhilHealthDeduction;
											} else {
												$PhilHealthDeduction = (($philhealth->PremiumRate / 100) * $employee->MonthlySalary) / 4;
												$record->PhilHealthDeduction = $PhilHealthDeduction;
											}
											break;
										}
									}
								}

								$BasicPay = $TotalHours * $employee->HourlyRate;
								$record->BasicPay = $BasicPay;
								// $OTPay = $TotalHours * $employee->HourlyRate;
								$SundayPay = $TotalHoursSunday * $employee->HourlyRate * 1.30;
								$record->SundayPay = $SundayPay;

								$SpecialHolidayPay = $SpecialHolidayWorkedHours ? $SpecialHolidayWorkedHours * $employee->HourlyRate * 1.30 : 0;
								$record->SpecialHolidayPay = $SpecialHolidayPay;

								$RegularHolidayPay = $RegHolidayWorkedHours ? $RegHolidayWorkedHours * $employee->HourlyRate : 0;
								$record->RegularHolidayPay = $RegularHolidayPay;

								$GrossPay = $EarningPay + $BasicPay + $SundayPay + $SpecialHolidayPay + $RegularHolidayPay;
								$record->GrossPay = $GrossPay;
								$TotalDeductions = $PagIbigDeduction + $SSSDeduction + $PhilHealthDeduction + $DeductionFee;
								$record->TotalDeductions = $TotalDeductions;

								$NetPay = $GrossPay - $TotalDeductions;
								$record->NetPay = $NetPay;
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
			
							}

							$payrollRecords = collect([
								$record->toArray(),
								// Add more records as needed
							]);
							// $record->NetPay = self::calculateNetPay($record);
							// $record->save();
							return Excel::download(new PayrollExport($payrollRecords), 'payroll_' . $record->id . '.xlsx');
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

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayslipResource\Pages;
use App\Filament\Resources\PayslipResource\RelationManagers;
use App\Models\Payslip;
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
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\ButtonAction;

class PayslipResource extends Resource
{
    protected static ?string $model = Payslip::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                                } else if ($state === 'Non-Regular' || $state === 'Project Based') {
                                    $set('PayrollFrequency', 'Weekly');
                                }

                            }),
                        Select::make('EmployeeID')
                            ->label('Select Employee')
                            ->required()
                            ->options(
                                fn($get) =>
                                Employee::where('employment_type', $get('EmployeeStatus'))
                                    ->get() // Get all records first
                                    ->mapWithKeys(function ($employee) {
                                        return [$employee->id => $employee->first_name . ' ' . $employee->last_name];
                                    }) // Combine names
                            )
                            ->reactive(),
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
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee Name')
                    ->searchable()
                    ->sortable(),

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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('viewPayslip')
                    ->label('View Payslip')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->url(fn($record) => route('payslip-records', ['EmployeeID' => $record->id])) // Pass the employee ID
                    ->openUrlInNewTab()
                    ->action(function ($record) {
                        // Removed action logic as we are only setting the URL
                    })


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
     * @param Payslip|array $data
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
            'index' => Pages\ListPayslips::route('/'),
            'create' => Pages\CreatePayslip::route('/create'),
            'edit' => Pages\EditPayslip::route('/{record}/edit'),
        ];
    }
}
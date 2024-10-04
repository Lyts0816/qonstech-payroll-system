<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Models\Loan;
use App\Models\Employee;
use App\Models\WeekPeriod;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $title = 'Loan';
    protected static ?string $navigationGroup = "Employee Payroll";

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Section::make('Loan Information')
                ->schema([

                    Select::make('EmployeeID')
                        ->label('Employee')
                        ->options(Employee::all()->pluck('full_name', 'id'))
                        ->required()
                        ->preload()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set) {
                            // Clear dependent fields
                            $set('PeriodID', null);
                            $set('LoanAmount', null);
                            $set('LoanType', null);
                            $set('NumberOfPayments', null);
                            $set('WeeklyDeduction', null); 
                            $set('KinsenaDeduction', null); 
                            $set('MonthlyDeduction', null); // Clear monthly deduction
                        }),

                    Select::make('PeriodID')
                        ->label('Select Period')
                        ->options(function (callable $get) {
                            $employeeId = $get('EmployeeID');
                            if ($employeeId) {
                                $employee = Employee::find($employeeId);
                                if ($employee) {
                                    $category = $employee->employment_type === 'Regular' ? 'Kinsenas' : 'Weekly';
                                    return WeekPeriod::where('Category', $category)->get()
                                        ->mapWithKeys(function ($period) {
                                            return [
                                                $period->id => $period->StartDate . ' - ' . $period->EndDate
                                            ];
                                        });
                                }
                            }
                            return [];
                        })
                        ->required()
                        ->reactive(),

                    Select::make('LoanType')
                        ->label('Loan Type')
                        ->options([
                            'Salary Loan' => 'Salary Loan',
                            'SSS Loan' => 'SSS Loan',
                            'Pagibig Loan' => 'Pagibig Loan',
                        ])
                        ->required(),

                    TextInput::make('LoanAmount')
                        ->label('Loan Amount')
                        ->required()
                        ->numeric()
                        ->reactive()
                        ->afterStateUpdated(function (callable $get, callable $set) {
                            $employeeId = $get('EmployeeID');
                            $employee = Employee::find($employeeId);
                            $employmentType = $employee ? $employee->employment_type : null;
                            $loanAmount = $get('LoanAmount');
                            $numberOfPayments = $get('NumberOfPayments');

                            if (!is_null($loanAmount) && !is_null($numberOfPayments) && $numberOfPayments > 0) {
                                // Calculate the various deductions
                                $weeklyDeduction = self::calculateWeeklyDeduction($loanAmount, $numberOfPayments, $employmentType);
                                $kinsenaDeduction = self::calculateKinsenaDeduction($loanAmount, $numberOfPayments, $employmentType);
                                $monthlyDeduction = self::calculateMonthlyDeduction($loanAmount, $numberOfPayments);

                                // Set the deduction values
                                $set('WeeklyDeduction', $weeklyDeduction);
                                $set('KinsenaDeduction', $kinsenaDeduction);
                                $set('MonthlyDeduction', $monthlyDeduction);
                            }
                        }),

                    TextInput::make('NumberOfPayments')
                        ->label('Number of Monthly Payments')
                        ->required()
                        ->numeric()
                        ->reactive()
                        ->afterStateUpdated(function (callable $get, callable $set) {
                            $employeeId = $get('EmployeeID');
                            $employee = Employee::find($employeeId);
                            $employmentType = $employee ? $employee->employment_type : null;
                            $loanAmount = $get('LoanAmount');
                            $numberOfPayments = $get('NumberOfPayments');

                            if (!is_null($loanAmount) && !is_null($numberOfPayments) && $numberOfPayments > 0) {
                                // Calculate the various deductions
                                $weeklyDeduction = self::calculateWeeklyDeduction($loanAmount, $numberOfPayments, $employmentType);
                                $kinsenaDeduction = self::calculateKinsenaDeduction($loanAmount, $numberOfPayments, $employmentType);
                                $monthlyDeduction = self::calculateMonthlyDeduction($loanAmount, $numberOfPayments);

                                // Set the deduction values
                                $set('WeeklyDeduction', $weeklyDeduction);
                                $set('KinsenaDeduction', $kinsenaDeduction);
                                $set('MonthlyDeduction', $monthlyDeduction);
                            }
                        }),

                        TextInput::make('MonthlyDeduction')
                        ->numeric()
                        ->dehydrated(true), // Ensure it is included in the form data


                    // Hidden fields for deductions
                    TextInput::make('KinsenaDeduction')
                    ->numeric()
                    ->dehydrated(true), // Ensure it is included in the form data

                    TextInput::make('WeeklyDeduction')
                        ->numeric()
                        ->dehydrated(true), // Ensure it is included in the form data

                  
                   
                ])->columns(4)->collapsible(true),
        ]);
}

// Helper functions to calculate deductions
private static function calculateWeeklyDeduction($loanAmount, $numberOfPayments, $employmentType)
{
    // Calculate Weekly Deduction for non-regular employees
    return $loanAmount / ($numberOfPayments * 4);
}

private static function calculateKinsenaDeduction($loanAmount, $numberOfPayments, $employmentType)
{
    // Calculate Kinsena Deduction for regular employees
    return $loanAmount / ($numberOfPayments * 2);
}

private static function calculateMonthlyDeduction($loanAmount, $numberOfPayments)
{
    // Calculate Monthly Deduction regardless of employment type
    return $loanAmount / $numberOfPayments;
}


    public static function table(Table $table): Table
    {
        // Calculate the total loan amount
        $totalLoanAmount = Loan::sum('LoanAmount');
    
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee'),
    
                Tables\Columns\TextColumn::make('LoanType')
                    ->label('Loan Type'),
    
                Tables\Columns\TextColumn::make('PeriodID') // This will reference the period
                    ->label('Period')
                    ->formatStateUsing(function ($state, $record) {
                        // Assuming $record->weekperiod exists and contains StartDate and EndDate
                        return $record->weekperiod ? 
                            $record->weekperiod->StartDate . ' - ' . $record->weekperiod->EndDate : 
                            'N/A'; // Handle case where no period is found
                    }),
    
                Tables\Columns\TextColumn::make('LoanAmount')
                    ->label('Loan Amount'),
    
                Tables\Columns\TextColumn::make('NumberOfPayments')
                    ->label('Number Of Monthly Payments'),
    
                // Weekly Deduction (for non-regular employees)
                Tables\Columns\TextColumn::make('MonthlyDeduction')
                    ->label('Monthly Deduction'),
                                         
                Tables\Columns\TextColumn::make('Balance')
                    ->label('Balance'),
            ])
            ->filters([
                // You can add any filters if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
            // Optionally render the total loan amount in the header
            // ->header(fn () => view('filament.loan-total', ['totalLoanAmount' => $totalLoanAmount]));
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
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}

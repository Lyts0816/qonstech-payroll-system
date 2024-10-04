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
                        ->reactive() // Make this field reactive
                        ->afterStateUpdated(function ($state, $set) {
                            // Clear PeriodID when EmployeeID changes
                            $set('PeriodID', null);
                    
                            // Reset LoanAmount, NumberOfPayments, and WeeklyDeduction
                            $set('LoanAmount', null);
                            $set('LoanType', null);
                            $set('NumberOfPayments', null);
                            $set('WeeklyDeduction', null); // Reset the deduction amount
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
                        ->required(fn (string $context) => $context === 'create')
                        ->reactive() // Add reactivity here
                        ->afterStateUpdated(function ($state, $set) {
                            // Clear any dependent fields if necessary
                        }),

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
                            ->required(fn (string $context) => $context === 'create')
                            ->numeric()
                            ->reactive() // Make reactive
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                // Get the selected EmployeeID
                                $employeeId = $get('EmployeeID'); // Assuming this is the field where you select the employee
                                
                                // Fetch the employee's employment type
                                $employee = Employee::find($employeeId);
                                $employmentType = $employee ? $employee->employment_type : null; // Assuming 'employment_type' is the column name
                        
                                // Get the selected DeductionPeriod
                                $deductionPeriod = $get('DeductionPeriod');
                        
                                // Get Loan Amount and Number of Payments
                                $loanAmount = $get('LoanAmount');
                                $numberOfPayments = $get('NumberOfPayments');
                        
                                // Calculate the deduction only if LoanAmount and NumberOfPayments are set and valid
                                if (!is_null($loanAmount) && !is_null($numberOfPayments) && $numberOfPayments > 0) {
                                    $deduction = self::calculateDeduction($loanAmount, $numberOfPayments, $deductionPeriod, $employmentType);
                                    // Set the calculated WeeklyDeduction
                                    $set('WeeklyDeduction', $deduction);
                                } else {
                                    $set('WeeklyDeduction', 0); // or handle accordingly
                                }
                            }),
                        
                        TextInput::make('NumberOfPayments')
                            ->label('Number of Monthly Payments')
                            ->required()
                            ->numeric()
                            ->reactive() // Make reactive
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                // Get the selected EmployeeID
                                $employeeId = $get('EmployeeID');
                        
                                // Fetch the employee's employment type
                                $employee = Employee::find($employeeId);
                                $employmentType = $employee ? $employee->employment_type : null; // Assuming 'employment_type' is the column name
                        
                                // Get the selected DeductionPeriod
                                $deductionPeriod = $get('DeductionPeriod');
                        
                                // Get Loan Amount and Number of Payments
                                $loanAmount = $get('LoanAmount');
                                $numberOfPayments = $get('NumberOfPayments');
                        
                                // Calculate the deduction only if LoanAmount and NumberOfPayments are set and valid
                                if (!is_null($loanAmount) && !is_null($numberOfPayments) && $numberOfPayments > 0) {
                                    $deduction = self::calculateDeduction($loanAmount, $numberOfPayments, $deductionPeriod, $employmentType);
                                    // Set the calculated WeeklyDeduction
                                    $set('WeeklyDeduction', $deduction);
                                } else {
                                    $set('WeeklyDeduction', 0); // or handle accordingly
                                }
                            }),
    
                        // Select::make('DeductionPeriod')
                        //     ->label('Deduction Period')
                        //     ->options([
                        //         'Kinsenas' => 'Kinsenas',
                        //         'Weekly' => 'Weekly',
                        //     ])
                        //     ->disabled(), // Set dynamically based on employee status
    
                        TextInput::make('WeeklyDeduction')
                        ->label(function (callable $get) {
                            // Get the selected EmployeeID
                            $employeeId = $get('EmployeeID');
                    
                            if (!$employeeId) {
                                // Return default label if no employee is selected
                                return 'Deduction Amount';
                            }
                    
                            // Fetch the employee's employment type
                            $employee = Employee::find($employeeId);
                            $employmentType = $employee ? $employee->employment_type : null; // Assuming 'employment_type' is the column name
                    
                            // Determine the label based on employment type
                            return $employmentType === 'Regular' ? 'Kinsena Deduction Amount' : 'Weekly Deduction Amount';
                        })
                        ->disabled()
                        ->numeric()
                        ->dehydrated(true), // Ensure it is submitted
                    
                    ])->columns(4)->collapsible(true),
            ]);
    }

    public static function calculateDeduction($loanAmount, $numberOfPayments, $deductionPeriod, $employmentType)
    {
        // Check if Loan Amount and Number of Payments are set and valid
        if (is_null($loanAmount) || is_null($numberOfPayments) || $numberOfPayments <= 0) {
            return 0; // or handle it however you prefer, e.g., return null or throw an exception
        }
    
        // Determine the category based on employment type
        if ($employmentType === 'Regular') {
            // Kinsenas for regular employees
            return $loanAmount / $numberOfPayments; // Example calculation logic for Kinsenas
        } else {
            // Weekly for non-regular employees
            return $loanAmount / ($numberOfPayments * 4); // Example calculation logic for Weekly
        }
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
                Tables\Columns\TextColumn::make('WeeklyDeduction')
                    ->label('Weekly/Kinsena Deduction'),
                                         
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

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Models\Loan;
use App\Models\Employee;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Loan Information')
                    ->schema([
                        Select::make('EmployeeID')
                            ->label('Employee')
                            ->options(Employee::all()->pluck('full_name', 'id'))
                            ->required(fn (string $context) => $context === 'create')
                            ->preload()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $employee = Employee::find($state);
    
                                // Assuming there's an 'is_regular' attribute to determine the employee's status
                            if ($employee && $employee->employment_type === 'Regular') {
                                    // Regular employee, use Kinsenas
                                    $set('DeductionPeriod', 'Kinsenas');
                                } else {
                                    // Non-regular employee, use Weekly
                                    $set('DeductionPeriod', 'Weekly');
                                }
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
                                $set('WeeklyDeduction', self::calculateDeduction($get('LoanAmount'), $get('NumberOfPayments'), $get('DeductionPeriod')));
                            }),
    
                        TextInput::make('NumberOfPayments')
                            ->label('Number of Payments')
                            ->required()
                            ->numeric()
                            ->reactive() // Make reactive
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $set('WeeklyDeduction', self::calculateDeduction($get('LoanAmount'), $get('NumberOfPayments'), $get('DeductionPeriod')));
                            }),
    
                        Select::make('DeductionPeriod')
                            ->label('Deduction Period')
                            ->options([
                                'Kinsenas' => 'Kinsenas',
                                'Weekly' => 'Weekly',
                            ])
                            ->disabled(), // Set dynamically based on employee status
    
                        TextInput::make('WeeklyDeduction')
                            ->label('Deduction Amount')
                            ->disabled()
                            ->numeric()
                            ->dehydrated(true), // Ensure it is submitted
    
                        TextInput::make('StartDate')
                            ->label('Start Paying')
                            ->required(fn (string $context) => $context === 'create')
                            ->type('date'),
                    ])->columns(4)->collapsible(true),
            ]);
    }

    public static function calculateDeduction($loanAmount, $numberOfPayments, $deductionPeriod)
    {
        if ($deductionPeriod === 'Kinsenas') {
            // Kinsenas deduction calculation (twice a month)
            return $loanAmount / ($numberOfPayments * 2);
        } elseif ($deductionPeriod === 'Weekly') {
            // Weekly deduction calculation
            return $loanAmount / ($numberOfPayments * 4);
        }
        
        return 0;
    }

    public static function table(Table $table): Table
    {

        $totalLoanAmount = Loan::sum('LoanAmount');
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee'),

                Tables\Columns\TextColumn::make('LoanType')
                    ->label('Loan Type'),

                Tables\Columns\TextColumn::make('StartDate')
                    ->label('Start Date'),

                Tables\Columns\TextColumn::make('LoanAmount')
                    ->label('Loan Amount'),

                Tables\Columns\TextColumn::make('NumberOfPayments')
                    ->label('Number Of Payments'),

                Tables\Columns\TextColumn::make('WeeklyDeduction')
                    ->label('Weekly Deduction'),

                Tables\Columns\TextColumn::make('Balance')
                    ->label('Balance'),

            ])
            ->filters([
                
            ])
             // Render total)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // ->header(fn () => view('filament.loan-total', ['totalLoanAmount' => $totalLoanAmount])) // Render total)
            ;
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

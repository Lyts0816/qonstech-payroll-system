<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeductionResource\Pages;
use App\Filament\Resources\DeductionResource\RelationManagers;
use App\Models\Deduction;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Employee;
use App\Models\pagibig;
use App\Models\philhealth;
use App\Models\sss;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeductionResource extends Resource
{
    protected static ?string $model = Deduction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "Employee Payroll";

    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('employeeID')
                ->options(
                    Employee::all()->pluck('full_name', 'id')
                )
                ->reactive() 
                ->required(),

            Select::make('DeductionType')
                ->options([
                    'SSS' => 'SSS',
                    'PhilHealth' => 'PhilHealth',
                    'Pagibig' => 'Pagibig',
                    'CashAdvance' => 'CashAdvance',
                    'Undertime' => 'Undertime',
                    'SalaryAdjustment' => 'SalaryAdjustment',
                    'Loan' => 'Loan',
                ])
                ->reactive()
                ->afterStateUpdated(function (callable $get, callable $set) {
                    $employeeID = $get('employeeID');
                    $deductionType = $get('DeductionType');
                    
                    if ($employeeID && in_array($deductionType, ['SSS', 'PhilHealth', 'Pagibig'])) {
                        $employee = Employee::find($employeeID);
                        $position = $employee->position;

                        if ($position) {
                            
                            $sss = SSS::where('MinSalary', '<=', $position->MonthlySalary)
                                      ->where('MaxSalary', '>=', $position->MonthlySalary)
                                      ->first();
                            $philhealth = Philhealth::where('MinSalary', '<=', $position->MonthlySalary)
                                                    ->where('MaxSalary', '>=', $position->MonthlySalary)
                                                    ->first();
                            $pagibig = Pagibig::first(); 

                            
                            if ($deductionType === 'SSS') {
                                $sssMonthlyPayment = $sss->EmployeeShare ?? 0;
                                $set('Amount', number_format($sssMonthlyPayment, 2, '.', ''));
                            }

                            if ($deductionType === 'PhilHealth') {
                                $philhealthMonthlyPayment = $position->MonthlySalary <= 10000
                                    ? ($philhealth->MonthlyRate ?? 0)
                                    : ($position->MonthlySalary * ($philhealth->PremiumRate / 100));
                                $set('Amount', number_format($philhealthMonthlyPayment, 2, '.', ''));
                            }

                            if ($deductionType === 'Pagibig') {
                                $pagibigMonthlyPayment = $position->MonthlySalary < 1500
                                    ? ($position->MonthlySalary * 0.01)
                                    : ($position->MonthlySalary * 0.02);
                                $set('Amount', number_format($pagibigMonthlyPayment, 2, '.', ''));
                            }
                        }
                    }
                })
                ->required(),

            TextInput::make('Amount')
                ->required(),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name'),
                TextColumn::make('DeductionType'),
                TextColumn::make('Amount'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListDeductions::route('/'),
            'create' => Pages\CreateDeduction::route('/create'),
            'edit' => Pages\EditDeduction::route('/{record}/edit'),
        ];
    }
}

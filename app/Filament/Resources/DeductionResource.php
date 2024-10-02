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

    protected static ?string $navigationGroup = "Employee/Deduction/Earnings";

    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Employee Select Field
                Select::make('employeeID')
                    ->label('Employee')
                    ->options(Employee::all()->pluck('full_name', 'id'))
                    ->reactive()
                    ->required()
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $employeeID = $get('employeeID');
                        if ($employeeID) {
                            $employee = Employee::find($employeeID);
    
                            if ($employee) {
                                if ($employee->employment_type === 'Regular') {
                                    // Regular employee, use Kinsenas
                                    $set('Period', 'Kinsenas');
                                } else {
                                    // Non-regular employee, use Weekly
                                    $set('Period', 'Weekly');
                                }
                            }
                        }
                    }),
    
                // Deduction Type - Leave only Salary Adjustment
                Select::make('DeductionType')
                    ->label('Deduction Type')
                    ->options([
                        'SalaryAdjustment' => 'Salary Adjustment',
                    ])
                    ->default('SalaryAdjustment'),
                                         
                // Amount Field
                TextInput::make('Amount')
                    ->label('Amount')
                    ->required()
                    ->numeric(),

                TextInput::make('StartDate')
                    ->label('Start Paying')
                    ->required(fn (string $context) => $context === 'create')
                    ->type('date'),
            ]);
    }
    


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                ->label('Employee'),

            TextColumn::make('DeductionType')
                ->label('Deduction Type'),

            TextColumn::make('StartDate')
                ->label('Start Paying'),
            
            TextColumn::make('Amount')
                ->label('Amount'),               
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

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EarningsResource\Pages;
use App\Filament\Resources\EarningsResource\RelationManagers;
use App\Models\Earnings;
use App\Models\Employee;
use App\Models\Overtime;
use Faker\Provider\ar_EG\Text;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EarningsResource extends Resource
{
    protected static ?string $model = Earnings::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "Employee/Deduction/Earnings";

    public static function calculateTotal($holiday, $leave, $overtimeRate)
    {
        return $holiday + $leave + $overtimeRate;
    }

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Section::make('Earnings Information')
                ->schema([
                    // Employee Select Field
                    Select::make('EmployeeID')
                        ->label('Employee')
                        ->options(Employee::all()->pluck('full_name', 'id'))
                        ->required()
                        ->preload()
                        ->searchable(),

                    // Earnings Type Select Field
                    Select::make('EarningType')
                        ->label('Earnings Type')
                        ->options([
                            'Other Allowance' => 'Other Allowance',
                        ])
                        ->required()
                        ->default('Other Allowance'), // Pre-select 'Other Allowance'
                  

                    // Amount Input Field
                    TextInput::make('Amount')
                        ->label(label: 'Amount')
                        ->required()
                        ->numeric()
                        ->minValue(0), // Ensure no negative amounts are input

                    TextInput::make('StartDate')
                        ->label('Start Paying')
                        ->required(fn (string $context) => $context === 'create')
                        ->type('date'),
                ])
                ->columns(2) // Set the layout to two columns for better UI alignment
                ->collapsible(true), // Allow the section to collapse for better user experience
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee'),

                TextColumn::make('EarningType')
                    ->label('Earning Type'),

                TextColumn::make('StartDate')
                    ->label('Start Paying'),
                
                TextColumn::make('Amount')
                    ->label('Amount'),
            ])
            ->filters([
                
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
            'index' => Pages\ListEarnings::route('/'),
            'create' => Pages\CreateEarnings::route('/create'),
            'edit' => Pages\EditEarnings::route('/{record}/edit'),
        ];
    }
}

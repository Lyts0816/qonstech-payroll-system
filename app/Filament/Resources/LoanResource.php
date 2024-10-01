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
                            ->required(fn (string $context) => $context === 'create')
                            ->preload()
                            ->searchable(),

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
                            ->numeric(),

                        TextInput::make('Balance')
                            ->label('Balance')
                            ->required(fn (string $context) => $context === 'create')
                            ->numeric(),

                        TextInput::make('MonthlyDeduction')
                            ->label('Monthly Deduction')
                            ->required()
                            ->numeric(),

                        TextInput::make('StartDate')
                            ->label('Start Date')
                            ->required(fn (string $context) => $context === 'create')
                            ->type('date'),

                        TextInput::make('EndDate')
                            ->label('End Date')
                            ->required(fn (string $context) => $context === 'create')
                            ->type('date'),
                    ])->columns(4)->collapsible(true),
            ]);
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

                Tables\Columns\TextColumn::make('LoanAmount')
                    ->label('Loan Amount'),

                Tables\Columns\TextColumn::make('Balance')
                    ->label('Balance'),

                Tables\Columns\TextColumn::make('MonthlyDeduction')
                    ->label('Monthly Deduction'),

                Tables\Columns\TextColumn::make('StartDate')
                    ->label('Start Date'),

                Tables\Columns\TextColumn::make('EndDate')
                    ->label('End Date'),
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

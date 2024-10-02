<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages;
use App\Filament\Resources\PayrollResource\RelationManagers;
use App\Models\Payroll;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Project;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
                Forms\Components\TextInput::make('EmployeeID')
                    ->label('Employee ID')
                    ->disabled()
                    ->default(request()->query('employee')),

                Forms\Components\TextInput::make('PayrollDate')
                    ->label('Payroll Date')
                    ->disabled()
                    ->default(request()->query('date')),

                Forms\Components\TextInput::make('TotalEarnings')
                    ->label('Total Earnings')
                    ->disabled()
                    ->default(request()->query('earnings')),

                Forms\Components\TextInput::make('GrossPay')
                    ->label('Gross Pay')
                    ->disabled()
                    ->default(request()->query('gross')),

                Forms\Components\TextInput::make('TotalDeductions')
                    ->label('Total Deductions')
                    ->disabled()
                    ->default(request()->query('deductions')),
                    
                Forms\Components\TextInput::make('NetPay')
                    ->label('Net Pay')
                    ->disabled()
                    ->default(request()->query('net')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('EmployeeID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.project.ProjectName')
                    ->Label('Project Name'),

                Tables\Columns\TextColumn::make('PayrollDate')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('TotalEarnings')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('GrossPay')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('TotalDeductions')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('NetPay')
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
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OvertimeResource\Pages;
use App\Filament\Resources\OvertimeResource\RelationManagers;
use App\Filament\Resources\OvertimeResource\RelationManagers\EmployeesRelationManager;
use App\Models\Overtime;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
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

class OvertimeResource extends Resource
{
    protected static ?string $model = Overtime::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Overtime';

    protected static ?string $navigationGroup = "Overtime/Assign";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Section::make('Overtime Information')
                ->schema([
                    TextInput::make('Reason')
                    ->required(fn (string $context) => $context === 'create')
                    ->unique(ignoreRecord: true)
                    ->rules('regex:/^[^\d]*$/'),

    
                    TextInput::make('Checkin')
                    ->label('Check-in Time')
                    ->type('time')
                    ->required(fn (string $context) => $context === 'create'),
                        
                    TextInput::make('Checkout')
                    ->label('Check-out Time')
                    ->type('time')
                    ->after('Checkin')
                    ->required(fn (string $context) => $context === 'create'),
    
    
                    DatePicker::make('Date')
                        ->required(fn (string $context) => $context === 'create')
                        ,
    
    
                    Select::make('Status')
                    ->label('Status')
                    ->options([
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Pending' => 'Pending',
    
                    ])
                    ->searchable()
                    ->preload(),

                ])->columns(3),
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Reason')->searchable(),
                TextColumn::make('Date')->searchable(),
                // TextColumn::make('OvertimeRate')->searchable(),
                TextColumn::make('Status')->searchable(),
                TextColumn::make('Checkin')->searchable(),
                TextColumn::make('Checkout')->searchable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

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
            EmployeesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOvertimes::route('/'),
            'create' => Pages\CreateOvertime::route('/create'),
            'edit' => Pages\EditOvertime::route('/{record}/edit'),
            'view' => Pages\ViewEmployee::route('/{record}'),
        ];
    }
}

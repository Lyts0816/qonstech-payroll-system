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

    protected static ?string $navigationGroup = "Employee Payroll";

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
                        Select::make('EmployeeID')
                        ->label('Employee')
                        ->options(Employee::all()->pluck('full_name', 'id'))
                        ->required()
                        ->preload()
                        ->searchable(),
                
                    Select::make('OvertimeID')
                        ->label('Overtime')
                        ->relationship('overtime', 'Reason')
                        ->required()
                        ->preload()
                        ->searchable()
                        ->reactive()  
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                           
                            $overtimeRate = Overtime::find($state)?->OvertimeRate ?? 0;
                            $set('OvertimeRate', $overtimeRate);  
                            
                            $set('Total', self::calculateTotal($get('Holiday'), $get('Leave'), $overtimeRate));
                        }),
                
                    TextInput::make('OvertimeRate')
                        ->label('Overtime Rate')
                        ->readOnly()  
                        ->numeric()
                        ->default(0),
                
                    TextInput::make('Holiday')
                        ->label('Holiday Pay')
                        ->required()
                        ->numeric()
                        ->reactive()  
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            
                            $set('Total', self::calculateTotal($state, $get('Leave'), $get('OvertimeRate')));
                        }),
                
                    TextInput::make('Leave')
                        ->label('Leave Pay')
                        ->required()
                        ->numeric()
                        ->reactive() 
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            
                            $set('Total', self::calculateTotal($get('Holiday'), $state, $get('OvertimeRate')));
                        }),
                
                    TextInput::make('Total')
                        ->label('Total')
                        ->readOnly(),

                        
                    ])->columns(2)->collapsible(true),
                
            ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee'),

                TextColumn::make('overtime.OvertimeRate')
                    ->label('Overtime Rate Total'),
                
                TextColumn::make('Holiday')
                    ->label('Holiday Pay'),

                TextColumn::make('Leave')
                    ->label('Leave Pay'),

                    TextColumn::make('Total')
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

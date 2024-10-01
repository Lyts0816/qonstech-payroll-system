<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Filament\Resources\HolidayResource\RelationManagers;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-date-range';
    protected static ?string $navigationGroup = "Employee Payroll";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
							Section::make('Holiday')
							->schema([
									TextInput::make('HolidayName')
									->label('Holiday Name')
									->required(fn (string $context) => $context === 'create')
									->unique(ignoreRecord: true)
									->rules('regex:/^[^\d]*$/'),

									DatePicker::make('HolidayDate')
									->label('Holiday Date')
									->required(fn (string $context) => $context === 'create'),
									
									Select::make('HolidayType')
									->label('Holiday Type')
									->required(fn (string $context) => $context === 'create')
									->options([
										'Regular' => 'Regular',
										'Special' => 'Special'
								])->native(false),
							])->columns(3)->collapsible(true),
          ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
							TextColumn::make('HolidayName')
                ->label('Holiday Name')
								->searchable(['HolidayName']),

							TextColumn::make('HolidayDate')
                ->label('Holiday Date'),

							TextColumn::make('HolidayType')
                ->label('Holiday Type')
                
            ])
            ->filters([
							SelectFilter::make('HolidayType')
							->label('Filter by Holiday Type')
							->options(
									Holiday::query()
											->pluck('HolidayType', 'HolidayType')
											->toArray()
							)
							->searchable()
							->multiple()
							->preload(),
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
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ButtonAction;
use PhpParser\Node\Stmt\Label;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-s-view-columns';
    protected static ?string $title = 'Attendance';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Add your form fields here if needed
        ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
<<<<<<< HEAD
                TextColumn::make('employee.full_name')
                ->label('Employee Name'),

                TextColumn::make('employee.employment_type')
                ->label('Employment Type'),
                
                TextColumn::make('employee.project.ProjectName')
                ->Label('Project Name'),

                TextColumn::make('employee.schedule.ScheduleName')
                ->Label('Schedule'),

                TextColumn::make('Checkin_One')
                ->label('Morning Check-in')
                ,

                TextColumn::make('Checkout_One')
                ->label('Morning Checkout')
                ,

                TextColumn::make('Checkin_Two')
                ->label('Afternoon Check-in')
                ,

                TextColumn::make('Checkout_Two')
                ->label('Afternoon Checkout')
                ,

=======
>>>>>>> d822a0f04a7f706c68be290956cfa4304096c82e
                TextColumn::make('Date')
                    ->label('Date')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return \Carbon\Carbon::parse($record->Date)->format('F j, Y');
                    }),
                TextColumn::make('Checkin_One')->label('Morning Check-in'),
                TextColumn::make('Checkout_One')->label('Morning Checkout'),
                TextColumn::make('Checkin_Two')->label('Afternoon Check-in'),
                TextColumn::make('Checkout_Two')->label('Afternoon Checkout'),
                TextColumn::make('Total_Hours')->label('Total Hours')->sortable(),
            ])
            ->recordUrl(function ($record) {
                return null;
            })
            ->filters([
<<<<<<< HEAD
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

                SelectFilter::make('schedule_id')
                ->label('Select Work Schedule')
                ->options(WorkSched::all()->pluck('ScheduleName', 'id'))
                
                ->query(function (Builder $query, array $data) {
                    if (empty($data['value'])) {
                        
                        return $query;
                    }
                    return $query->whereHas('employee.schedule', function (Builder $query) use ($data) {
                        $query->where('id', $data['value']);
                    });
                }),

                SelectFilter::make('employment_type')
                ->label('Select Employment Type')
                ->options([
                    'Regular' => 'Regular',
                    'Project Based' => 'Project Based',
                ])
                ->query(function (Builder $query, array $data) {
                    if (empty($data['value'])) {
                        return $query;
                    }
                    return $query->whereHas('employee', function (Builder $query) use ($data) {
                        $query->where('employment_type', $data['value']);
                    });
                }),

                SelectFilter::make('date_filter')
                ->label('Select Date Filter')
                ->options([
                    'daily' => 'Daily',
                    'weekly' => 'Weekly',
                ])
                ->query(function (Builder $query, array $data) {
                    if (empty($data['value'])) {
                        return $query;
                    }
                    if ($data['value'] === 'daily') {
                        return $query->whereDate('Date', now()->toDateString());
                    } elseif ($data['value'] === 'weekly') {
                        return $query->whereBetween('Date', [now()->startOfWeek(), now()->endOfWeek()]);
                    }
                }),


               
=======
                // Employee select filter
                Filter::make('employee_id')
                    ->form([
                        Forms\Components\Select::make('employee_id')
                            ->label('Select Employee')
                            ->options(Employee::all()->pluck('full_name', 'id'))
                            ->extraAttributes([
                                'class' => 'h-12 text-lg',
                                'style' => 'width: 110%;'
                            ])
                            ->required(),
                    ])
                    ->query(
                        fn(Builder $query, array $data) =>
                        !empty ($data['employee_id']) ? $query->where('employee_id', $data['employee_id']) : null
                    ),
                // Date range filter with two columns
                Filter::make('date_range')
                    ->form([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('start_date')
                                    ->label('Start Date')
                                    ->type('date')
                                    ->default(now()->startOfMonth()->toDateString())
                                    ->extraAttributes(['style' => 'width: 125%;']),
                                Forms\Components\TextInput::make('end_date')
                                    ->label('End Date')
                                    ->type('date')
                                    ->default(now()->endOfMonth()->toDateString())
                                    ->extraAttributes(['style' => 'width: 125%;']),
                            ])
                            ->columns(2), // Set to 2 columns for side-by-side display
                    ])
                    ->query(
                        fn(Builder $query, array $data) =>
                        !empty ($data['start_date']) && !empty ($data['end_date']) ?
                        $query->whereBetween('Date', [$data['start_date'], $data['end_date']]) : null
                    ),
>>>>>>> d822a0f04a7f706c68be290956cfa4304096c82e
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                ButtonAction::make('viewDtr')
                    ->label('View DTR')
                    ->color('primary')
                    ->url(fn() => route('dtr.show', [
                        'employee_id' => 1,
                    ]))

                    ->openUrlInNewTab(),

<<<<<<< HEAD
            ->actions([
                // Tables\Actions\DeleteAction::make(),
=======
>>>>>>> d822a0f04a7f706c68be290956cfa4304096c82e
            ])




            ->bulkActions([]);


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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            // 'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}

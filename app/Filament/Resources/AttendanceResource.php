<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Resources\Resource;
use Filament\Tables;
use App\Models\Employee;
use App\Models\Project;
use App\Models\WorkSched;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PhpParser\Node\Stmt\Label;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-s-view-columns';

    protected static ?string $title = 'Attendance';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Date')
                    ->label('Date')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return \Carbon\Carbon::parse($record->Date)->format('F j, Y'); // Example: October 1, 2024
                    }),
                TextColumn::make('Checkin_One')->label('Morning Check-in'),
                TextColumn::make('Checkout_One')->label('Morning Checkout'),
                TextColumn::make('Checkin_Two')->label('Afternoon Check-in'),
                TextColumn::make('Checkout_Two')->label('Afternoon Checkout'),
                TextColumn::make('Total_Hours')->label('Total Hours')->sortable(),
            ])
            ->filters([
                // Employee select filter
                Filter::make('employee_id')
                    ->form([
                        Forms\Components\Select::make('employee_id')
                            ->label('Select Employee')
                            ->options(Employee::all()->pluck('full_name', 'id'))
                            ->required(),  // Ensure the field is required
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['employee_id'])) {
                            return $query->where('employee_id', $data['employee_id']);
                        }
                    }),
    
                // Date range filter with two columns
                Filter::make('date_range')
                    ->form([
                        Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\TextInput::make('start_date')
                                ->label('Start Date')
                                ->type('date')
                                ->default(now()->startOfMonth()->toDateString()), // Start of last month

                            Forms\Components\TextInput::make('end_date')
                                ->label('End Date')
                                ->type('date')
                                ->default(now()->endOfMonth()->toDateString()), // End of last month
                        ])
                            ->columns(columns: 1),  // 2 columns for date inputs
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['start_date']) && !empty($data['end_date'])) {
                            return $query->whereBetween('Date', [$data['start_date'], $data['end_date']]);
                        }
                    }),
            ], layout: FiltersLayout::AboveContent)
            
            // ->actions([
            //     Tables\Actions\DeleteAction::make(),
            // ])
            ->bulkActions([]);
    }
    
    // Make sure to adjust the method that retrieves the data for the table
    public function getData()
    {
        $query = Employee::query(); // Replace YourModel with the actual model you are querying
    
        // Check if filters are applied
        $filters = request('filters', []);
    
        // If no filters are applied, return an empty result set
        if (empty($filters['employee_id']) && empty($filters['date_range']['start_date']) && empty($filters['date_range']['end_date'])) {
            return collect(); // Return an empty collection to ensure no data is displayed
        }
    
        // Apply the filters to the query
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
    
        if (!empty($filters['date_range']['start_date']) && !empty($filters['date_range']['end_date'])) {
            $query->whereBetween('Date', [$filters['date_range']['start_date'], $filters['date_range']['end_date']]);
        }
    
        return $query->get();
    }
    
    
    

    
public function getFormattedDateAttribute()
{
    return \Carbon\Carbon::parse($this->Date)->format('F j, Y'); // Example: October 1, 2024
}
    

    // public static function table(Table $table): Table
    // {
    //     return $table
    //         ->columns([
    //             TextColumn::make('employee.full_name')
    //             ->label('Employee Name'),
                
    //             TextColumn::make('employee.project.ProjectName')
    //             ->Label('Project Name'),

    //             TextColumn::make('Checkin_One')
    //             ->label('Morning Check-in')
    //             ,

    //             TextColumn::make('Checkout_One')
    //             ->label('Morning Checkout')
    //             ,

    //             TextColumn::make('Checkin_Two')
    //             ->label('Afternoon Check-in')
    //             ,

    //             TextColumn::make('Checkout_Two')
    //             ->label('Afternoon Checkout')
    //             ,

    //             TextColumn::make('Date')
    //             ->label('Date')
    //             ->sortable(),

    //             TextColumn::make('Total_Hours')
    //             ->label('Total Hours')
    //             ->sortable()
            
    //         ])
    //         ->filters([
    //             SelectFilter::make('project_id')
    //             ->label('Select Project')
    //             ->options(Project::all()->pluck('ProjectName', 'id'))
                
    //             ->query(function (Builder $query, array $data) {
    //                 if (empty($data['value'])) {
                        
    //                     return $query;
    //                 }
    //                 return $query->whereHas('employee.project', function (Builder $query) use ($data) {
    //                     $query->where('id', $data['value']);
    //                 });
    //             }),

    //             SelectFilter::make('schedule_id')
    //             ->label('Select Work Schedule')
    //             ->options(WorkSched::all()->pluck('ScheduleName', 'id'))
                
    //             ->query(function (Builder $query, array $data) {
    //                 if (empty($data['value'])) {
                        
    //                     return $query;
    //                 }
    //                 return $query->whereHas('employee.schedule', function (Builder $query) use ($data) {
    //                     $query->where('id', $data['value']);
    //                 });
    //             }),


               
    //         ], layout: FiltersLayout::AboveContent)


    //         ->actions([
    //             Tables\Actions\DeleteAction::make(),
    //         ])
    //         ->bulkActions([

    //         ]);
    // }

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

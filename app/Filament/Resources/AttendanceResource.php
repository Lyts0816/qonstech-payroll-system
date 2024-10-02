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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                TextColumn::make('employee.full_name')
                ->label('Employee Name'),

                TextColumn::make('employee.employment_type')
                ->label('Employment Type'),
                
                TextColumn::make('employee.project.ProjectName')
                ->Label('Project Name'),

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

                TextColumn::make('Date')
                ->label('Date')
                ->sortable(),

                TextColumn::make('Total_Hours')
                ->label('Total Hours')
                ->sortable()
            
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


               
            ], layout: FiltersLayout::AboveContent)


            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([

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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}

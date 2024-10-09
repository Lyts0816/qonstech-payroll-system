<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Project;
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
use Filament\Tables\Actions\Action;
use PhpParser\Node\Stmt\Label;
use Illuminate\Support\Facades\Session;

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
            ->filters(
                [
                    Filter::make('employee_filter')
                        ->form([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Select::make('selectedEmployeeId')
                                        ->label('Select Employee')
                                        ->options(Employee::all()->pluck('full_name', 'id'))
                                        ->extraAttributes(['class' => 'h-12 text-lg', 'style' => 'width: 100%;'])
                                        ->required(),


                                ])
                                ->columns(1),
                        ])
                        ->query(
                            function (Builder $query, array $data) {
                                if (!empty($data['selectedEmployeeId'])) {
                                    Session::put('selected_employee_id', $data['selectedEmployeeId']);
                                    $query->where('employee_id', $data['selectedEmployeeId']);
                                }

                                return $query;
                            }
                        ),

                    Filter::make('project_filter')
                        ->form([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Select::make('selectedProjectId')
                                        ->label('Select Project')
                                        ->options(Project::all()->pluck('ProjectName', 'id'))
                                        ->extraAttributes(['class' => 'h-12 text-lg', 'style' => 'width: 100%;'])
                                        ->required(),
                                ])
                                ->columns(1),
                        ])
                        ->query(
                            function (Builder $query, array $data) {
                                if (!empty($data['selectedProjectId'])) {
                                    Session::put('selected_project_id', $data['selectedProjectId']);
                                    $query->where('ProjectID', $data['selectedProjectId']); // Make sure to use project_id for filtering
                                }
                                return $query;
                            }
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
                                ->columns(2),
                        ])
                        ->query(
                            fn(Builder $query, array $data) =>
                            !empty ($data['start_date']) && !empty ($data['end_date']) ?
                            $query->whereBetween('Date', [$data['start_date'], $data['end_date']]) : null
                        ),
                ],

                layout: FiltersLayout::AboveContent
            )
            ->headerActions([
                Action::make('viewDtr')
                    ->label('View DTR')
                    ->color('primary')
                    ->url(fn() => route('dtr.show', [
                        'employee_id' => Session::get('selected_employee_id'), // Get from session
                        'project_id' => Session::get('selected_project_id'), // Get from session
                    ]))
                    ->openUrlInNewTab(),
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

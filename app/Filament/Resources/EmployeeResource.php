<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use App\Models\Position;
use App\Models\WorkSched;
use Faker\Provider\ar_EG\Text;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Relationship;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PhpParser\Node\Stmt\Label;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-c-user-group';

    protected static ?string $navigationGroup = "Employee Details";

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Name')
                ->schema([
                    TextInput::make('first_name')
                    ->label('First Name')
                    ->required(fn (string $context) => $context === 'create' || 'edit')
                    ->rules('regex:/^[^\d]*$/'),

                    TextInput::make('middle_name')
                    ->label('Middle Name')
                    ->rules('regex:/^[^\d]*$/'),
                    

                    TextInput::make('last_name')
                    ->label('last Name')
                    ->required(fn (string $context) => $context === 'create' || 'edit')
                    ->rules('regex:/^[^\d]*$/'),

                    TextInput::make('attendance_code')
                    ->label('Attendance Code')
                    ->unique(ignoreRecord: true)
                    ->required(fn (string $context) => $context === 'create' || 'edit')
                    ])->columns(3)->collapsible(true),

                Section::make('Address')
                ->schema([
                    Select::make('province')
                            ->label('Province')
                            ->required(fn (string $context) => $context === 'create' || 'edit')
                            ->options([
                            'Sarangani' => 'Sarangani',
                            'South Cotabato' => 'South Cotabato',
                            ])->native(false),

                    Select::make('city')
                        ->label('City')
                        ->required(fn (string $context) => $context === 'create' || 'edit')
                        ->options([
                            'Koronadal City' => 'Koronadal City',
                            'Polomolok' => 'Polomolok',
                            'Tupi' => 'Tupi',
                            'Tantangan' => 'Tantangan',
                            'Surallah' => 'Surallah',
                            'Lake Sebu' => 'Lake Sebu',
                            'Banga' => 'Banga',
                            'Glan' => 'Glan'
                        ])->native(false),

                    Select::make('barangay')
                        ->label('Barangay')
                        ->required(fn (string $context) => $context === 'create' || 'edit')
                        ->options([
                            'Barangay Zone III' => 'Barangay Zone III',
                            'Cannery Site' => 'Cannery Site',
                            'Poblacion' => 'Poblacion',
                            'Crossing Rubber' => 'Crossing Rubber',
                            'Liberty' => 'Liberty',
                            'Lamian' => 'Lamian',
                            'Rizal' => 'Rizal',
                            'Gumasa' => 'Gumasa',
                        ])->native(false),

                    Select::make('street')
                        ->label('Street')
                        ->required(fn (string $context) => $context === 'create' || 'edit')
                        ->options([
                            'Sanchez St.' => 'Sanchez St.',
                            'Roxas St.' => 'Roxas St.',
                            'GenSan Drive' => 'GenSan Drive',
                            'Polomolok Road' => 'Polomolok Road',
                            'Apolinario Mabini St.' => 'Apolinario Mabini St.',
                            'Bonifacio St.' => 'Bonifacio St.',
                            'Clemente Lapaz Street' => 'Clemente Lapaz Street',
                        ])->native(false),
                ])->columns(4)->collapsible(true),

								Section::make(heading: 'Employment Details')
                ->schema([
                    Select::make('schedule_id')
                    ->label('Work Schedule')
										->options(
											WorkSched::query()
											->pluck('ScheduleName', 'id')
											->toArray()
										)
                    ->required(fn (string $context) => $context === 'create' || 'edit'),

										Select::make('position_id')
										->label('Position')
										->options(
											Position::query()
											->pluck('PositionName', 'id')
											->toArray()
										)
										->required(fn (string $context) => $context === 'create' || 'edit'),

									// Select::make('project_id')
									//     ->label('Project')
											
									//     ->relationship('project', 'ProjectName')
									//     ->native(false)
									//     ->reactive()
									//     ->afterStateUpdated(fn ($state, callable $set) => $set('status', $state ? 'Assigned' : 'Available'))
									//     ,

									// Select::make('schedule_id')
									//     ->label('Work Schedule')
											
									//     ->relationship('schedule', 'ScheduleName')
									//     ->native(false),

									// Select::make('overtime_id')
									//     ->label('Overtime')
									//     ->relationship('overtime', 'Reason')
									//     ->native(false),

                ])->columns(4)->collapsible(true),

                Section::make(heading: 'Other Details')
                ->schema([
                    // Select::make('position_id')
                    //         ->label('Position')
                            
                    //         ->relationship('position', 'PositionName')
                    //         ->native(false),
                    TextInput::make('TaxIdentificationNumber')
                    ->label('Tax ID Number')
                    ->required(fn (string $context) => $context === 'create' || 'edit')
                    ->unique(ignoreRecord: true),

                            TextInput::make('SSSNumber')
                            ->label('SSS Number')
                            ->required(fn (string $context) => $context === 'create' || 'edit')
                            ->unique(ignoreRecord: true),

                            TextInput::make('PhilHealthNumber')
                            ->label('PhilHealth Number')
                            ->required(fn (string $context) => $context === 'create' || 'edit')
                            ->unique(ignoreRecord: true),

                            TextInput::make('PagibigNumber')
                            ->label('Pagibig Number')
                            ->required(fn (string $context) => $context === 'create' || 'edit')
                            ->unique(ignoreRecord: true),
                    // Select::make('project_id')
                    //     ->label('Project')
                        
                    //     ->relationship('project', 'ProjectName')
                    //     ->native(false)
                    //     ->reactive()
                    //     ->afterStateUpdated(fn ($state, callable $set) => $set('status', $state ? 'Assigned' : 'Available'))
                    //     ,

                    // Select::make('schedule_id')
                    //     ->label('Work Schedule')
                        
                    //     ->relationship('schedule', 'ScheduleName')
                    //     ->native(false),

                    // Select::make('overtime_id')
                    //     ->label('Overtime')
                    //     ->relationship('overtime', 'Reason')
                    //     ->native(false),

                ])->columns(4)->collapsible(true),


                Section::make('Contact Number/Status')
                ->schema([

                    TextInput::make('contact_number')
                    ->label('Contact Number')
                    ->required(fn (string $context) => $context === 'create' || 'edit')
                    ->unique(ignoreRecord: true)
                    ->rules('regex:/^[\d]*$/'),

                    Select::make('status')
                        ->label('Status')
                        ->required(fn (string $context) => $context === 'create' || 'edit')
                        ->options([
                            'Assigned' => 'Assigned',
                            'Available' => 'Available',

                        ])->default('Available'),

                    
                        Select::make('employment_type')
                        ->label('Employment Type')
                        ->required(fn (string $context) => $context === 'create' || 'edit')
                        ->options([
                            'Regular' => 'Regular',
                            'Contractual' => 'Contractual',
                        ])->native(false),

                        Select::make('assignment')
                        ->label('Assignment')
                        ->required(fn (string $context) => $context === 'create' || 'edit')
                        ->options([
                            'Main Office' => 'Main Office',
                            'Project Based' => 'Project Based',
                        ])->native(false),


                ])->columns(3)->collapsible(true)



                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Employee ID')->sortable(),
                TextColumn::make('full_name')
                ->label('Name')
                ->formatStateUsing(fn ($record) => $record->first_name . ' ' . ($record->middle_name ? $record->middle_name . ' ' : '') . $record->last_name)
                ->searchable(['first_name', 'middle_name', 'last_name'])
                ,

                TextColumn::make('full_address')
                ->label('Address')
                ->formatStateUsing(fn ($record) => $record->full_address),

                TextColumn::make('TaxIdentificationNumber')->label('Tax Number'),
                TextColumn::make('SSSNumber')->label('SSS Number'),
                TextColumn::make('PhilHealthNumber')->label('PhilHealth Number'),
                TextColumn::make('PagibigNumber')->label('Pagibig Number'),
                TextColumn::make('assignment'),

                TextColumn::make('project.ProjectName'),
                TextColumn::make('position.PositionName'),
                

                TextColumn::make('contact_number'),
                TextColumn::make('employment_type'),
                TextColumn::make('status'),

            ])

            ->filters([
                SelectFilter::make('first_name')
                ->label('Filter by Employee First Name')
                ->options(
                    \App\Models\Employee::query()
                        ->pluck('first_name', 'first_name')
                        ->toArray()
                )
                ->searchable()
                ->multiple()
                ->preload(),

                SelectFilter::make('position_id')
                ->label('Filter by Position')
                ->relationship('position', 'PositionName')
                ->searchable()
                ->multiple()
                ->preload(),

                SelectFilter::make('overtime_id')
                ->label('Filter by Overtime')
                ->relationship('overtime', 'Reason')
                ->searchable()
                ->multiple()
                ->preload(),

                SelectFilter::make('status')
                ->label('Filter by Status')
                ->options([
                    'Assigned' => 'Assigned',
                    'Available' => 'Available',

                ])
                ->searchable()
                ->preload(),

            ])//end of filter

            ->actions([
                Tables\Actions\EditAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),

                BulkAction::make('assign_to_project')
                ->label('Assign to Project')
                ->form([
                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'ProjectName')
                        ->required()
                ])
                ->action(function (array $data, Collection $records) {
                    $projectId = $data['project_id'];

                    foreach ($records as $record) {
                        $record->update(['project_id' => $projectId]);
                    }
                })
                ->deselectRecordsAfterCompletion()
                ->requiresConfirmation(),


                BulkAction::make('assign_to_position')
                ->label('Assign to Position')
                ->form([
                    Select::make('position_id')
                        ->label('Position')
                        ->relationship('position', 'PositionName')
                        ->required()
                ])
                ->action(function (array $data, Collection $records) {
                    $projectId = $data['position_id'];

                    foreach ($records as $record) {
                        $record->update(['position_id' => $projectId]);
                    }
                })
                ->deselectRecordsAfterCompletion()
                ->requiresConfirmation(),


                BulkAction::make('assign_to_overtime')
                ->label('Assign to Overtime')
                ->form([
                    Select::make('overtime_id')
                        ->label('Overtime')
                        ->relationship('overtime', 'Reason')
                        ->required()
                ])
                ->action(function (array $data, Collection $records) {
                    $projectId = $data['overtime_id'];

                    foreach ($records as $record) {
                        $record->update(['overtime_id' => $projectId]);
                    }
                })
                ->deselectRecordsAfterCompletion()
                ->requiresConfirmation()
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
            'show-employees' => ProjectResource\Pages\ShowEmployeesPage::route('/show-employees'),
        ];
    }
}

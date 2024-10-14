<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use App\Models\Employee;
use Dompdf\FrameDecorator\Text;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-s-user';

    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('EmployeeID')
                    ->label('Employee')
                    ->options(Employee::all()->pluck('full_name', 'id'))
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $employee = Employee::find($state);
                        if ($employee) {
                            // Update the text input with the selected employee's full name
                            $set('name', $employee->full_name);

                            $set('role', $employee->position->PositionName);
                        }
                    }),


                    TextInput::make('name')
                    ->label('Name')
                    ->required(fn(string $context) => $context === 'create')
                    ->string()
                    ->rules([
                        'regex:/^[a-zA-Z\s]*$/', 
                        'min:3',            
                        'max:30'            
                    ])
                    ->validationMessages([
                        'regex' => 'The user name must not contain any digits or special characters.',
                        'min' => 'The name must be at least 3 characters long.',
                        'max' => 'The name must not exceed 30 characters.'
                    ]),
                
                TextInput::make('email')
                    ->label('Email')
                    ->required(fn(string $context) => $context === 'create')
                    ->unique(ignoreRecord: true)
                    ->email()
                    ->placeholder('Example@gmail.com')
                    ->rules([
                        'max:30' // Ensures the email is no more than 50 characters long
                    ])
                    ->validationMessages([
                        'email' => 'The email must be a valid email address.',
                        'unique' => 'The email has already been taken.',
                        'max' => 'The email must not exceed 30 characters.'
                    ]),

                Select::make('role') // Field name
                    ->label('Role')
                    ->required(fn(string $context) => $context === 'create')
                    ->options([
                    
                        'Project Clerk' => 'Project Clerk',
                        'Human Resource' => 'Human Resource',
                        'Admin Vice President' => 'Admin Vice President',
                        'Finance Vice President' => 'Finance Vice President',
                    ]), 

                    Fieldset::make('Password')
                        ->schema([
                            TextInput::make('password')
                                ->required(fn(string $context) => $context === 'create')
                                ->visible(fn(string $context) => $context === 'create') // Only show on create
                                ->password()
                                ->confirmed()
                                ->placeholder('Password')
                                ->rule(Password::default()->letters()->mixedCase()->numbers()->symbols())
                                ->maxLength(20),

                            TextInput::make('password_confirmation')
                                ->label('Confirm Password')
                                ->required(fn(string $context) => $context === 'create')
                                ->visible(fn(string $context) => $context === 'create')
                                ->password()
                                ->placeholder('Confirm Password')
                                ->maxLength(20),
                        ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ->query(User::with('roles')) // Eager load the role relationship
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('role')
                    ->searchable(), // Allow searching by role name
                    
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

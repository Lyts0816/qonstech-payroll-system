<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use App\Models\Employee;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\Select;
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
                        }
                    }),


                TextInput::make('name')
                    ->required(fn(string $context) => $context === 'create')
                    ->string()->rules('regex:/^[^\d]*$/'),

                TextInput::make('email')
                    ->required(fn(string $context) => $context === 'create')
                    ->unique(ignoreRecord: true),

                Select::make('role') // Field name
                    ->label('Role')
                    ->options([
                        'Vice President' => 'Vice President',
                        'Project Clerk' => 'Project Clerk',
                        'Human Resource' => 'Human Resource',
                        'Admin Vice President' => 'Admin Vice President',
                        'Finance Vice President' => 'Finance Vice President',
                    ]), 

                TextInput::make('password')
                ->password()
                ->rule(Password::default())
                ->required(fn(string $context) => $context === 'create'),
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

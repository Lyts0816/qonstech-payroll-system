<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use Filament\Resources\Pages\ListRecords;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->unique(Role::class, 'name')
                ->maxLength(255),

            Select::make('modules')
                ->label('Access Modules')
                ->multiple()
                ->relationship('modules', 'name')
                ->preload(), // Optional: Load options in advance
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Role::with('modules')) // Eager load modules here
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('modules') // Reference the relationship
                    ->label('Access Modules')
                    ->formatStateUsing(function ($state) {
                        \Log::info($state); // Log the value of $state
                        return is_array($state) || $state instanceof \Illuminate\Support\Collection
                            ? implode(', ', $state->pluck('name')->toArray())
                            : 'No Modules'; // Fallback if $state is not a collection
                    }),
            ])
            ->filters([
                //
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}

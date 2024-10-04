<?php

namespace App\Livewire;

use Filament\Tables;
use App\Models\Employee;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class Employees extends BaseWidget
{

    public function table(Table $table): Table
    {   
        return $table
            ->query(
                Employee::query()->where('status', 'Available')
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name') 
                ->label('Name')
                ->formatStateUsing(fn ($record) => $record->first_name . ' ' . ($record->middle_name ? $record->middle_name . ' ' : '') . $record->last_name)
                ->sortable()
                ->searchable(),
                
                Tables\Columns\TextColumn::make('position.PositionName'),

                TextColumn::make('full_address')
                ->label('Address')
                ->formatStateUsing(fn ($record) => 
                    trim(
                        $record->street . ', ' . 
                        ($record->barangay ? $record->barangay . ', ' : '') . 
                        $record->city . ', ' . 
                        $record->province
                    )
                ),

                Tables\Columns\TextColumn::make('status')
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

                SelectFilter::make('City')
                ->label('Filter Employee by city')
                ->options(
                    \App\Models\Employee::query()
                        ->pluck('city', 'city')
                        ->toArray()
                )
                ->searchable()
                ->multiple()
                ->preload(),

                SelectFilter::make('Province')
                ->label('Filter Employee by Province')
                ->options(
                    \App\Models\Employee::query()
                        ->pluck('province', 'province')
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

            ])//end of filter

            ->actions([
                // Tables\Actions\EditAction::make(),
            ])


            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    
                ]),

                BulkAction::make('assign_to_project')
                ->label('Assign to Project')
                ->form([
                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'ProjectName')
                        ->required()
                ])
                ->deselectRecordsAfterCompletion()
                ->requiresConfirmation()
                ->action(function (array $data, Collection $records) {
									$projectId = $data['project_id'];

									foreach ($records as $record) {
									    $record->update(['project_id' => $projectId, 'status' => 'Assigned']);
									}
							}),
                
            ]);
    }



}

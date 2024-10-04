<?php

namespace App\Filament\Resources\OvertimeScheduleResource\Pages;

use App\Filament\Resources\OvertimeScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOvertimeSchedules extends ListRecords
{
    protected static string $resource = OvertimeScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

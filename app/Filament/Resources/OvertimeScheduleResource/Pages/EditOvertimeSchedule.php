<?php

namespace App\Filament\Resources\OvertimeScheduleResource\Pages;

use App\Filament\Resources\OvertimeScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOvertimeSchedule extends EditRecord
{
    protected static string $resource = OvertimeScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

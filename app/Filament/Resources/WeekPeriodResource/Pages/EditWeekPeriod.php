<?php

namespace App\Filament\Resources\WeekPeriodResource\Pages;

use App\Filament\Resources\WeekPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeekPeriod extends EditRecord
{
    protected static string $resource = WeekPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

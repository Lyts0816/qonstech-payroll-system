<?php

namespace App\Filament\Resources\WeekPeriodResource\Pages;

use App\Filament\Resources\WeekPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeekPeriods extends ListRecords
{
    protected static string $resource = WeekPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

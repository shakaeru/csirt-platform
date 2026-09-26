<?php

namespace App\Filament\Resources\BoardPeriods\Pages;

use App\Filament\Resources\BoardPeriods\BoardPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBoardPeriods extends ManageRecords
{
    protected static string $resource = BoardPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

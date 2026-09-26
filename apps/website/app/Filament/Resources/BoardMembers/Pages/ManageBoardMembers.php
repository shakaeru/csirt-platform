<?php

namespace App\Filament\Resources\BoardMembers\Pages;

use App\Filament\Resources\BoardMembers\BoardMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBoardMembers extends ManageRecords
{
    protected static string $resource = BoardMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

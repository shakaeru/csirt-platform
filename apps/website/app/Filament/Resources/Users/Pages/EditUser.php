<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Role dan hak akses akun sendiri tidak ikut disimpan walaupun request direkayasa (field-nya
     * juga dinonaktifkan di form): mencegah menaikkan hak akses sendiri atau mengunci diri.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->getRecord()->is(auth()->user())) {
            unset($data['role_id'], $data['permissions']);
        }

        return $data;
    }
}

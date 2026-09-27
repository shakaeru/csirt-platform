<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** Dibuat operator (seperti admin:create), jadi langsung terverifikasi. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'email_verified_at' => now()];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $verifiedAt = $data['email_verified_at'];
        unset($data['email_verified_at']);

        $user = static::getModel()::make($data);
        $user->forceFill(['email_verified_at' => $verifiedAt])->save();

        return $user;
    }
}

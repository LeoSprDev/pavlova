<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    protected array $userRolesToSync = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store user_roles for later processing
        $this->userRolesToSync = $data['user_roles'] ?? [];
        
        // Remove user_roles from data since it's not a User model field
        unset($data['user_roles']);
        
        // Hash password for new user
        if (isset($data['password']) && filled($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Assign roles using the stored data
        if (isset($this->userRolesToSync) && is_array($this->userRolesToSync)) {
            $this->record->assignRole($this->userRolesToSync);
        }
    }
}

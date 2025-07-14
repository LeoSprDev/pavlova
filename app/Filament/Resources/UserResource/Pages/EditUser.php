<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
    
    protected array $userRolesToSync = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store user_roles for later processing
        $this->userRolesToSync = $data['user_roles'] ?? [];
        
        // Remove user_roles from data since it's not a User model field
        unset($data['user_roles']);
        
        // Hash password only if provided
        if (isset($data['password']) && filled($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            // Remove password from data if not provided (keep existing password)
            unset($data['password']);
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Sync roles using the stored data
        if (isset($this->userRolesToSync) && is_array($this->userRolesToSync)) {
            $this->record->syncRoles($this->userRolesToSync);
        }
    }
}

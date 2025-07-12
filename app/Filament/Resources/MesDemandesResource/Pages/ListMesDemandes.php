<?php

namespace App\Filament\Resources\MesDemandesResource\Pages;

use App\Filament\Resources\MesDemandesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMesDemandes extends ListRecords
{
    protected static string $resource = MesDemandesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

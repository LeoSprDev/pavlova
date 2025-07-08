<?php

namespace App\Filament\Resources\DemandeDevisResource\Pages;

use App\Filament\Resources\DemandeDevisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ListDemandeDevis extends ListRecords
{
    protected static string $resource = DemandeDevisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn(): bool => Auth::user()->can('create', \App\Models\DemandeDevis::class)),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        // Add widgets specific to DemandeDevis listing if any
        // e.g. Stats on pending, approved, rejected demands
        return [
            // DemandeDevisStatsWidget::class (if created)
        ];
    }

    // Apply default filters based on role if necessary
    // Example: if service-achat should by default see only 'approved_budget' status
    // protected function getTableFilters(): array
    // {
    //     $user = Auth::user();
    //     $filters = parent::getTableFilters();
    //     if ($user->hasRole('service-achat')) {
    //         // This is tricky as filters are additive.
    //         // Default active filter can be set in table definition using ->default() on a filter.
    //     }
    //     return $filters;
    // }
}

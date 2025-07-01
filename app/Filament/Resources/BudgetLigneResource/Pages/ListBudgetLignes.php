<?php

namespace App\Filament\Resources\BudgetLigneResource\Pages;

use App\Filament\Resources\BudgetLigneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Widgets\BudgetGlobalStatsWidget; // Example, if you create such a widget
use App\Filament\Widgets\ServiceBudgetStatsWidget; // Example for service demandeur
use Illuminate\Support\Facades\Auth;

class ListBudgetLignes extends ListRecords
{
    protected static string $resource = BudgetLigneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        $user = Auth::user();
        $widgets = [];

        // if ($user->hasRole('responsable-budget')) {
        //     $widgets[] = BudgetGlobalStatsWidget::class;
        // } elseif ($user->hasRole('service-demandeur') && $user->service_id) {
        //     // Pass service_id to the widget if it's designed to be specific
        //     $widgets[] = ServiceBudgetStatsWidget::make(['service_id' => $user->service_id]);
        // }
        // Add other widgets as needed, e.g. charts from the prompt.
        // For now, returning empty until those widgets are defined.
        return $widgets;
    }

     /**
     * Get the columns for the table. (For ListRecords\Concerns\HasTable)
     * This is often defined in the Resource's table method, but can be overridden.
     * For now, we rely on the Resource's table() method.
     */

    /**
     * Add default table sort
     */
    protected function getDefaultTableSortColumn(): ?string
    {
        return 'date_prevue';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }
}

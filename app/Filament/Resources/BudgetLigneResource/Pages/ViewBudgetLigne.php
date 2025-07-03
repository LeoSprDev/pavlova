<?php

namespace App\Filament\Resources\BudgetLigneResource\Pages;

use App\Filament\Resources\BudgetLigneResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBudgetLigne extends ViewRecord
{
    protected static string $resource = BudgetLigneResource::class;

    // The form schema from BudgetLigneResource is used by default for viewing.
    // You can customize the view further if needed by overriding methods or adding infolist components.

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            // You might want a "Nouvelle Demande" action here too if contextually appropriate
            Actions\Action::make('nouvelle_demande_from_view')
                ->label('Nouvelle Demande')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(fn (): string => $this->getResource()::getUrl('nouvelle_demande', ['record' => $this->record])) // Needs custom route/method if not a standard page
                ->visible(fn (): bool => $this->record->valide_budget === 'oui' && $this->record->calculateBudgetRestant() > 0 && auth()->user()->can('create', \App\Models\DemandeDevis::class)),
        ];
    }

    // If you want to use Infolists for the view page (recommended for more complex views)
    // public function infolist(Infolist $infolist): Infolist
    // {
    //     return $infolist
    //         ->schema([
    //             TextEntry::make('service.nom'),
    //             TextEntry::make('date_prevue')->date('d/m/Y'),
    //             TextEntry::make('intitule'),
    //             // ... add other fields from the form schema as TextEntry or other Infolist components
    //             TextEntry::make('montant_ht_prevu')->money('eur'),
    //             TextEntry::make('montant_ttc_prevu')->money('eur'),
    //             TextEntry::make('montant_depense_reel_calculated')->label('Dépensé Réel HT')->money('eur'),
    //             TextEntry::make('budget_restant_calculated')
    //                 ->label('Budget Restant HT')
    //                 ->money('eur')
    //                 ->state(fn (BudgetLigne $record): float => $record->calculateBudgetRestant()),
    //             BadgeEntry::make('valide_budget')
    //                 ->label('Statut Validation')
    //                 ->colors([
    //                     'success' => 'oui',
    //                     'danger' => 'non',
    //                     'warning' => 'potentiellement',
    //                 ])
    //                 ->formatStateUsing(fn (string $state): string => match ($state) {
    //                     'oui' => 'Validé',
    //                     'non' => 'Non Validé',
    //                     'potentiellement' => 'Potentiel',
    //                     default => $state,
    //                 }),
    //             TextEntry::make('commentaire_service')->columnSpanFull(),
    //             TextEntry::make('commentaire_budget')->columnSpanFull()->visible(fn() => auth()->user()->hasRole('responsable-budget')),
    //         ])
    //         ->columns(3);
    // }
}

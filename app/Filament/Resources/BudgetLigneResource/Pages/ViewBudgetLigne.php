<?php

namespace App\Filament\Resources\BudgetLigneResource\Pages;

use App\Filament\Resources\BudgetLigneResource;
use App\Models\BudgetLigne;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Infolists\Components\Section;

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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informations Générales')
                    ->schema([
                        TextEntry::make('service.nom')
                            ->label('Service'),
                        TextEntry::make('date_prevue')
                            ->date('d/m/Y')
                            ->label('Date Prévue'),
                        TextEntry::make('intitule')
                            ->label('Intitulé')
                            ->columnSpanFull(),
                        TextEntry::make('nature')
                            ->badge(),
                        TextEntry::make('type_depense')
                            ->badge(),
                    ])->columns(2),

                Section::make('Budget')
                    ->schema([
                        TextEntry::make('montant_ht_prevu')
                            ->money('EUR')
                            ->label('Budget Prévu HT'),
                        TextEntry::make('montant_ttc_prevu')
                            ->money('EUR')
                            ->label('Budget Prévu TTC'),
                        TextEntry::make('montant_depense_reel_calculated')
                            ->label('Dépensé Réel HT')
                            ->money('EUR')
                            ->state(fn (BudgetLigne $record): float => $record->montant_depense_reel_calculated),
                        TextEntry::make('budget_restant_calculated')
                            ->label('Budget Restant HT')
                            ->money('EUR')
                            ->color(fn (BudgetLigne $record): string => $record->calculateBudgetRestant() < 0 ? 'danger' : 'success')
                            ->state(fn (BudgetLigne $record): float => $record->calculateBudgetRestant()),
                        BadgeEntry::make('valide_budget')
                            ->label('Statut Validation')
                            ->colors([
                                'success' => 'oui',
                                'danger' => 'non',
                                'warning' => 'potentiellement',
                            ])
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'oui' => 'Validé',
                                'non' => 'Non Validé',
                                'potentiellement' => 'Potentiel',
                                default => $state,
                            }),
                    ])->columns(3),

                Section::make('📋 Demandes de Devis Associées')
                    ->schema([
                        TextEntry::make('demandes_total')
                            ->label('Total Demandes')
                            ->state(fn (BudgetLigne $record): int => $record->demandesAssociees()->count())
                            ->badge()
                            ->color('info'),
                        TextEntry::make('demandes_en_cours')
                            ->label('En Cours')
                            ->state(fn (BudgetLigne $record): int => 
                                $record->demandesAssociees()
                                    ->whereIn('statut', ['pending', 'approved_service', 'approved_budget', 'approved_achat'])
                                    ->count()
                            )
                            ->badge()
                            ->color('warning'),
                        TextEntry::make('demandes_livrees')
                            ->label('Livrées')
                            ->state(fn (BudgetLigne $record): int => 
                                $record->demandesAssociees()->where('statut', 'delivered')->count()
                            )
                            ->badge()
                            ->color('success'),
                        TextEntry::make('demandes_rejetees')
                            ->label('Rejetées')
                            ->state(fn (BudgetLigne $record): int => 
                                $record->demandesAssociees()->where('statut', 'rejected')->count()
                            )
                            ->badge()
                            ->color('danger'),
                        TextEntry::make('montant_engage')
                            ->label('Montant Engagé')
                            ->state(fn (BudgetLigne $record): float => 
                                $record->demandesAssociees()
                                    ->whereIn('statut', ['approved_achat', 'delivered'])
                                    ->sum('prix_total_ttc')
                            )
                            ->money('EUR')
                            ->columnSpanFull(),
                    ])->columns(4),

                Section::make('Commentaires')
                    ->schema([
                        TextEntry::make('commentaire_service')
                            ->label('Commentaire Service')
                            ->columnSpanFull()
                            ->visible(fn (BudgetLigne $record): bool => !empty($record->commentaire_service)),
                        TextEntry::make('commentaire_budget')
                            ->label('Commentaire Budget')
                            ->columnSpanFull()
                            ->visible(fn (BudgetLigne $record): bool => 
                                !empty($record->commentaire_budget) && auth()->user()->hasRole('responsable-budget')
                            ),
                    ]),
            ]);
    }
}

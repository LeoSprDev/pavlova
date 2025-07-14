<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\BudgetLigne;
use Illuminate\Database\Eloquent\Builder;

class BudgetLignesTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Lignes Budget - Aperçu Critique';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BudgetLigne::query()
                    ->where('valide_budget', 'oui')
                    ->whereRaw('(montant_depense_reel / montant_ht_prevu) > 0.75') // Plus de 75% consommé
                    ->orderByRaw('(montant_depense_reel / montant_ht_prevu) DESC')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('intitule')
                    ->label('Intitulé')
                    ->limit(30)
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('service.nom')
                    ->label('Service')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('montant_ht_prevu')
                    ->label('Budget Prévu')
                    ->money('EUR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('montant_depense_reel')
                    ->label('Dépensé')
                    ->money('EUR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('montant_restant')
                    ->label('Restant')
                    ->money('EUR')
                    ->getStateUsing(function (BudgetLigne $record): float {
                        return $record->montant_ht_prevu - $record->montant_depense_reel;
                    })
                    ->color(function (BudgetLigne $record): string {
                        $restant = $record->montant_ht_prevu - $record->montant_depense_reel;
                        return $restant <= 0 ? 'danger' : ($restant < ($record->montant_ht_prevu * 0.1) ? 'warning' : 'success');
                    }),
                
                Tables\Columns\TextColumn::make('taux_consommation')
                    ->label('Taux')
                    ->getStateUsing(function (BudgetLigne $record): string {
                        $taux = $record->montant_ht_prevu > 0 ? 
                            round(($record->montant_depense_reel / $record->montant_ht_prevu) * 100, 1) : 0;
                        return $taux . '%';
                    })
                    ->badge()
                    ->color(function (BudgetLigne $record): string {
                        $taux = $record->montant_ht_prevu > 0 ? 
                            ($record->montant_depense_reel / $record->montant_ht_prevu) * 100 : 0;
                        
                        if ($taux >= 100) return 'danger';
                        if ($taux >= 90) return 'warning';
                        if ($taux >= 75) return 'info';
                        return 'success';
                    }),
                
                Tables\Columns\IconColumn::make('statut_critique')
                    ->label('Statut')
                    ->getStateUsing(function (BudgetLigne $record): bool {
                        $taux = $record->montant_ht_prevu > 0 ? 
                            ($record->montant_depense_reel / $record->montant_ht_prevu) * 100 : 0;
                        return $taux >= 95;
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->actions([
                Tables\Actions\Action::make('voir_details')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn (BudgetLigne $record): string => "/admin/budget-lignes/{$record->id}")
                    ->openUrlInNewTab(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
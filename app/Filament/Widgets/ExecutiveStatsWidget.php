<?php
namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\BudgetLigne;
use App\Models\DemandeDevis;

class ExecutiveStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $budgetTotal = BudgetLigne::where('valide_budget', 'oui')->sum('montant_ht_prevu');
        $budgetConsomme = DemandeDevis::where('statut', 'delivered_confirmed')->sum('prix_total_ttc');
        $demandesEnCours = DemandeDevis::whereIn('statut', [
            'pending_manager', 'pending_direction', 'pending_achat', 'pending_delivery'
        ])->count();

        $tauxConsommation = $budgetTotal > 0 ? ($budgetConsomme / $budgetTotal) * 100 : 0;

        return [
            Stat::make('Budget Total', number_format($budgetTotal, 2).' €')
                ->icon('heroicon-o-banknotes')
                ->color('primary'),
            Stat::make('Budget Consommé', number_format($budgetConsomme, 2).' €')
                ->icon('heroicon-o-chart-bar')
                ->color('success'),
            Stat::make('Demandes en Cours', $demandesEnCours)
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Taux Utilisation', round($tauxConsommation,1).' %')
                ->icon('heroicon-o-chart-pie')
                ->color($tauxConsommation > 90 ? 'danger' : 'info'),
        ];
    }
}

<?php
namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\BudgetLigne;
use App\Models\DemandeDevis;

class BudgetStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user->hasRole('service-demandeur')) {
            return $this->getServiceStats($user->service_id);
        }

        return $this->getGlobalStats();
    }

    private function getServiceStats($serviceId): array
    {
        $budgetTotal = BudgetLigne::where('service_id', $serviceId)
            ->where('valide_budget', 'oui')
            ->sum('montant_ht_prevu');

        $budgetConsomme = DemandeDevis::where('service_demandeur_id', $serviceId)
            ->where('statut', 'delivered')
            ->sum('prix_total_ttc');

        $demandesEnCours = DemandeDevis::where('service_demandeur_id', $serviceId)
            ->whereIn('statut', ['pending', 'approved_budget'])
            ->count();

        return [
            Stat::make('Budget Disponible', number_format($budgetTotal - $budgetConsomme, 2) . ' €')
                ->description('Budget restant')
                ->color($budgetTotal - $budgetConsomme > 0 ? 'success' : 'danger'),

            Stat::make('Demandes en Cours', $demandesEnCours)
                ->description('En attente de validation'),

            Stat::make('Taux Utilisation', round(($budgetConsomme / max($budgetTotal, 1)) * 100, 1) . '%')
                ->description('Budget utilisé')
        ];
    }

    private function getGlobalStats(): array
    {
        $budgetTotalOrg = BudgetLigne::where('valide_budget', 'oui')->sum('montant_ht_prevu');
        $demandesAValider = DemandeDevis::where('statut', 'pending')->count();
        $depassements = BudgetLigne::whereRaw('montant_depense_reel > montant_ht_prevu')->count();

        return [
            Stat::make('Budget Organisation', number_format($budgetTotalOrg, 2) . ' €')
                ->description('Budget total validé'),

            Stat::make('Demandes à Valider', $demandesAValider)
                ->description('En attente validation')
                ->color($demandesAValider > 10 ? 'danger' : 'success'),

            Stat::make('Dépassements', $depassements)
                ->description('Budgets dépassés')
                ->color($depassements > 0 ? 'danger' : 'success')
        ];
    }
}

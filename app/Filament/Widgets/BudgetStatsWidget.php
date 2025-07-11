<?php
namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\BudgetLigne;
use App\Models\DemandeDevis;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class BudgetStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected function getStats(): array
    {
        $user = Auth::user();

        if ($user->hasRole('agent-service') || $user->hasRole('manager-service')) {
            return $this->getServiceStats($user->service_id);
        }

        if ($user->hasRole('responsable-direction') || $user->hasRole('service-budget')) {
            return $this->getGlobalStats();
        }

        return $this->getAchatStats();
    }

    private function getServiceStats($serviceId): array
    {
        $budgetTotal = BudgetLigne::where('service_id', $serviceId)
            ->where('valide_budget', 'oui')
            ->sum('montant_ht_prevu');

        $budgetConsomme = DemandeDevis::where('service_demandeur_id', $serviceId)
            ->where('statut', 'delivered_confirmed')
            ->sum('prix_total_ttc');

        $budgetEngage = DemandeDevis::where('service_demandeur_id', $serviceId)
            ->whereIn('statut', ['ordered', 'pending_delivery'])
            ->sum('prix_total_ttc');

        $demandesEnCours = DemandeDevis::where('service_demandeur_id', $serviceId)
            ->whereIn('statut', ['pending_manager', 'pending_direction', 'pending_achat'])
            ->count();

        $budgetDisponible = $budgetTotal - $budgetConsomme - $budgetEngage;
        $tauxUtilisation = $budgetTotal > 0 ? (($budgetConsomme + $budgetEngage) / $budgetTotal) * 100 : 0;

        return [
            Stat::make('Budget Disponible', number_format($budgetDisponible, 2) . ' €')
                ->description('Budget restant utilisable')
                ->color($budgetDisponible > 0 ? 'success' : 'danger')
                ->chart([7,3,4,5,6,3,5,3])
                ->icon('heroicon-o-banknotes'),

            Stat::make('Budget Engagé', number_format($budgetEngage, 2) . ' €')
                ->description('Commandes en cours')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Demandes en Cours', $demandesEnCours)
                ->description('En attente de validation')
                ->color($demandesEnCours > 5 ? 'warning' : 'success')
                ->icon('heroicon-o-document-text'),

            Stat::make('Taux Utilisation', round($tauxUtilisation, 1) . '%')
                ->description('Budget utilisé + engagé')
                ->color($tauxUtilisation > 90 ? 'danger' : ($tauxUtilisation > 75 ? 'warning' : 'success'))
                ->chart([3,5,7,8,9,10,8])
                ->icon('heroicon-o-chart-pie'),
        ];
    }

    private function getGlobalStats(): array
    {
        $budgetTotalOrg = BudgetLigne::where('valide_budget', 'oui')->sum('montant_ht_prevu');
        $demandesAValider = DemandeDevis::whereIn('statut', ['pending_manager', 'pending_direction'])->count();
        $depassements = BudgetLigne::whereRaw('montant_depense_reel > montant_ht_prevu')->count();
        $servicesActifs = Service::where('actif', true)->count();

        return [
            Stat::make('Budget Organisation', number_format($budgetTotalOrg, 2) . ' €')
                ->description('Budget total validé')
                ->icon('heroicon-o-building-office-2'),

            Stat::make('Demandes à Valider', $demandesAValider)
                ->description('Nécessitent votre attention')
                ->color($demandesAValider > 10 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make('Services Actifs', $servicesActifs)
                ->description('Services avec budget')
                ->color('success')
                ->icon('heroicon-o-user-group'),

            Stat::make('Dépassements', $depassements)
                ->description('Budgets dépassés')
                ->color($depassements > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-circle'),
        ];
    }

    private function getAchatStats(): array
    {
        $commandesEnCours = \App\Models\Commande::whereIn('statut', ['commande', 'en_cours'])->count();
        $livraisons = \App\Models\Commande::where('statut', 'livree')->count();
        $retards = \App\Models\Commande::where('date_livraison_prevue', '<', now())
                                     ->whereNotIn('statut', ['livree', 'annulee'])
                                     ->count();

        return [
            Stat::make('Commandes en Cours', $commandesEnCours)
                ->description('À suivre')
                ->color('info')
                ->icon('heroicon-o-shopping-cart'),

            Stat::make('Livraisons Reçues', $livraisons)
                ->description('Ce mois')
                ->color('success')
                ->icon('heroicon-o-truck'),

            Stat::make('Retards', $retards)
                ->description('Dépassement délai')
                ->color($retards > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-clock'),
        ];
    }
}

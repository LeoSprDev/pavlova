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
        try {
            $stats = $this->calculateStats();

            return count($stats) > 0 ? $stats : $this->getDefaultStats();
        } catch (\Exception $e) {
            \Log::warning('BudgetStatsWidget Error: ' . $e->getMessage());

            return $this->getDefaultStats();
        }
    }

    private function getDefaultStats(): array
    {
        return [
            Stat::make('Total Budgets', '0')
                ->description('Aucune donnée disponible')
                ->color('gray')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Demandes', '0')
                ->description('Aucune demande créée')
                ->color('gray')
                ->icon('heroicon-o-document-text'),
            Stat::make('Budget Disponible', '0 €')
                ->description('Créer des lignes budgétaires')
                ->color('gray')
                ->icon('heroicon-o-calculator'),
            Stat::make('Taux Consommation', '0%')
                ->description('Pas encore de consommation')
                ->color('gray')
                ->icon('heroicon-o-chart-pie'),
        ];
    }

    private function calculateStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        return $user->hasAnyRole(['responsable-service', 'agent-service'])
            ? $this->getServiceStats()
            : $this->getGlobalStats();
    }

    private function getServiceStats(?int $serviceId = null): array
    {
        try {
            $serviceId = $serviceId ?? optional(Auth::user())->service_id;
            if (! $serviceId) {
                return $this->getDefaultStats();
            }

            $budgets = BudgetLigne::where('service_id', $serviceId)
                ->where('valide_budget', 'oui');
            $budgetTotal = $budgets->sum('montant_ht_prevu') ?: 0;

            $budgetConsomme = DemandeDevis::where('service_demandeur_id', $serviceId)
                ->where('statut', 'delivered')
                ->sum('prix_total_ttc') ?: 0;

            $budgetEngage = DemandeDevis::where('service_demandeur_id', $serviceId)
                ->whereIn('statut', ['ordered', 'approved_achat'])
                ->sum('prix_total_ttc') ?: 0;

            $demandesEnCours = DemandeDevis::where('service_demandeur_id', $serviceId)
                ->whereIn('statut', ['pending', 'approved_service', 'approved_budget'])
                ->count();

            $budgetDisponible = $budgetTotal - $budgetConsomme - $budgetEngage;
            $tauxUtilisation = $budgetTotal > 0 ? (($budgetConsomme + $budgetEngage) / $budgetTotal) * 100 : 0;
        } catch (\Exception $e) {
            \Log::warning('Error calculating service stats: ' . $e->getMessage());
            return $this->getDefaultStats();
        }

        return [
            Stat::make('Budget Disponible', number_format($budgetDisponible, 2) . ' €')
                ->description('Budget restant utilisable')
                ->color($budgetDisponible > 0 ? 'success' : 'danger')
                ->chart($this->getTendanceConsommation($serviceId))
                ->icon('heroicon-o-banknotes')
                ->extraAttributes([
                    'aria-label' => "Budget disponible: {$budgetDisponible} euros",
                    'role' => 'status',
                    'aria-live' => 'polite',
                ]),

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
                ->chart($this->getTendanceConsommation($serviceId))
                ->icon('heroicon-o-chart-pie'),

            Stat::make('Délai Moyen', $this->getDelaiMoyenValidation($serviceId) . ' jours')
                ->description('Temps moyen validation')
                ->color('info')
                ->icon('heroicon-o-clock'),

            Stat::make('Taux Approbation', $this->getTauxApprobation($serviceId) . '%')
                ->description('% demandes approuvées')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
        ];
    }

    /**
     * Retourne la tendance de consommation sur les 12 derniers mois pour un service.
     */
    private function getTendanceConsommation(int $serviceId): array
    {
        try {
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $consommation = DemandeDevis::where('service_demandeur_id', $serviceId)
                    ->where('statut', 'delivered')
                    ->whereYear('date_finalisation', $date->year)
                    ->whereMonth('date_finalisation', $date->month)
                    ->sum('prix_fournisseur_final') ?: 0;
                $data[] = $consommation;
            }
            return $data;
        } catch (\Exception $e) {
            \Log::warning('Error calculating tendance consommation: ' . $e->getMessage());
            return array_fill(0, 12, 0);
        }
    }

    private function getGlobalStats(): array
    {
        try {
            $budgetTotalOrg = BudgetLigne::where('valide_budget', 'oui')->sum('montant_ht_prevu') ?: 0;
            $demandesAValider = DemandeDevis::whereIn('statut', ['pending', 'approved_service'])->count();
            $demandesRealisees = DemandeDevis::where('statut', 'delivered')->count();
            $depassements = BudgetLigne::whereRaw('montant_depense_reel > montant_ht_prevu')->count();
            $servicesActifs = Service::count();
        } catch (\Exception $e) {
            \Log::warning('Error calculating global stats: ' . $e->getMessage());
            return $this->getDefaultStats();
        }

        return [
            Stat::make('Budget Organisation', number_format($budgetTotalOrg, 2) . ' €')
                ->description('Budget total validé')
                ->icon('heroicon-o-building-office-2'),

            Stat::make('Demandes à Valider', $demandesAValider)
                ->description('Nécessitent votre attention')
                ->color($demandesAValider > 10 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make('Demandes Réalisées', $demandesRealisees)
                ->description('Livrées et terminées')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

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

    private function getDelaiMoyenValidation($serviceId): int
    {
        try {
            return DemandeDevis::where('service_demandeur_id', $serviceId)
                ->where('statut', 'delivered')
                ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
                ->value('avg_days') ?? 0;
        } catch (\Exception $e) {
            \Log::warning('Error calculating delai moyen: ' . $e->getMessage());
            return 0;
        }
    }

    private function getTauxApprobation($serviceId): int
    {
        try {
            $total = DemandeDevis::where('service_demandeur_id', $serviceId)->count();
            $approuves = DemandeDevis::where('service_demandeur_id', $serviceId)
                ->whereIn('statut', ['delivered', 'ordered'])
                ->count();

            return $total > 0 ? round(($approuves / $total) * 100) : 0;
        } catch (\Exception $e) {
            \Log::warning('Error calculating taux approbation: ' . $e->getMessage());
            return 0;
        }
    }
}

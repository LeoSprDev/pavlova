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

        // Adapté pour les nouveaux rôles agent-service et responsable-service
        if ($user->hasAnyRole(['agent-service', 'responsable-service']) && $user->service_id) {
            return $this->getServiceStats($user->service_id);
        }

        // Les autres rôles (responsable-budget, service-achat, administrateur) verront les stats globales.
        return $this->getGlobalStats();
    }

    private function getServiceStats($serviceId): array
    {
        $budgetTotal = BudgetLigne::where('service_id', $serviceId)
            ->where('valide_budget', 'oui')
            ->sum('montant_ht_prevu');

        // Budget consommé reste basé sur les demandes livrées
        $budgetConsomme = DemandeDevis::where('service_demandeur_id', $serviceId)
            ->where('statut', 'delivered') // 'delivered' est l'état final après réception.
            ->sum('prix_total_ttc');

        // Demandes en cours pour ce service (non finalisées et non rejetées/annulées)
        $demandesEnCours = DemandeDevis::where('service_demandeur_id', $serviceId)
            ->whereNotIn('statut', ['delivered', 'rejected', 'cancelled']) // Statuts finaux à exclure
            ->count();

        return [
            Stat::make('Budget Disponible Service', number_format($budgetTotal - $budgetConsomme, 2) . ' €')
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

        // Nouvelles demandes soumises, en attente de la première validation (par Responsable Service)
        $nouvellesDemandes = DemandeDevis::where('current_step', 'validation-responsable-service')
                                      // Le statut 'pending' est l'état initial avant toute action du workflow par le package.
                                      // Si le package met à jour current_step dès la soumission, se baser sur current_step est suffisant.
                                      // ->where('statut', 'pending')
                                      ->count();

        // La stat Dépassements est commentée car montant_depense_reel a été retiré des fillables
        // et sa logique de calcul direct en SQL (whereRaw) n'est plus simple.
        // $depassements = BudgetLigne::enDepassement()->count(); // Supposant un scope 'enDepassement' qui calcule correctement.

        $stats = [
            Stat::make('Budget Total Organisation', number_format($budgetTotalOrg, 2) . ' €')
                ->description('Budget total validé (tous services)'),

            Stat::make('Nouvelles Demandes Soumises', $nouvellesDemandes)
                ->description('En attente validation Responsable Service')
                ->color($nouvellesDemandes > 5 ? 'warning' : 'success'),
        ];

        // Pour réactiver les dépassements, il faudrait une méthode fiable :
        // $lignesEnDepassement = 0;
        // $lignes = BudgetLigne::where('valide_budget', 'oui')->get();
        // foreach ($lignes as $ligne) {
        //     if ($ligne->isDepassementBudget()) { // isDepassementBudget utilise calculateBudgetRestant()
        //         $lignesEnDepassement++;
        //     }
        // }
        // if ($lignesEnDepassement > 0) {
        //     $stats[] = Stat::make('Lignes en Dépassement', $lignesEnDepassement)
        //         ->description('Constaté sur lignes validées')
        //         ->color('danger');
        // }

        return $stats;
    }
}

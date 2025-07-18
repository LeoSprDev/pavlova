<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\{BudgetLigne, Service, DemandeDevis};
use Illuminate\Support\Facades\Auth;

class BudgetAlertsWidget extends Widget
{
    protected static string $view = 'filament.widgets.budget-alerts-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;
    
    // SÉCURITÉ : Seuls les responsables budget/direction
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            'responsable-budget', 'responsable-direction', 'administrateur', 'responsable-service'
        ]) ?? false;
    }
    
    public function getAlertsData(): array
    {
        return [
            'critiques' => $this->getCriticalAlerts(),
            'avertissements' => $this->getWarningAlerts(),
            'info' => $this->getInfoAlerts(),
            'stats' => $this->getGlobalStats()
        ];
    }
    
    private function getCriticalAlerts(): array
    {
        $alerts = [];
        
        // Services en dépassement (> 100%)
        $servicesDepassement = Service::whereHas('budgetLignes', function($query) {
            $query->whereRaw('montant_depense_reel + montant_engage > montant_ht_prevu');
        })->with('budgetLignes')->get();
        
        foreach ($servicesDepassement as $service) {
            $depassement = $service->budgetLignes->sum(function($budget) {
                return ($budget->montant_depense_reel + $budget->montant_engage) - $budget->montant_ht_prevu;
            });
                           
            if ($depassement > 0) {
                $alerts[] = [
                    'type' => 'critique',
                    'service' => $service->nom,
                    'message' => "Dépassement de " . number_format($depassement, 0) . "€",
                    'action' => 'Réallocation urgente requise',
                    'url' => '/admin/budget-lignes?tableFilters[service_id][value]=' . $service->id
                ];
            }
        }
        
        return $alerts;
    }
    
    private function getWarningAlerts(): array
    {
        $alerts = [];
        
        // Budgets proches saturation (80-100%)
        $budgetsRisque = BudgetLigne::with('service')->get()->filter(function($budget) {
            $taux = $budget->getTauxUtilisation();
            return $taux >= 80 && $taux <= 100;
        });
        
        foreach ($budgetsRisque as $budget) {
            $tauxUtilisation = $budget->getTauxUtilisation();
            $alerts[] = [
                'type' => 'warning',
                'service' => $budget->service->nom,
                'ligne' => $budget->intitule,
                'taux' => round($tauxUtilisation, 1),
                'message' => "Budget à " . round($tauxUtilisation, 1) . "% - Attention",
                'action' => 'Surveiller nouvelles demandes'
            ];
        }
        
        return $alerts;
    }
    
    private function getInfoAlerts(): array
    {
        $alerts = [];
        
        // Demandes bloquées > 3 jours
        $demandesBloquees = DemandeDevis::whereIn('statut', [
            'pending_manager', 'pending_direction', 'pending_achat'
        ])->where('updated_at', '<', now()->subDays(3))
          ->with(['serviceDemandeur'])
          ->get();
          
        foreach ($demandesBloquees as $demande) {
            $alerts[] = [
                'type' => 'info',
                'service' => $demande->serviceDemandeur->nom,
                'demande' => $demande->denomination,
                'statut' => $demande->statut,
                'jours' => $demande->updated_at->diffInDays(now()),
                'message' => "Bloquée depuis " . $demande->updated_at->diffInDays(now()) . " jours",
                'action' => 'Relancer approbateur'
            ];
        }
        
        return $alerts;
    }
    
    private function getGlobalStats(): array
    {
        return [
            'services_ok' => Service::whereHas('budgetLignes', function($query) {
                $query->whereRaw('((montant_depense_reel + montant_engage) / montant_ht_prevu * 100) < 80');
            })->count(),
            'services_attention' => Service::whereHas('budgetLignes', function($query) {
                $query->whereRaw('((montant_depense_reel + montant_engage) / montant_ht_prevu * 100) BETWEEN 80 AND 100');
            })->count(),
            'services_depassement' => Service::whereHas('budgetLignes', function($query) {
                $query->whereRaw('montant_depense_reel + montant_engage > montant_ht_prevu');
            })->count()
        ];
    }
}
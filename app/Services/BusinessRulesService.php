<?php
namespace App\Services;

use App\Models\{BusinessRule, DemandeDevis, Service, User};

class BusinessRulesService
{
    private array $defaultRules = [
        'seuils_approbation' => [
            'manager' => 50,
            'direction' => 3000,
            'achat' => 50000,
        ],
        'delais_standard' => [
            'manager' => 2,
            'direction' => 3,
            'achat' => 5,
        ],
        'budget_limits' => [
            'warning_threshold' => 85,
            'critical_threshold' => 95,
        ],
    ];

    private function getRule(string $key): mixed
    {
        return BusinessRule::getValue($key) ?? data_get($this->defaultRules, $key);
    }

    public function validateSeuilMontant(float $montant, Service $service): bool
    {
        $seuils = $this->getRule('seuils_approbation');
        if (!$seuils) {
            $seuils = $this->defaultRules['seuils_approbation'];
        }
        return $montant < $seuils['achat'];
    }

    public function canApprove(User $user, DemandeDevis $demande): bool
    {
        $montant = $demande->prix_total_ttc;
        $seuils = $this->getRule('seuils_approbation');
        if ($user->hasRole('manager-service') && $montant < $seuils['manager']) {
            return true;
        }
        if ($user->hasRole('responsable-direction') && $montant < $seuils['direction']) {
            return true;
        }
        if ($user->hasRole('service-achat') && $montant < $seuils['achat']) {
            return true;
        }
        return false;
    }

    public function shouldEscalate(DemandeDevis $demande): bool
    {
        $delais = $this->getRule('delais_standard');
        $max = match($demande->statut) {
            'pending_manager' => $delais['manager'],
            'pending_direction' => $delais['direction'],
            'pending_achat' => $delais['achat'],
            default => 5,
        };
        return now()->diffInDays($demande->updated_at) > $max;
    }
}

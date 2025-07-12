<?php
namespace App\Services;

use App\Models\{DemandeDevis, Service};
use App\Rules\{BudgetCoherenceRule, FournisseurWarningRule, SeuilApprobationRule, DelaiCoherenceRule, DoublonDemandeRule};

class WorkflowValidationService
{
    public function validateDemandeCreation(DemandeDevis $demande): array
    {
        $errors = [];
        $warnings = [];

        if (! (new BudgetCoherenceRule())->passes('demande', $demande)) {
            $errors[] = 'Incohérence entre demande et ligne budgétaire';
        }

        if (! (new FournisseurWarningRule())->passes('demande', $demande)) {
            $warnings[] = 'Fournisseur non-référencé dans le système - Vérification recommandée';
        }

        if (! (new SeuilApprobationRule())->passes('demande', $demande)) {
            $errors[] = 'Montant nécessite validation supérieure';
        }

        if (! (new DelaiCoherenceRule())->passes('demande', $demande)) {
            $errors[] = 'Date de besoin incohérente avec délais standard';
        }

        if ((new DoublonDemandeRule())->passes('demande', $demande)) {
            $warnings[] = 'Demande similaire récente détectée - Vérification recommandée';
        }

        if ($this->isBudgetServiceCritique($demande->serviceDemandeur)) {
            $warnings[] = 'Budget service > 95% consommé - Attention dépassement possible';
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function isBudgetServiceCritique(Service $service): bool
    {
        $totalBudgets = $service->budgetLignes()->sum('montant_ht_prevu');
        $totalConsomme = $service->budgetLignes()->sum('montant_depense_reel');
        $taux = $totalBudgets > 0 ? ($totalConsomme / $totalBudgets) * 100 : 0;
        return $taux > 95;
    }
}

<?php
namespace App\Rules;

use App\Models\DemandeDevis;
use Illuminate\Contracts\Validation\Rule;

class BudgetCoherenceRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (! $value instanceof DemandeDevis) {
            return true;
        }

        $ligne = $value->budgetLigne;
        if (! $ligne) {
            return false;
        }

        $montantMax = $ligne->montant_ht_prevu * 0.3;
        return $value->prix_total_ttc <= $montantMax && $ligne->service_id === $value->service_demandeur_id;
    }

    public function message(): string
    {
        return 'La demande dépasse la cohérence budgétaire.';
    }
}

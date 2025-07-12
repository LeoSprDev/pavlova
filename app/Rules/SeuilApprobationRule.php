<?php
namespace App\Rules;

use App\Models\DemandeDevis;
use Illuminate\Contracts\Validation\Rule;
use App\Services\BusinessRulesService;

class SeuilApprobationRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (! $value instanceof DemandeDevis) {
            return true;
        }

        return app(BusinessRulesService::class)->validateSeuilMontant($value->prix_total_ttc, $value->serviceDemandeur);
    }

    public function message(): string
    {
        return 'Montant dépasse les seuils d\'approbation';
    }
}

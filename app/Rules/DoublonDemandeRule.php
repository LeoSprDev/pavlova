<?php
namespace App\Rules;

use App\Models\DemandeDevis;
use Illuminate\Contracts\Validation\Rule;

class DoublonDemandeRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (! $value instanceof DemandeDevis) {
            return false;
        }

        return DemandeDevis::where('service_demandeur_id', $value->service_demandeur_id)
            ->where('denomination', 'LIKE', '%' . $value->denomination . '%')
            ->where('created_at', '>', now()->subDays(30))
            ->where('id', '!=', $value->id ?? 0)
            ->doesntExist();
    }

    public function message(): string
    {
        return 'Une demande similaire existe déjà récemment';
    }
}

<?php
namespace App\Rules;

use App\Models\Fournisseur;
use App\Models\DemandeDevis;
use Illuminate\Contracts\Validation\Rule;

class FournisseurWarningRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (! $value instanceof DemandeDevis) {
            return true;
        }

        return Fournisseur::where('nom', $value->fournisseur_propose)
            ->orWhere('nom_commercial', $value->fournisseur_propose)
            ->exists();
    }

    public function message(): string
    {
        return 'Fournisseur non référencé';
    }
}

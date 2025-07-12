<?php
namespace App\Rules;

use App\Models\DemandeDevis;
use Illuminate\Contracts\Validation\Rule;

class DelaiCoherenceRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (! $value instanceof DemandeDevis) {
            return true;
        }

        return $value->date_besoin && $value->date_besoin->isAfter(now()->subDay());
    }

    public function message(): string
    {
        return 'La date de besoin ne peut pas être antérieure à hier';
    }
}

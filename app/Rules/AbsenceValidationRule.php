<?php
namespace App\Rules;

use App\Models\{User, DemandeDevis};
use Illuminate\Contracts\Validation\Rule;
use App\Services\ApprovalDelegationService;

class AbsenceValidationRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (! ($value instanceof User)) {
            return true;
        }

        $service = app(ApprovalDelegationService::class);
        return ! $service->getCurrentDelegate($value) instanceof User;
    }

    public function message(): string
    {
        return 'Cet utilisateur est actuellement absent et a délégué ses approbations';
    }
}

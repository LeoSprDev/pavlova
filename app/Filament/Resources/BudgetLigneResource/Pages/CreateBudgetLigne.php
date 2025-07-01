<?php

namespace App\Filament\Resources\BudgetLigneResource\Pages;

use App\Filament\Resources\BudgetLigneResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CreateBudgetLigne extends CreateRecord
{
    protected static string $resource = BudgetLigneResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // If service_demandeur, ensure their service_id is set, overriding any other value.
        if ($currentUser->hasRole('service-demandeur') && $currentUser->service_id) {
            $data['service_id'] = $currentUser->service_id;
        }

        // Calculate TTC from HT if not already handled by livewire updates (e.g. if JS is disabled or direct submission)
        if (isset($data['montant_ht_prevu']) && is_numeric($data['montant_ht_prevu']) && !isset($data['montant_ttc_prevu'])) {
            $data['montant_ttc_prevu'] = round((float)$data['montant_ht_prevu'] * 1.20, 2);
        }

        // Default 'valide_budget' to 'non' if not submitted by RB
        if (!$currentUser->hasRole('responsable-budget') && !isset($data['valide_budget'])) {
            $data['valide_budget'] = 'non';
        }


        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Ligne budgétaire créée';
    }
}

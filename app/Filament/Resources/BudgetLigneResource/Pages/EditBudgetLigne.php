<?php

namespace App\Filament\Resources\BudgetLigneResource\Pages;

use App\Filament\Resources\BudgetLigneResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\BudgetLigne;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class EditBudgetLigne extends EditRecord
{
    protected static string $resource = BudgetLigneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ViewAction::make(), // Add view action
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Calculate TTC from HT if not already handled by livewire updates
        if (isset($data['montant_ht_prevu']) && is_numeric($data['montant_ht_prevu'])) {
            $currentTTC = isset($data['montant_ttc_prevu']) && is_numeric($data['montant_ttc_prevu']) ? (float)$data['montant_ttc_prevu'] : 0;
            $calculatedTTC = round((float)$data['montant_ht_prevu'] * 1.20, 2);
            // Only update TTC if it's different from the calculated one, or if it's not set
            // This avoids issues if TTC was manually set to something specific (though it's disabled in form)
            if ($currentTTC !== $calculatedTTC || !isset($data['montant_ttc_prevu'])) {
                 $data['montant_ttc_prevu'] = $calculatedTTC;
            }
        }

        /** @var User $currentUser */
        $currentUser = Auth::user();
        /** @var BudgetLigne $record */
        $record = $this->getRecord();

        // Prevent service_demandeur from changing service_id after creation or to a different one
        if ($currentUser->hasRole('service-demandeur')) {
            if ($record->service_id !== $currentUser->service_id) {
                // This should ideally be caught by policy, but as a safeguard:
                throw new \Illuminate\Auth\Access\AuthorizationException("Vous ne pouvez pas modifier le service d'affectation.");
            }
            $data['service_id'] = $record->service_id; // Ensure it's not changed

            // Prevent service_demandeur from changing validation status or budget comments
            if (isset($data['valide_budget'])) unset($data['valide_budget']);
            if (isset($data['commentaire_budget'])) unset($data['commentaire_budget']);
        }


        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Ligne budgétaire modifiée';
    }
}

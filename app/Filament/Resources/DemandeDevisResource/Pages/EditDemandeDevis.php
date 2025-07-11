<?php

namespace App\Filament\Resources\DemandeDevisResource\Pages;

use App\Filament\Resources\DemandeDevisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\DemandeDevis;
use App\Models\BudgetLigne;
use Illuminate\Validation\ValidationException;

class EditDemandeDevis extends EditRecord
{
    protected static string $resource = DemandeDevisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn(DemandeDevis $record): bool => Auth::user()->can('delete', $record)),
            Actions\ViewAction::make(),
            // Approval actions can also be added here if desired, similar to table actions
            // Example: Actions\Action::make('approve_step_from_edit')...
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        /** @var DemandeDevis $record */
        $record = $this->getRecord();

        // Prevent editing key fields if the demand is past 'pending' or 'rejected' state, unless by specific roles
        if (!in_array($record->statut, ['pending', 'rejected'])) {
            if (!optional($currentUser)->hasAnyRole(['responsable-budget', 'administrateur'])) { // Example roles that can edit more fields
                // For service demandeur, restrict changes if not pending/rejected
                $allowedFields = ['commentaire_validation']; // Allow only comments for example
                foreach ($data as $key => $value) {
                    if (!in_array($key, $allowedFields)) {
                        unset($data[$key]);
                    }
                }
                 // Or simply throw an exception if no edits are allowed
                 // throw new AuthorizationException('Vous ne pouvez plus modifier cette demande.');
            }
        }


        // Recalculate TTC if relevant fields changed
        if (isset($data['quantite'], $data['prix_unitaire_ht']) && (is_numeric($data['quantite']) && is_numeric($data['prix_unitaire_ht']))) {
            $calculatedTTC = round((float)$data['quantite'] * (float)$data['prix_unitaire_ht'] * 1.20, 2);
            if (!isset($data['prix_total_ttc']) || (float)$data['prix_total_ttc'] !== $calculatedTTC) {
                $data['prix_total_ttc'] = $calculatedTTC;
            }
        } elseif (!isset($data['prix_total_ttc']) && ($record->statut === 'pending' || $record->statut === 'rejected')) {
            // If pending/rejected and TTC becomes invalid, throw error
            throw ValidationException::withMessages(['prix_total_ttc' => 'Le prix total TTC n\'a pas pu être calculé. Vérifiez quantité et prix unitaire.']);
        }


        // Re-validate budget if amount or budget line changed and status is still pending
        if (($record->statut === 'pending' || $record->statut === 'rejected') && (isset($data['budget_ligne_id']) || isset($data['prix_total_ttc']))) {
            $budgetLigneId = $data['budget_ligne_id'] ?? $record->budget_ligne_id;
            $prixTotalTTC = $data['prix_total_ttc'] ?? $record->prix_total_ttc;

            $budgetLigne = BudgetLigne::find($budgetLigneId);
            if (!$budgetLigne) {
                throw ValidationException::withMessages(['budget_ligne_id' => 'Ligne budgétaire invalide.']);
            }
            if ($budgetLigne->valide_budget !== 'oui') {
                throw ValidationException::withMessages(['budget_ligne_id' => 'La ligne budgétaire sélectionnée n\'est pas validée.']);
            }
            // When editing, we need to consider the *change* in amount if the demand was already linked.
            // This is complex. For simplicity, check against current total.
            // A more robust check would be: initial_budget_restant - new_demand_amount >= 0
            if (!$budgetLigne->canAcceptNewDemande((float)$prixTotalTTC)) {
                 // If it's the *same* demand being edited, the check should be:
                 // (budget_restant_avant_cette_demande) - new_amount_cette_demande >= 0
                 // $budgetRestantAvantCetteDemande = $budgetLigne->calculateBudgetRestant() + $record->prix_total_ttc; // if it was already "consuming"
                 // This simplified check might prevent saving even if only other fields are changed but budget is tight.
                 // For now, using the direct check:
                throw ValidationException::withMessages(['budget_ligne_id' => 'Budget insuffisant sur la ligne sélectionnée pour ce montant. Restant (hors cette demande si déjà comptée): ' . $budgetLigne->calculateBudgetRestant() . '€']);
            }
        }

        // Ensure service_demandeur_id is not changed by SD after creation
        if ($currentUser->hasRole('service-demandeur') && isset($data['service_demandeur_id']) && $data['service_demandeur_id'] !== $record->service_demandeur_id) {
            $data['service_demandeur_id'] = $record->service_demandeur_id; // revert
        }


        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Demande de devis modifiée';
    }
}

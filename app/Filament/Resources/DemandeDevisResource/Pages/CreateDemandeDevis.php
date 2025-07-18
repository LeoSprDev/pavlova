<?php

namespace App\Filament\Resources\DemandeDevisResource\Pages;

use App\Filament\Resources\DemandeDevisResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\BudgetLigne;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class CreateDemandeDevis extends CreateRecord
{
    protected static string $resource = DemandeDevisResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        // Log pour debugging
        \Log::info('Création demande devis par: ' . $currentUser->email, [
            'user_id' => $currentUser->id,
            'user_roles' => $currentUser->roles->pluck('name')->toArray(),
            'service_id' => $currentUser->service_id,
            'form_data' => $data
        ]);

        if ($currentUser->hasAnyRole(['service-demandeur', 'agent-service', 'responsable-service']) && $currentUser->service_id) {
            $data['service_demandeur_id'] = $currentUser->service_id;
        } elseif (!isset($data['service_demandeur_id'])) {
            // This should be caught by validation, but as a fallback
            throw ValidationException::withMessages(['service_demandeur_id' => 'Le service demandeur est requis.']);
        }

        // Calculate TTC from HT and quantity if not already handled by livewire
        if (isset($data['quantite'], $data['prix_unitaire_ht']) && is_numeric($data['quantite']) && is_numeric($data['prix_unitaire_ht'])) {
             if (!isset($data['prix_total_ttc']) || !is_numeric($data['prix_total_ttc']) ||
                 (float)$data['prix_total_ttc'] !== round((float)$data['quantite'] * (float)$data['prix_unitaire_ht'] * 1.20, 2) ) {
                $data['prix_total_ttc'] = round((float)$data['quantite'] * (float)$data['prix_unitaire_ht'] * 1.20, 2);
             }
        } else if (!isset($data['prix_total_ttc'])) {
             throw ValidationException::withMessages(['prix_total_ttc' => 'Le prix total TTC n\'a pas pu être calculé. Vérifiez quantité et prix unitaire.']);
        }


        // Validate budget availability
        if (isset($data['budget_ligne_id'], $data['prix_total_ttc'])) {
            $budgetLigne = BudgetLigne::find($data['budget_ligne_id']);
            if (!$budgetLigne) {
                throw ValidationException::withMessages(['budget_ligne_id' => 'Ligne budgétaire invalide.']);
            }
            if ($budgetLigne->valide_budget !== 'oui') {
                throw ValidationException::withMessages(['budget_ligne_id' => 'La ligne budgétaire sélectionnée n\'est pas validée.']);
            }
            // Plus de blocage pour dépassement budget - on avertit seulement les valideurs
            // if (!$budgetLigne->canAcceptNewDemande((float)$data['prix_total_ttc'])) {
            //     throw ValidationException::withMessages(['budget_ligne_id' => 'Budget insuffisant sur la ligne sélectionnée. Restant: ' . $budgetLigne->calculateBudgetRestant() . '€']);
            // }
        } else {
            throw ValidationException::withMessages(['budget_ligne_id' => 'Ligne budgétaire et montant total sont requis pour la validation du budget.']);
        }

        $data['statut'] = 'pending'; // Initial status
        $data['created_by'] = $currentUser->id; // Ensure created_by is set

        // Handle file uploads from temporary state to the model's media collections
        // This is usually handled automatically by Filament if collection name matches form field name.
        // If names are different (e.g. 'devis_fournisseur_upload' vs 'devis_fournisseur'), manual handling might be needed after record creation.
        // For now, assuming Filament handles it if collection names match.

        return $data;
    }

    protected function afterCreate(): void
    {
        // If 'devis_fournisseur_upload' was the field name for a FileUpload
        // and 'devis_fournisseur' is the collection name, Filament should handle it.
        // If not, you might need to retrieve the uploaded file path from $this->data and add it to media library:
        // $record = $this->getRecord();
        // if (isset($this->data['devis_fournisseur_upload'])) {
        //     $record->addMedia(storage_path('app/' . $this->data['devis_fournisseur_upload']))
        //            ->toMediaCollection('devis_fournisseur');
        // }
        // Similar for 'documents_complementaires_upload' if it's an array of paths.
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Demande de devis créée';
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Log::error('Erreur de validation lors de la création d\'une demande devis', [
            'user_id' => Auth::id(),
            'errors' => $exception->errors(),
            'form_data' => $this->data ?? []
        ]);
        
        Notification::make()
            ->title('Erreur lors de la création')
            ->body('Des erreurs de validation ont été détectées. Vérifiez les champs requis.')
            ->danger()
            ->send();
            
        parent::onValidationError($exception);
    }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création d\'une demande devis', [
                'user_id' => Auth::id(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'form_data' => $this->data ?? []
            ]);
            
            Notification::make()
                ->title('Erreur technique')
                ->body('Une erreur technique s\'est produite: ' . $e->getMessage())
                ->danger()
                ->send();
                
            throw $e;
        }
    }
}

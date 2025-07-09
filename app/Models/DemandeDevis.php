<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ProcessApproval as Approval; // Alias for clarity
use App\Traits\Approvable;
use App\Contracts\Approvable as ApprovableContract; // Corrected Contract name
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DemandeDevis extends Model implements ApprovableContract, HasMedia
{
    use HasFactory, Approvable, InteractsWithMedia; // Removed HasApprovals as Approvable includes it

    protected $fillable = [
        'user_id', // Added user_id
        'service_demandeur_id',
        'budget_ligne_id',
        'denomination',
        'reference_produit',
        'description',
        'quantite',
        'prix_unitaire_ht',
        'prix_total_ttc',
        'fournisseur_propose',
        'justification_besoin',
        'urgence',
        'date_besoin',
        'statut',
        'commentaire_validation',
        'date_validation_budget',
        'date_validation_achat',
        // Process Approval columns (if any not handled by trait directly)
        'current_step',
    ];

    protected $casts = [
        'date_besoin' => 'date',
        'date_validation_budget' => 'datetime',
        'date_validation_achat' => 'datetime',
        'prix_unitaire_ht' => 'decimal:2',
        'prix_total_ttc' => 'decimal:2',
        'quantite' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function serviceDemandeur(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_demandeur_id');
    }

    public function budgetLigne(): BelongsTo
    {
        return $this->belongsTo(BudgetLigne::class);
    }

    public function commande(): HasOne
    {
        return $this->hasOne(Commande::class);
    }

    /**
     * Get all approval history for this model.
     * This method name 'approvals' is usually defined by the Approvable trait.
     * If a custom name is needed, ensure it doesn't conflict.
     * The prompt uses 'approvalsHistory'.
     */
    public function approvalsHistory(): MorphMany
    {
        // The Approvable trait should provide an 'approvals()' method.
        // If you need a differently named relationship or specific filtering:
        return $this->morphMany(Approval::class, 'approvable')
                    ->orderBy('created_at');
    }

    // Configuration for Laravel Process Approval

    /**
     * Get the name of the approval workflow.
     * This is used by the Approvable trait.
     * The prompt uses getApprovalFlowAttribute() but the trait expects this method name.
     */
    public function approvalWorkflow(): string
    {
        return 'demande-devis-workflow';
    }
    // Compatibility with prompt's naming if needed, but trait uses approvalWorkflow()
    public function getApprovalFlowAttribute(): string
    {
        return $this->approvalWorkflow();
    }


    /**
     * Get the steps for the approval workflow.
     * This is used by the Approvable trait.
     */
    public function approvalSteps(): array
    {
        return [
            'validation-responsable-service' => [
                'label' => 'Validation Responsable Service',
                'role' => 'responsable-service',
                'description' => 'Validation par le responsable du service demandeur'
            ],
            'validation-budget' => [
                'label' => 'Validation Budget',
                'role' => 'responsable-budget',
                'description' => 'Validation budgétaire de la demande'
            ],
            'validation-achat' => [
                'label' => 'Validation Achat',
                'role' => 'service-achat',
                'description' => 'Validation par le service achat'
            ],
            'controle-reception' => [
                'label' => 'Contrôle Réception',
                'role' => 'agent-service',
                'description' => 'Contrôle de réception par l\'agent du service'
            ]
        ];
    }

    /**
     * Check if the model can be approved for the current step.
     * This is used by the Approvable trait.
     */
    public function canBeApproved(): bool
    {
        $currentStepKey = $this->getCurrentApprovalStepKey();

        switch($currentStepKey) {
            case 'validation-responsable-service':
                return $this->serviceDemandeur->responsables()->exists();
            
            case 'validation-budget':
                if (!$this->budgetLigne) {
                    Log::warning("DemandeDevis ID {$this->id}: Tentative de validation budgétaire sans budgetLigne associée.");
                    return false;
                }
                if ($this->budgetLigne->valide_budget !== 'oui') {
                    Log::info("DemandeDevis ID {$this->id}: Tentative de validation sur ligne budgétaire non validée ('{$this->budgetLigne->valide_budget}').");
                    return false;
                }
                if (!$this->budgetLigne->canAcceptNewDemande((float) $this->prix_total_ttc)) {
                    Log::info("DemandeDevis ID {$this->id}: Tentative de validation, mais budget insuffisant sur la ligne {$this->budgetLigne->id}.");
                    return false;
                }
                return true;
            
            case 'validation-achat':
                return !empty($this->fournisseur_propose);
            
            case 'controle-reception':
                return true;
            
            default:
                return true;
        }
    }

    // Media Library Configuration
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('devis_fournisseur')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile()
            ->useFallbackUrl('/images/no-quote.pdf') // Ensure this path is valid in public/images
            ->useFallbackPath(public_path('/images/no-quote.pdf')); // For local operations

        $this->addMediaCollection('documents_complementaires')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->useFallbackUrl('/images/no-document.png')
            ->useFallbackPath(public_path('/images/no-document.png'));
    }

    // Optional: Media Conversions (if not already handled globally or if specific conversions are needed)
    public function registerMediaConversions(Media $media = null): void
    {
        if ($media && str_starts_with($media->mime_type, 'image/')) {
            $this->addMediaConversion('thumbnail')
              ->width(150)
              ->height(150)
              ->sharpen(10);
        }
    }


    // SCOPES
    public function scopeEnAttenteValidationResponsableService(Builder $query): Builder
    {
        // Demandes en attente de l'approbation du Responsable Service
        return $query->where('current_step', 'validation-responsable-service')
                     ->where(function (Builder $q) {
                         // Statut 'pending' est le statut initial avant toute action du workflow.
                         // Ou un statut spécifique si on en définit un après la création par l'agent.
                         $q->where('statut', 'pending');
                         // Add other relevant statuses if a demand can reach this step from other states.
                     });
    }

    public function scopeEnAttenteBudget(Builder $query): Builder
    {
        // Demandes en attente de l'approbation du Responsable Budget
        // Assumes current_step is reliably updated by the approval package.
        return $query->where('current_step', 'validation-budget')
                     ->where(function (Builder $q) {
                        // Le statut pourrait être 'pending_budget_approval' ou un statut générique
                        // si la demande vient d'être approuvée par le responsable de service.
                        // Pour l'instant, on se fie surtout à current_step.
                        // Le statut 'pending' est trop générique ici si on a plusieurs étapes avant.
                        // On s'attend à ce que le statut soit mis à jour par l'action d'approbation précédente.
                        // Exemple: ->where('statut', 'pending_budget_validation')
                        // Ou, si le package ne met pas à jour 'statut' mais seulement 'current_step':
                        $q->whereNotIn('statut', ['rejected', 'cancelled', 'delivered']); // Exclure les états finaux
                     });
    }

    public function scopeEnAttenteAchat(Builder $query): Builder
    {
        // Demandes en attente de l'approbation du Service Achat
        return $query->where('current_step', 'validation-achat')
                     ->where(function (Builder $q) {
                        // सिमिलर to above, statut should reflect it passed budget approval.
                        // Exemple: ->where('statut', 'pending_achat_validation')
                        $q->whereNotIn('statut', ['rejected', 'cancelled', 'delivered']);
                     });
    }

    public function scopeEnAttenteReception(Builder $query): Builder
    {
        // Demandes en attente de la confirmation de réception par l'agent service
        return $query->where('current_step', 'controle-reception')
                     ->where(function (Builder $q) {
                        // Statut pourrait être 'pending_reception' ou 'commande_livree_en_attente_confirmation'
                        $q->whereNotIn('statut', ['rejected', 'cancelled', 'delivered']);
                     });
    }


    // Helper to get current approval step label if needed by Filament/Livewire
    public function getCurrentApprovalStepLabel(): ?string
    {
        $currentStepKey = $this->getCurrentApprovalStepKey();
        
        if ($currentStepKey) {
            $steps = $this->approvalSteps();
            return $steps[$currentStepKey]['label'] ?? $currentStepKey;
        }

        if ($this->isFullyApproved()) {
            return 'Terminé';
        }

        if ($this->isRejected()) {
            return 'Rejeté';
        }

        return 'Nouvelle Demande';
    }
}

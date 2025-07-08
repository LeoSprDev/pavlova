<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ProcessApproval as Approval; // Alias for clarity
use App\Traits\Approvable;
use App\Contracts\Approvable as ApprovableContract; // Corrected Contract name
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DemandeDevis extends Model implements ApprovableContract, HasMedia
{
    use HasFactory, Approvable, InteractsWithMedia; // Removed HasApprovals as Approvable includes it

    protected $fillable = [
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
    public function approvalsHistory(): HasMany
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
            'responsable-budget' => [
                'label' => 'Validation budgétaire',
                'description' => 'Vérification cohérence budget et enveloppe service',
                'role' => 'responsable-budget', // Role name from spatie/laravel-permission
                'conditions' => ['budget_available', 'line_validated'] // These are example condition names
            ],
            'service-achat' => [
                'label' => 'Validation achat',
                'description' => 'Analyse fournisseur et optimisation commande',
                'role' => 'service-achat',
                'conditions' => ['supplier_valid', 'commercial_terms_ok']
            ],
            'reception-livraison' => [
                'label' => 'Contrôle réception',
                'description' => 'Vérification livraison et conformité produit',
                'role' => 'service-demandeur', // This should be the role of the user who initiated
                                               // or is responsible for receiving in that service.
                'auto_trigger' => 'on_delivery_upload' // Example auto trigger
            ]
        ];
    }
    // Compatibility with prompt's naming if needed
    public function getApprovalSteps(): array
    {
        return $this->approvalSteps();
    }


    /**
     * Check if the model can be approved for the current step.
     * This is used by the Approvable trait.
     */
    public function canBeApproved(): bool
    {
        if (!$this->budgetLigne) {
            return false; // Should not happen if data is consistent
        }
        return $this->budgetLigne->canAcceptNewDemande((float) $this->prix_total_ttc) &&
               $this->budgetLigne->valide_budget === 'oui';
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
    public function scopeEnAttenteBudget(Builder $query): Builder
    {
        // This scope needs to interact with the approval package's way of storing steps.
        // 'pending' status and not having an 'approved' status for 'responsable-budget' step.
        return $query->where('statut', 'pending') // General pending status
                     ->where(function (Builder $q) { // More specific check based on approval steps
                        $q->where('current_step', 'responsable-budget') // If current_step is tracked
                          ->orWhereDoesntHave('approvalsHistory', function (Builder $approvalQuery) {
                              $approvalQuery->where('step', 'responsable-budget')
                                            ->where('status', 'approved');
                          });
                     });
    }

    public function scopeEnAttenteAchat(Builder $query): Builder
    {
        // 'approved_budget' status implies it's waiting for 'service-achat'
        return $query->where('statut', 'approved_budget')
                     ->where('current_step', 'service-achat'); // If current_step is tracked
    }


    // Helper to get current approval step label if needed by Filament/Livewire
    public function getCurrentApprovalStepLabel(): ?string
    {
        $currentStepKey = $this->getCurrentApprovalStepKey(); // Method from Approvable trait
        if ($currentStepKey) {
            $steps = $this->approvalSteps();
            return $steps[$currentStepKey]['label'] ?? $currentStepKey;
        }
        return $this->isFullyApproved() ? 'Terminé' : ($this->isRejected() ? 'Rejeté' : 'N/A');
    }
}

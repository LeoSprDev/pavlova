<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\ProcessApproval as Approval; // Alias for clarity
use App\Traits\Approvable;
use App\Contracts\Approvable as ApprovableContract; // Corrected Contract name
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DemandeDevis extends Model implements ApprovableContract, HasMedia
{
    use HasFactory, Approvable, InteractsWithMedia; // Removed HasApprovals as Approvable includes it

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (DemandeDevis $demande) {
            // S'assurer que created_by est défini
            if (empty($demande->created_by)) {
                $demande->created_by = auth()->id() ?? 1; // Utilisateur par défaut si pas authentifié
            }

            if ($demande->fournisseur_propose) {
                app(\App\Services\FournisseurTrackingService::class)
                    ->createOrUpdateFournisseur($demande->fournisseur_propose);
            }
        });
    }

    protected $fillable = [
        'service_demandeur_id',
        'budget_ligne_id',
        'created_by',
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
        'date_envoi_demande_fournisseur',
        'date_reception_devis',
        'prix_fournisseur_final',
        'devis_fournisseur_valide',
        'numero_commande_fournisseur',
        'statut_fournisseur',
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
        'date_envoi_demande_fournisseur' => 'date',
        'date_reception_devis' => 'date',
        'prix_fournisseur_final' => 'decimal:2',
        'devis_fournisseur_valide' => 'boolean',
    ];

    public function serviceDemandeur(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_demandeur_id');
    }

    public function budgetLigne(): BelongsTo
    {
        return $this->belongsTo(BudgetLigne::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_propose', 'nom');
    }

    public function commande(): HasOne
    {
        return $this->hasOne(Commande::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
            'responsable-service' => [
                'label' => 'Validation responsable service',
                'description' => 'Validation hiérarchique du service',
                'role' => 'responsable-service',
                'conditions' => ['agent_same_service'],
            ],
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
            'ready_for_order' => [
                'label' => 'Préparation commande',
                'description' => 'Validation achat terminée, en attente de commande',
                'role' => 'service-achat',
                'conditions' => []
            ],
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

    public function canPasserCommande(): bool
    {
        return $this->statut === 'approved_achat' &&
               $this->devis_fournisseur_valide &&
               !empty($this->prix_fournisseur_final);
    }

    // Media Library Configuration
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('devis_initial')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->singleFile()
            ->useFallbackUrl('/images/no-document.pdf');

        $this->addMediaCollection('devis_fournisseur')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->singleFile()
            ->useFallbackUrl('/images/no-supplier-doc.pdf');

        $this->addMediaCollection('bons_commande')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile()
            ->useFallbackUrl('/images/no-order.pdf');
    }

    // Optional: Media Conversions (if not already handled globally or if specific conversions are needed)
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(300)
            ->height(200)
            ->optimize()
            ->quality(90)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->optimize()
            ->quality(95)
            ->nonQueued();
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

    public function approve(User $user, string $comment = ''): bool
    {
        $currentStep = $this->getCurrentApprovalStepKey();

        if ($user->hasRole('manager-service') && $currentStep === 'pending_manager') {
            $this->update([
                'statut' => 'approved_manager',
                'current_step' => 'pending_direction',
                'commentaire_manager' => $comment,
                'approved_manager_at' => now(),
                'approved_manager_by' => $user->id,
            ]);
            $this->notifyNextLevel('direction');
            return true;
        }

        if ($user->hasRole('responsable-direction') && $currentStep === 'pending_direction') {
            if (! $this->checkBudgetDisponible()) {
                throw new \Exception('Budget insuffisant pour cette demande');
            }

            $this->update([
                'statut' => 'approved_direction',
                'current_step' => 'pending_achat',
                'commentaire_direction' => $comment,
                'approved_direction_at' => now(),
                'approved_direction_by' => $user->id,
            ]);
            $this->budgetLigne->increment('montant_engage', $this->prix_total_ttc);
            $this->notifyNextLevel('achat');
            return true;
        }

        if ($user->hasRole('service-achat') && $currentStep === 'pending_achat') {
            $this->update([
                'statut' => 'approved_achat',
                'current_step' => 'ready_for_order',
                'commentaire_achat' => $comment,
                'approved_achat_at' => now(),
                'approved_achat_by' => $user->id,
            ]);
            $this->notifyNextLevel('achat');
            return true;
        }

        return false;
    }

    public function reject(User $user, string $reason): bool
    {
        $currentStep = $this->getCurrentApprovalStepKey();

        $this->update([
            'statut' => 'rejected_by_' . $user->getRoleNames()->first(),
            'current_step' => $this->getPreviousStep($currentStep),
            'commentaire_rejet' => $reason,
            'rejected_at' => now(),
            'rejected_by' => $user->id,
        ]);

        $this->notifyRejection($user, $reason);
        return true;
    }

    private function checkBudgetDisponible(): bool
    {
        $budgetDisponible = $this->budgetLigne->calculateBudgetRestant();
        return $budgetDisponible >= $this->prix_total_ttc;
    }

    private function createCommande(User $user, string $comment): Commande
    {
        return Commande::create([
            'demande_devis_id' => $this->id,
            'numero_commande' => 'CMD-' . now()->format('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT),
            'date_commande' => now(),
            'commanditaire' => $user->name,
            'fournisseur_nom' => $this->fournisseur_propose,
            'montant_ht' => $this->prix_fournisseur_final ?? $this->prix_total_ttc / 1.2,
            'montant_ttc' => $this->prix_fournisseur_final ?? $this->prix_total_ttc,
            'date_livraison_prevue' => now()->addDays(15),
            'statut' => 'confirmee',
            'commentaire_commande' => $comment,
        ]);
    }

    public function prepareCommande(): Commande
    {
        return new Commande([
            'demande_devis_id' => $this->id,
            'numero_commande' => 'CMD-' . now()->format('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT),
            'fournisseur_nom' => $this->fournisseur_propose,
            'montant_ht' => $this->prix_unitaire_ht * $this->quantite,
            'montant_ttc' => $this->prix_total_ttc,
            'service_demandeur_id' => $this->service_demandeur_id,
            'date_commande' => now(),
        ]);
    }

    private function getPreviousStep(?string $currentStep): ?string
    {
        $steps = array_keys($this->approvalSteps());
        $index = array_search($currentStep, $steps);
        if ($index === false || $index === 0) {
            return null;
        }

        return $steps[$index - 1];
    }

    private function notifyNextLevel(string $role): void
    {
        // Implementation placeholder for notifications
    }

    private function notifyRejection(User $user, string $reason): void
    {
        // Implementation placeholder for rejection notifications
    }

    public function getCurrentApprovalStepKey(): ?string
    {
        return $this->current_step;
    }

    public function isFullyApproved(): bool
    {
        return $this->statut === 'delivered_confirmed';
    }

    public function isRejected(): bool
    {
        return str_contains($this->statut, 'rejected');
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

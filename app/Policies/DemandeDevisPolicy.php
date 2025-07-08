<?php

namespace App\Policies;

use App\Models\DemandeDevis;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DemandeDevisPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Tous les rôles du workflow peuvent lister des demandes, le scope sera affiné par EloquentQuery dans la Resource.
        return $user->hasAnyRole(['agent-service', 'responsable-service', 'responsable-budget', 'service-achat', 'administrateur']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DemandeDevis $demandeDevis): bool
    {
        if ($user->hasAnyRole(['responsable-budget', 'service-achat', 'administrateur'])) {
            return true; // Ces rôles peuvent voir toutes les demandes une fois qu'elles atteignent un certain stade.
        }
        if ($user->hasRole('agent-service')) {
            // L'agent peut voir les demandes qu'il a créées.
            // Note: La colonne user_id sur DemandeDevis serait nécessaire ici.
            // Pour l'instant, on se base sur le service_demandeur_id et on assume que l'agent est de ce service.
            // Une vérification plus stricte serait $demandeDevis->user_id === $user->id
            return $user->service_id === $demandeDevis->service_demandeur_id;
        }
        if ($user->hasRole('responsable-service')) {
            // Le responsable de service peut voir toutes les demandes de son service.
            return $user->service_id === $demandeDevis->service_demandeur_id;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('agent-service'); // Seul l'agent service peut créer.
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DemandeDevis $demandeDevis): bool
    {
        // L'agent service peut modifier sa demande si elle est en attente de validation par le responsable service,
        // ou si elle a été rejetée par le responsable service (pour correction).
        if ($user->hasRole('agent-service') && $demandeDevis->user_id === $user->id) { // Assumant un champ user_id sur DemandeDevis
            return ($demandeDevis->getCurrentApprovalStepKey() === 'validation-responsable-service' && $demandeDevis->statut === 'pending') ||
                   ($demandeDevis->isRejected() && $demandeDevis->getApprovalHistory()->last()?->step === 'validation-responsable-service'); // Approx.
        }

        // Le responsable de service peut modifier une demande de son service avant de la valider.
        if ($user->hasRole('responsable-service') && $user->service_id === $demandeDevis->service_demandeur_id) {
            return $demandeDevis->getCurrentApprovalStepKey() === 'validation-responsable-service' && $demandeDevis->statut === 'pending';
        }
        // Autres rôles (RB, SA) pourraient avoir des droits de modification limités (commentaires, champs spécifiques)
        // mais pas une mise à jour générale. Pour l'instant, on limite.
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DemandeDevis $demandeDevis): bool
    {
        // L'agent service peut supprimer sa demande si elle n'a pas encore été approuvée par le responsable service.
        if ($user->hasRole('agent-service') && $demandeDevis->user_id === $user->id) { // Assumant user_id
             return $demandeDevis->getCurrentApprovalStepKey() === 'validation-responsable-service' && $demandeDevis->statut === 'pending';
        }
        // L'administrateur peut supprimer (avec prudence).
        if ($user->hasRole('administrateur')) {
            return true; // Ou ajouter des conditions plus strictes.
        }
        return false;
    }

    // /**
    //  * Determine whether the user can approve the model for a specific step.
    //  * This uses the `laravel-process-approval` logic.
    //  */
    // public function approve(User $user, DemandeDevis $demandeDevis): bool
    // {
    //     // This generic approve method might be too simple if using specific policy methods per step.
    //     // Or, it could be the entry point that then calls canBeApprovedBy from the trait.
    //     // For now, we will use specific methods below.
    //     // $currentStepKey = $demandeDevis->getCurrentApprovalStepKey();
    //     // if (!$currentStepKey) {
    //     //     return false;
    //     // }
    //     // $workflowConfig = config('approval.workflows.' . $demandeDevis->approvalWorkflow());
    //     // $requiredRole = $workflowConfig['steps'][$currentStepKey]['approver_role'] ?? null;
    //     // if (!$requiredRole) {
    //     //     return false; // Misconfiguration
    //     // }
    //     // if ($user->hasRole($requiredRole)) {
    //     //     // Additional specific checks like service ownership can be added here or in dedicated methods
    //     //     return true;
    //     // }
    //     // return false;
    //     return $demandeDevis->canBeApprovedBy($user); // Prefer using the trait's method
    // }

    // /**
    //  * Determine whether the user can reject the model for a specific step.
    //  */
    // public function reject(User $user, DemandeDevis $demandeDevis): bool
    // {
    //     // return $this->approve($user, $demandeDevis); // Or use trait's canBeRejectedBy
    //     return $demandeDevis->canBeRejectedBy($user); // Prefer using the trait's method
    // }

    // --- Specific approval methods for each step of the NEW workflow ---

    /**
     * Determine whether the user can approve/reject at the 'validation-responsable-service' step.
     */
    private function canManageValidationResponsableService(User $user, DemandeDevis $demandeDevis): bool
    {
        if ($demandeDevis->getCurrentApprovalStepKey() !== 'validation-responsable-service') {
            return false;
        }
        return $user->hasRole('responsable-service') && $user->service_id === $demandeDevis->service_demandeur_id;
    }

    public function approveValidationResponsableService(User $user, DemandeDevis $demandeDevis): bool
    {
        return $this->canManageValidationResponsableService($user, $demandeDevis);
    }

    public function rejectValidationResponsableService(User $user, DemandeDevis $demandeDevis): bool
    {
        return $this->canManageValidationResponsableService($user, $demandeDevis);
    }

    /**
     * Determine whether the user can approve/reject at the 'validation-budget' step.
     */
    private function canManageValidationBudget(User $user, DemandeDevis $demandeDevis): bool
    {
        if ($demandeDevis->getCurrentApprovalStepKey() !== 'validation-budget') {
            return false;
        }
        return $user->hasRole('responsable-budget');
    }

    public function approveValidationBudget(User $user, DemandeDevis $demandeDevis): bool
    {
        return $this->canManageValidationBudget($user, $demandeDevis);
    }

    public function rejectValidationBudget(User $user, DemandeDevis $demandeDevis): bool
    {
        return $this->canManageValidationBudget($user, $demandeDevis);
    }

    /**
     * Determine whether the user can approve/reject at the 'validation-achat' step.
     */
    private function canManageValidationAchat(User $user, DemandeDevis $demandeDevis): bool
    {
        if ($demandeDevis->getCurrentApprovalStepKey() !== 'validation-achat') {
            return false;
        }
        return $user->hasRole('service-achat');
    }

    public function approveValidationAchat(User $user, DemandeDevis $demandeDevis): bool
    {
        return $this->canManageValidationAchat($user, $demandeDevis);
    }

    public function rejectValidationAchat(User $user, DemandeDevis $demandeDevis): bool
    {
        return $this->canManageValidationAchat($user, $demandeDevis);
    }

    /**
     * Determine whether the user can approve/reject at the 'controle-reception' step.
     */
    private function canManageControleReception(User $user, DemandeDevis $demandeDevis): bool
    {
        if ($demandeDevis->getCurrentApprovalStepKey() !== 'controle-reception') {
            return false;
        }
        // Assuming user_id is the creator of the DemandeDevis
        return $user->hasRole('agent-service') && $demandeDevis->user_id === $user->id;
    }

    public function approveControleReception(User $user, DemandeDevis $demandeDevis): bool
    {
        return $this->canManageControleReception($user, $demandeDevis);
    }

    public function rejectControleReception(User $user, DemandeDevis $demandeDevis): bool
    {
        // Typically, reception is confirmed or an issue is raised, direct rejection might be less common.
        // If rejection means "problem with delivery", this permission might be valid.
        return $this->canManageControleReception($user, $demandeDevis);
    }

    // Specific step approval permissions from prompt (can be part of the 'approve' method above)
    // These are now superseded by the methods above.
    // public function approveBudgetDemande(User $user, DemandeDevis $demandeDevis): bool
    // {
    //     return $demandeDevis->getCurrentApprovalStepKey() === 'responsable-budget' && $user->hasRole('responsable-budget');
    // }

    // public function approveAchatDemande(User $user, DemandeDevis $demandeDevis): bool
    // {
    //     return $demandeDevis->getCurrentApprovalStepKey() === 'service-achat' && $user->hasRole('service-achat');
    // }

    // public function approveReceptionDemande(User $user, DemandeDevis $demandeDevis): bool
    // {
    //     return $demandeDevis->getCurrentApprovalStepKey() === 'reception-livraison' &&
    //            $user->hasRole('service-demandeur') &&
    //            $user->service_id === $demandeDevis->service_demandeur_id;
    // }
}

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
        return $user->hasAnyRole(['responsable-budget', 'service-demandeur', 'service-achat', 'responsable-service', 'agent-service']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DemandeDevis $demandeDevis): bool
    {
        if ($user->hasAnyRole(['responsable-budget', 'service-achat'])) {
            return true;
        }
        if ($user->hasAnyRole(['service-demandeur', 'responsable-service', 'agent-service'])) {
            return $user->service_id === $demandeDevis->service_demandeur_id;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['service-demandeur', 'agent-service']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DemandeDevis $demandeDevis): bool
    {
        // Only the requester service can update, and only if it's still pending or rejected at their level
        if ($user->hasAnyRole(['service-demandeur', 'agent-service']) && $user->service_id === $demandeDevis->service_demandeur_id) {
            // Cannot update if it's already passed budget approval or fully delivered/approved
            return in_array($demandeDevis->statut, ['pending', 'rejected']) ||
                   ($demandeDevis->current_step === 'reception-livraison' && $demandeDevis->statut !== 'delivered');
        }
        // RB or SA might be able to add comments or specific fields but not general update
        // For now, restricting general update to service demandeur under conditions.
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DemandeDevis $demandeDevis): bool
    {
        if ($user->hasRole('responsable-budget')) { // RB might be able to delete certain erroneous requests
            return in_array($demandeDevis->statut, ['pending', 'rejected']);
        }
        if ($user->hasAnyRole(['service-demandeur', 'agent-service']) && $user->service_id === $demandeDevis->service_demandeur_id) {
            return in_array($demandeDevis->statut, ['pending', 'rejected']); // Can delete if not yet processed far
        }
        return false;
    }

    /**
     * Determine whether the user can approve the model for a specific step.
     * This uses the `laravel-process-approval` logic.
     */
    public function approve(User $user, DemandeDevis $demandeDevis): bool
    {
        $currentStepKey = $demandeDevis->getCurrentApprovalStepKey();
        if (!$currentStepKey) {
            return false; // No current step to approve (e.g., already fully approved or rejected)
        }

        $workflowSteps = $demandeDevis->approvalSteps();
        $requiredRole = $workflowSteps[$currentStepKey]['role'] ?? null;

        if (!$requiredRole) {
            return false; // Misconfiguration
        }

        if ($user->hasRole($requiredRole)) {
            if ($requiredRole === 'service-demandeur' && $currentStepKey === 'reception-livraison') {
                // Ensure the service demandeur is from the same service as the request
                return $user->service_id === $demandeDevis->service_demandeur_id;
            }
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can reject the model for a specific step.
     */
    public function reject(User $user, DemandeDevis $demandeDevis): bool
    {
        // Rejection permission can mirror approval permission or be more broad
        // For now, assume if you can approve, you can reject.
        return $this->approve($user, $demandeDevis);
    }


    // Specific step approval permissions from prompt (can be part of the 'approve' method above)

    public function approveBudgetDemande(User $user, DemandeDevis $demandeDevis): bool
    {
        return $demandeDevis->getCurrentApprovalStepKey() === 'responsable-budget' && $user->hasRole('responsable-budget');
    }

    public function approveAchatDemande(User $user, DemandeDevis $demandeDevis): bool
    {
        return $demandeDevis->getCurrentApprovalStepKey() === 'service-achat' && $user->hasRole('service-achat');
    }

    public function approveReceptionDemande(User $user, DemandeDevis $demandeDevis): bool
    {
        return $demandeDevis->getCurrentApprovalStepKey() === 'reception-livraison' &&
               $user->hasRole('service-demandeur') &&
               $user->service_id === $demandeDevis->service_demandeur_id;
    }
}

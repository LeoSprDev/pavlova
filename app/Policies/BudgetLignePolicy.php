<?php

namespace App\Policies;

use App\Models\BudgetLigne;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BudgetLignePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['responsable-budget', 'service-demandeur', 'service-achat', 'responsable-service']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BudgetLigne $budgetLigne): bool
    {
        if ($user->hasRole('responsable-budget')) {
            return true;
        }
        if ($user->hasRole('service-achat')) { // Service achat might need to view lines for context
            return true;
        }
        if ($user->hasRole('service-demandeur') || $user->hasRole('responsable-service')) {
            return $user->service_id === $budgetLigne->service_id;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Service demandeur can create for their own service, RB can create for any
        return $user->hasAnyRole(['responsable-budget', 'service-demandeur']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BudgetLigne $budgetLigne): bool
    {
        if ($user->hasRole('responsable-budget')) {
            return true; // RESPONSABLE BUDGET PEUT TOUT MODIFIER
        }

        if ($user->hasRole('service-demandeur') && $user->service_id === $budgetLigne->service_id) {
            // SERVICE DEMANDEUR NE PEUT MODIFIER QUE SI PAS ENCORE VALIDÉ DÉFINITIVEMENT PAR RB
            // ou si la ligne n'a pas de dépenses engagées.
            return $budgetLigne->valide_budget !== 'oui' || $budgetLigne->demandesAssociees()->where('statut', '!=', 'rejected')->count() === 0;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BudgetLigne $budgetLigne): bool
    {
        if ($user->hasRole('responsable-budget')) {
            // Can only delete if no associated demands (or only rejected ones)
            return $budgetLigne->demandesAssociees()->where('statut', '!=', 'rejected')->count() === 0;
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BudgetLigne $budgetLigne): bool
    {
        return $user->hasRole('responsable-budget');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BudgetLigne $budgetLigne): bool
    {
        return $user->hasRole('responsable-budget');
    }

    /**
     * Determine whether the user can validate a budget line (specific action for RB).
     */
    public function validateBudget(User $user, BudgetLigne $budgetLigne): bool
    {
        return $user->hasRole('responsable-budget');
    }

     /**
     * Determine whether the user can reallocate a budget line.
     */
    public function reallocateBudget(User $user, BudgetLigne $budgetLigne): bool
    {
        return $user->hasRole('responsable-budget');
    }
}

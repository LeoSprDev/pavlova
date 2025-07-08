<?php

namespace App\Policies;

use App\Models\Commande;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommandePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['responsable-budget', 'service-demandeur', 'service-achat']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Commande $commande): bool
    {
        if ($user->hasAnyRole(['responsable-budget', 'service-achat'])) {
            return true;
        }
        if ($user->hasRole('service-demandeur')) {
            // User can view if the command is linked to their service via DemandeDevis
            return $commande->demandeDevis && $user->service_id === $commande->demandeDevis->service_demandeur_id;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Primarily service-achat, but RB might create in some scenarios.
        return $user->hasAnyRole(['service-achat', 'responsable-budget']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Commande $commande): bool
    {
        if ($user->hasRole('service-achat')) {
            // Service Achat can update orders, especially if not yet fully delivered.
            return $commande->statut !== 'livree' && $commande->statut !== 'annulee';
        }
        if ($user->hasRole('responsable-budget')) {
             // RB might have some update capabilities for financial adjustments or status.
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Commande $commande): bool
    {
        // Deleting orders should be highly restricted.
        // Only if no deliveries made and status allows.
        if ($user->hasRole('responsable-budget') || $user->hasRole('service-achat')) {
            return !$commande->livraison()->exists() && $commande->statut !== 'livree';
        }
        return false;
    }

    /**
     * Determine whether the user can cancel a commande.
     */
    public function cancelCommande(User $user, Commande $commande): bool
    {
        if ($user->hasRole('service-achat')) {
            return $commande->statut !== 'livree' && $commande->statut !== 'annulee';
        }
        if ($user->hasRole('responsable-budget') && $commande->statut !== 'livree' && $commande->statut !== 'annulee') {
            return true; // RB might cancel for budgetary reasons
        }
        return false;
    }


    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Commande $commande): bool
    {
        return $user->hasAnyRole(['responsable-budget', 'administrateur']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Commande $commande): bool
    {
        return $user->hasAnyRole(['responsable-budget', 'administrateur']);
    }
}

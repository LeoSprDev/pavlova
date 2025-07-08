<?php

namespace App\Policies;

use App\Models\Livraison;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LivraisonPolicy
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
    public function view(User $user, Livraison $livraison): bool
    {
        if ($user->hasAnyRole(['responsable-budget', 'service-achat'])) {
            return true;
        }
        if ($user->hasRole('service-demandeur')) {
            // User can view if the livraison is linked to their service via Commande -> DemandeDevis
            return $livraison->commande && $livraison->commande->demandeDevis &&
                   $user->service_id === $livraison->commande->demandeDevis->service_demandeur_id;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Service demandeur (who receives) or service achat (who might record initial delivery info)
        return $user->hasAnyRole(['service-demandeur', 'service-achat']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Livraison $livraison): bool
    {
        if ($user->hasRole('service-demandeur')) {
            // Can update if it's their service's delivery and not yet fully finalized/closed.
            return $livraison->commande && $livraison->commande->demandeDevis &&
                   $user->service_id === $livraison->commande->demandeDevis->service_demandeur_id &&
                   $livraison->statut_reception !== 'recue_validee'; // Assuming a final validated status
        }
        if ($user->hasRole('service-achat')) {
            // Can update certain aspects, perhaps related to disputes or returns.
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Livraison $livraison): bool
    {
        // Deleting livraison records should be very restricted.
        return $user->hasAnyRole(['responsable-budget', 'administrateur']) && $livraison->statut_reception !== 'recue_validee';
    }

    /**
     * Determine whether the user can validate a livraison (confirm conformity, etc.).
     */
    public function validateLivraison(User $user, Livraison $livraison): bool
    {
        if ($user->hasRole('service-demandeur')) {
            return $livraison->commande && $livraison->commande->demandeDevis &&
                   $user->service_id === $livraison->commande->demandeDevis->service_demandeur_id;
        }
        return false;
    }

    /**
     * Determine whether the user can upload a delivery note for the livraison.
     */
    public function uploadBonLivraison(User $user, Livraison $livraison): bool
    {
         if ($user->hasRole('service-demandeur')) {
            return $livraison->commande && $livraison->commande->demandeDevis &&
                   $user->service_id === $livraison->commande->demandeDevis->service_demandeur_id;
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Livraison $livraison): bool
    {
        return $user->hasAnyRole(['responsable-budget', 'administrateur']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Livraison $livraison): bool
    {
        return $user->hasAnyRole(['responsable-budget', 'administrateur']);
    }
}

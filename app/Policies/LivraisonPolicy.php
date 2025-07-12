<?php
namespace App\Policies;

use App\Models\{Livraison, User};

class LivraisonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['responsable-budget', 'service-achat', 'agent-service', 'service-demandeur']);
    }

    public function view(User $user, Livraison $livraison): bool
    {
        if ($user->hasAnyRole(['agent-service', 'service-demandeur'])) {
            return $livraison->commande?->demandeDevis?->service_demandeur_id === $user->service_id;
        }
        return $user->hasAnyRole(['responsable-budget', 'service-achat']);
    }

    public function update(User $user, Livraison $livraison): bool
    {
        if ($user->hasAnyRole(['agent-service', 'service-demandeur'])) {
            return $livraison->commande?->demandeDevis?->service_demandeur_id === $user->service_id;
        }
        return false;
    }

    public function validerReceptionComplete(User $user, Livraison $livraison): bool
    {
        return $this->update($user, $livraison) &&
               $livraison->statut_reception !== 'recu_conforme' &&
               $livraison->getMedia('bons_livraison')->isNotEmpty();
    }
}

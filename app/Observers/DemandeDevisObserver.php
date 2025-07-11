<?php

namespace App\Observers;

use App\Models\DemandeDevis;
use App\Notifications\DemandeStatusChangedNotification;

class DemandeDevisObserver
{
    public function updated(DemandeDevis $demande): void
    {
        if ($demande->wasChanged('statut')) {
            $demande->createdBy?->notify(new DemandeStatusChangedNotification($demande));
        }
    }
}

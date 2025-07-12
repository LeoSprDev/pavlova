<?php
namespace App\Observers;

use App\Models\{AuditTrail, DemandeDevis};
use App\Services\AuditTrailService;

class AuditTrailObserver
{
    public function updated(DemandeDevis $demande): void
    {
        if ($demande->isDirty('statut')) {
            app(AuditTrailService::class)->logAction(
                'status_changed',
                DemandeDevis::class,
                $demande->id,
                [
                    'old' => $demande->getOriginal('statut'),
                    'new' => $demande->statut,
                ]
            );
        }
    }
}

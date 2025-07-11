<?php

namespace App\Observers;

use App\Models\Commande;
use App\Models\User;
use Filament\Notifications\Notification;

class CommandeObserver
{
    public function updated(Commande $commande): void
    {
        if ($commande->isDirty('statut') && $commande->statut === 'livree') {
            $this->notifyReception($commande);
        }

        if ($commande->isEnRetard()) {
            $this->notifyRetard($commande);
        }
    }

    private function notifyRetard(Commande $commande): void
    {
        $recipients = User::role('responsable-achat')->get();
        foreach ($recipients as $user) {
            Notification::make()
                ->title('📦 Livraison en retard')
                ->body("Commande {$commande->numero_commande} en retard de {$commande->joursRetard()} jours")
                ->warning()
                ->sendToDatabase($user);
        }
    }

    private function notifyReception(Commande $commande): void
    {
        $users = User::role('agent-service')->get();
        foreach ($users as $user) {
            Notification::make()
                ->title('Livraison confirmée')
                ->body("Commande {$commande->numero_commande} livrée")
                ->success()
                ->sendToDatabase($user);
        }
    }
}

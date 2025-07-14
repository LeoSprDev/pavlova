<?php

namespace App\Observers;

use App\Models\Commande;
use App\Models\User;
use App\Models\Livraison;
use Filament\Notifications\Notification;

class CommandeObserver
{
    public function created(Commande $commande): void
    {
        Livraison::create([
            'commande_id' => $commande->id,
            'statut_reception' => 'en_attente',
            'date_livraison_prevue' => now()->addDays(7),
        ]);
    }

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
        $recipients = User::role('service-achat')->get();
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

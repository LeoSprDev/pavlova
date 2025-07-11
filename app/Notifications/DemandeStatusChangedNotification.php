<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\DemandeDevis;

class DemandeStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public DemandeDevis $demande)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'demande_id' => $this->demande->id,
            'statut' => $this->demande->statut,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Demande mise à jour')
            ->line('La demande "' . $this->demande->denomination . '" est maintenant ' . $this->demande->statut . '.');
    }
}

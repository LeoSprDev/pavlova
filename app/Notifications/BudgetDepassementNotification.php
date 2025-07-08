<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\BudgetLigne;

class BudgetDepassementNotification extends Notification
{
    use Queueable;

    public function __construct(public BudgetLigne $ligne) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'budget_depassement',
            'titre' => 'Budget dépassé',
            'message' => "La ligne '{$this->ligne->intitule}' du service {$this->ligne->service->nom} a dépassé son budget.",
            'ligne_id' => $this->ligne->id,
            'service_nom' => $this->ligne->service->nom,
            'montant_prevu' => $this->ligne->montant_ht_prevu,
            'url' => "/admin/budget-lignes/{$this->ligne->id}"
        ];
    }
}

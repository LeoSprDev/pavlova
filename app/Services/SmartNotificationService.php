<?php

namespace App\Services;

use App\Models\{BudgetLigne, Commande, DemandeDevis, User};
use App\Mail\DigestNotificationMail;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class SmartNotificationService
{
    public function sendRoleSpecificNotification(string $event, $model): void
    {
        $users = match ($event) {
            'budget_critique' => User::role('responsable-direction')->get(),
            'livraison_retard' => User::role('service-achat')->get(),
            default => User::role('admin')->get(),
        };

        foreach ($users as $user) {
            Notification::make()
                ->title($this->title($event))
                ->body($this->body($event, $model))
                ->sendToDatabase($user);
        }
    }

    private function title(string $event): string
    {
        return match ($event) {
            'budget_critique' => '🚨 Budget critique',
            'livraison_retard' => '📦 Livraison en retard',
            default => 'Notification',
        };
    }

    private function body(string $event, $model): string
    {
        if ($event === 'budget_critique' && $model instanceof BudgetLigne) {
            return "La ligne {$model->intitule} dépasse 95%";
        }

        if ($event === 'livraison_retard' && $model instanceof Commande) {
            return "Commande {$model->numero_commande} en retard";
        }

        return '';
    }

    public function sendWorkflowNotification(DemandeDevis $demande, string $event): void
    {
        $users = User::role('service-achat')->get();
        foreach ($users as $user) {
            Notification::make()
                ->title('Workflow ' . $event)
                ->body("Demande #{$demande->id} : {$event}")
                ->sendToDatabase($user);
        }
    }

    public function sendBudgetAlert(BudgetLigne $ligne, string $type): void
    {
        $users = User::role('responsable-budget')->get();
        foreach ($users as $user) {
            Notification::make()
                ->title("Alerte budget {$type}")
                ->body("Ligne {$ligne->intitule} nécessite attention")
                ->sendToDatabase($user);
        }
    }

    public function sendEscalationNotification(DemandeDevis $demande): void
    {
        $direction = User::role('responsable-direction')->get();
        Notification::make()
            ->title('⏰ Escalade workflow')
            ->body("Demande #{$demande->id} en attente depuis trop longtemps")
            ->sendToDatabase($direction->all());
    }

    public function sendDigestNotifications(): void
    {
        $users = User::all();
        foreach ($users as $user) {
            $notifications = $user->unreadNotifications()->limit(20)->get();
            if ($notifications->isEmpty() || !$user->email) {
                continue;
            }
            Mail::to($user->email)->queue(new DigestNotificationMail($user, $notifications));
        }
    }
}

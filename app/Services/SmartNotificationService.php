<?php

namespace App\Services;

use App\Models\BudgetLigne;
use App\Models\Commande;
use App\Models\User;
use Filament\Notifications\Notification;

class SmartNotificationService
{
    public function sendRoleSpecificNotification(string $event, $model): void
    {
        $users = match ($event) {
            'budget_critique' => User::role('responsable-direction')->get(),
            'livraison_retard' => User::role('responsable-achat')->get(),
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
}

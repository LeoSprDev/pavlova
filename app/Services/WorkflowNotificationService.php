<?php
namespace App\Services;

use App\Models\{DemandeDevis, BudgetLigne, User};
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\WorkflowNotificationMail;

class WorkflowNotificationService
{
    public function notifyNextApprovers(DemandeDevis $demande): void
    {
        $role = $demande->approvalSteps()[$demande->statut]['role'] ?? null;
        if (! $role) {
            return;
        }

        $users = User::role($role)->get();
        foreach ($users as $user) {
            Notification::make()
                ->title('Nouvelle demande à approuver')
                ->body("La demande #{$demande->id} nécessite votre validation")
                ->sendToDatabase($user);

            if ($user->email) {
                Mail::to($user->email)->queue(new WorkflowNotificationMail($demande, $user));
            }
        }
    }

    public function notifyBudgetAlerts(BudgetLigne $ligne): void
    {
        $taux = round($ligne->getTauxUtilisation(), 1);
        $users = User::role('responsable-budget')->get();
        foreach ($users as $user) {
            Notification::make()
                ->title("Budget {$ligne->intitule} {$taux}% utilisé")
                ->warning()
                ->sendToDatabase($user);
        }
    }
}

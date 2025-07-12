<?php
namespace App\Services;

use App\Models\{DemandeDevis, BudgetLigne, BudgetWarning, User};
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

    public function notifyBudgetWarning(BudgetWarning $warning): void
    {
        $users = User::role('responsable-budget')->get();
        foreach ($users as $user) {
            Notification::make()
                ->title('Dépassement budget autorisé')
                ->body($warning->message)
                ->danger()
                ->sendToDatabase($user);
        }
    }

    public function notifyDeadlineApproaching(DemandeDevis $demande): void
    {
        $daysUntilDeadline = now()->diffInDays($demande->date_besoin);

        if ($daysUntilDeadline <= 3) {
            $recipients = $this->getRecipientsForStatus($demande->statut);

            foreach ($recipients as $user) {
                Notification::make()
                    ->title('⏰ Délai approche')
                    ->body("Demande #{$demande->id} expire dans {$daysUntilDeadline} jours")
                    ->warning()
                    ->persistent()
                    ->sendToDatabase($user);
            }
        }
    }

    public function notifyWorkflowCompleted(DemandeDevis $demande): void
    {
        $recipients = [
            $demande->createdBy,
            ...$demande->serviceDemandeur->managers,
        ];

        foreach ($recipients as $user) {
            Mail::to($user->email)->queue(
                new \App\Mail\WorkflowCompletedMail($demande, $user)
            );
        }
    }

    public function notifyBudgetThreshold(BudgetLigne $ligne, int $threshold): void
    {
        $users = User::role('responsable-budget')->get();

        foreach ($users as $user) {
            Notification::make()
                ->title("Budget {$threshold}% consommé")
                ->body("Ligne '{$ligne->intitule}' : {$threshold}% du budget utilisé")
                ->color($threshold >= 90 ? 'danger' : 'warning')
                ->sendToDatabase($user);
        }
    }

    public function sendWeeklyReport(): void
    {
        $responsables = User::role(['responsable-direction', 'responsable-budget'])->get();

        foreach ($responsables as $user) {
            Mail::to($user->email)->queue(
                new \App\Mail\WeeklyReportMail($user)
            );
        }
    }

    private function getRecipientsForStatus(string $status)
    {
        $role = match($status) {
            'pending_manager' => 'manager-service',
            'pending_direction' => 'responsable-direction',
            'pending_achat' => 'service-achat',
            default => 'responsable-budget'
        };

        return User::role($role)->get();
    }
}

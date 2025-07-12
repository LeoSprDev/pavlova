<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\{DemandeDevis, User};
use App\Mail\DeadlineReminderMail;
use Illuminate\Support\Facades\Mail;

class SendDeadlineReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $demandesEnRetard = DemandeDevis::whereIn('statut', [
            'pending_manager', 'pending_direction', 'pending_achat'
        ])
        ->where('updated_at', '<', now()->subDays(3))
        ->where('date_besoin', '>', now())
        ->get();

        foreach ($demandesEnRetard as $demande) {
            $this->sendReminderForDemande($demande);
        }
    }

    private function sendReminderForDemande(DemandeDevis $demande): void
    {
        $recipients = $this->getRecipientsForStatus($demande->statut);

        foreach ($recipients as $user) {
            Mail::to($user->email)->queue(
                new DeadlineReminderMail($demande, $user)
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

        return User::role($role)->whereNotNull('email')->get();
    }
}

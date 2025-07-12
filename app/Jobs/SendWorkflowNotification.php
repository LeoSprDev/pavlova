<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\{DemandeDevis, User};
use App\Mail\WorkflowStepNotificationMail;
use Filament\Notifications\Notification;

class SendWorkflowNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public DemandeDevis $demande,
        public string $action
    ) {}

    public function handle(): void
    {
        $recipients = $this->getRecipients();

        foreach ($recipients as $user) {
            Mail::to($user->email)->queue(
                new WorkflowStepNotificationMail($this->demande, $user, $this->action)
            );

            Notification::make()
                ->title($this->getNotificationTitle())
                ->body($this->getNotificationBody())
                ->icon($this->getNotificationIcon())
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Voir demande')
                        ->url("/admin/demande-devis/{$this->demande->id}")
                        ->button(),
                ])
                ->sendToDatabase($user);
        }
    }

    private function getRecipients()
    {
        $role = match($this->action) {
            'pending_manager' => 'manager-service',
            'pending_direction' => 'responsable-direction',
            'pending_achat' => 'service-achat',
            'ready_for_order' => 'service-achat',
            default => 'responsable-budget'
        };

        return User::role($role)->whereNotNull('email')->get();
    }

    private function getNotificationTitle(): string
    {
        return match($this->action) {
            'rejected' => 'Demande rejetée',
            'ready_for_order' => 'Commande à préparer',
            default => 'Nouvelle étape du workflow'
        };
    }

    private function getNotificationBody(): string
    {
        return "Demande #{$this->demande->id} - {$this->demande->denomination}";
    }

    private function getNotificationIcon(): ?string
    {
        return match($this->action) {
            'rejected' => 'heroicon-o-x-circle',
            'ready_for_order' => 'heroicon-o-check-circle',
            default => 'heroicon-o-bell'
        };
    }
}

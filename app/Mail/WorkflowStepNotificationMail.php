<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\{DemandeDevis, User};

class WorkflowStepNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DemandeDevis $demande,
        public User $user,
        public string $action
    ) {}

    public function build()
    {
        return $this->subject($this->getEmailSubject())
            ->view('emails.workflow.step-notification')
            ->with([
                'userName' => $this->user->name,
                'demandeDenomination' => $this->demande->denomination,
                'demandeId' => $this->demande->id,
                'serviceDemandeur' => $this->demande->serviceDemandeur->nom,
                'montant' => $this->demande->prix_total_ttc,
                'action' => $this->action,
                'actionUrl' => url("/admin/demande-devis/{$this->demande->id}"),
                'deadlineDate' => $this->demande->date_besoin,
            ]);
    }

    private function getEmailSubject(): string
    {
        return match($this->action) {
            'pending_manager' => 'Nouvelle demande à valider - Manager',
            'pending_direction' => 'Validation direction requise',
            'pending_achat' => 'Validation achat requise',
            'ready_for_order' => 'Commande prête à finaliser',
            'rejected' => 'Demande rejetée',
            default => 'Mise à jour demande'
        };
    }
}

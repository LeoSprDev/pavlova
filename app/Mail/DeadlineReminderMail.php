<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\{DemandeDevis, User};

class DeadlineReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DemandeDevis $demande,
        public User $user
    ) {}

    public function build()
    {
        return $this->subject('⏰ Rappel : demande en attente')
            ->view('emails.workflow.deadline-reminder')
            ->with([
                'userName' => $this->user->name,
                'demandeId' => $this->demande->id,
                'denomination' => $this->demande->denomination,
                'actionUrl' => url("/admin/demande-devis/{$this->demande->id}"),
                'deadlineDate' => $this->demande->date_besoin,
            ]);
    }
}

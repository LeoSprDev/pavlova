<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\{DemandeDevis, User};

class WorkflowCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DemandeDevis $demande,
        public User $user
    ) {}

    public function build()
    {
        return $this->subject('✅ Demande finalisée')
            ->view('emails.workflow.workflow-completed')
            ->with([
                'userName' => $this->user->name,
                'demandeId' => $this->demande->id,
                'denomination' => $this->demande->denomination,
                'actionUrl' => url("/admin/demande-devis/{$this->demande->id}"),
            ]);
    }
}

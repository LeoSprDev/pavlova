<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\{DemandeDevis, User};

class RejectionNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DemandeDevis $demande,
        public User $user,
        public string $reason
    ) {}

    public function build()
    {
        return $this->subject('❌ Demande rejetée')
            ->view('emails.workflow.rejection-notification')
            ->with([
                'userName' => $this->user->name,
                'demandeId' => $this->demande->id,
                'denomination' => $this->demande->denomination,
                'reason' => $this->reason,
                'actionUrl' => url("/admin/demande-devis/{$this->demande->id}"),
            ]);
    }
}

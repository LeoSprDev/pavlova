<?php
namespace App\Mail;

use App\Models\DemandeDevis;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;

class WorkflowNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DemandeDevis $demande, public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nouvelle demande à valider');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.workflow.notification',
            with: [
                'demande' => $this->demande,
                'user' => $this->user,
            ]
        );
    }
}

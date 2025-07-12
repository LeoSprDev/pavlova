<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DigestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Collection $notifications)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Résumé quotidien de vos notifications');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notifications.digest',
            with: [
                'user' => $this->user,
                'notifications' => $this->notifications,
            ]
        );
    }
}

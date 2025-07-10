<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Livraison;

class RelanceLivraisonEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Livraison $livraison
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📦 Relance : Confirmation réception livraison requise',
        );
    }

    public function content(): Content
    {
        $demande = $this->livraison->commande->demandeDevis;
        $jours_retard = now()->diffInDays($this->livraison->date_livraison_prevue);

        return new Content(
            view: 'emails.relance-livraison',
            with: [
                'livraison' => $this->livraison,
                'demande' => $demande,
                'jours_retard' => $jours_retard,
            ]
        );
    }
}

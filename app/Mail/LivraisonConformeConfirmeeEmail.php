<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Livraison;

class LivraisonConformeConfirmeeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Livraison $livraison
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Livraison conforme validée - Budget mis à jour',
        );
    }

    public function content(): Content
    {
        $demande = $this->livraison->commande->demandeDevis;
        $montant_reel = $demande->prix_fournisseur_final ?? $demande->prix_total_ttc;

        return new Content(
            view: 'emails.livraison-conforme-confirmee',
            with: [
                'livraison' => $this->livraison,
                'demande' => $demande,
                'montant_reel' => $montant_reel,
            ]
        );
    }
}

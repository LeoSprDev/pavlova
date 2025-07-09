<?php
namespace App\Mail;

use App\Models\Livraison;
use Illuminate\Mail\Mailable;

class RelanceLivraisonEmail extends Mailable
{
    public function __construct(public Livraison $livraison) {}

    public function build()
    {
        return $this->subject('📦 Relance : Confirmation réception livraison requise')
            ->view('emails.relance-livraison')
            ->with([
                'livraison' => $this->livraison,
                'demande' => $this->livraison->commande->demandeDevis,
                'jours_retard' => now()->diffInDays($this->livraison->date_livraison_prevue)
            ]);
    }
}

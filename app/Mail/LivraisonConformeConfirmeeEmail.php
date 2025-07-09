<?php
namespace App\Mail;

use App\Models\Livraison;
use Illuminate\Mail\Mailable;

class LivraisonConformeConfirmeeEmail extends Mailable
{
    public function __construct(public Livraison $livraison) {}

    public function build()
    {
        $demandeDevis = $this->livraison->commande->demandeDevis;

        return $this->subject('✅ Livraison conforme validée - Budget mis à jour')
            ->view('emails.livraison-conforme-confirmee')
            ->with([
                'livraison' => $this->livraison,
                'demande' => $demandeDevis,
                'montant_reel' => $demandeDevis->prix_fournisseur_final ?? $demandeDevis->prix_total_ttc
            ]);
    }
}

<?php
namespace App\Jobs;

use App\Models\DemandeDevis;
use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateCommandeAutomatique implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public DemandeDevis $demande)
    {
    }

    public function handle(): void
    {
        if (! $this->demande->commande) {
            Commande::create([
                'demande_devis_id' => $this->demande->id,
                'numero_commande' => 'CMD-' . now()->format('Y') . '-' . str_pad($this->demande->id, 6, '0', STR_PAD_LEFT),
                'fournisseur_nom' => $this->demande->fournisseur_propose,
                'montant_ht' => $this->demande->prix_unitaire_ht * $this->demande->quantite,
                'montant_ttc' => $this->demande->prix_total_ttc,
                'statut' => 'en_cours',
                'date_commande' => now(),
                'service_demandeur_id' => $this->demande->service_demandeur_id,
            ]);
        }
    }
}

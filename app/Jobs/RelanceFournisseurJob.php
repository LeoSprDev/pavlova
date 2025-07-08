<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Commande;
use App\Models\RelanceFournisseur;
use Illuminate\Support\Facades\Log;

class RelanceFournisseurJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Commande $commande,
        public int $niveauRelance = 1
    ) {}

    public function handle(): void
    {
        // Vérifier si commande toujours en attente
        if ($this->commande->livraison()->exists()) {
            Log::info('Relance annulée - commande déjà livrée', ['commande_id' => $this->commande->id]);
            return;
        }

        try {
            // Enregistrer relance
            RelanceFournisseur::create([
                'commande_id' => $this->commande->id,
                'niveau' => $this->niveauRelance,
                'email_envoye' => now(),
                'destinataire' => $this->commande->fournisseur_email ?? 'non-specifie@exemple.com',
                'template_utilise' => "Relance niveau {$this->niveauRelance}"
            ]);

            // Incrémenter compteur
            $this->commande->increment('nb_relances');

            // Programmer prochaine relance si nécessaire
            if ($this->niveauRelance < 3) {
                $delai = match($this->niveauRelance) {
                    1 => now()->addDays(3),
                    2 => now()->addDays(2)
                };

                RelanceFournisseurJob::dispatch($this->commande, $this->niveauRelance + 1)
                    ->delay($delai);
            }

            Log::info('Relance fournisseur envoyée', [
                'commande_id' => $this->commande->id,
                'niveau' => $this->niveauRelance
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur envoi relance fournisseur', [
                'commande_id' => $this->commande->id,
                'niveau' => $this->niveauRelance,
                'error' => $e->getMessage()
            ]);
        }
    }
}

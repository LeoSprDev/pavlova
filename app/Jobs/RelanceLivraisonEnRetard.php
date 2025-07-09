<?php
namespace App\Jobs;

use App\Models\Livraison;
use App\Mail\RelanceLivraisonEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RelanceLivraisonEnRetard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $livraisonsEnRetard = Livraison::where('statut_reception', 'en_attente')
            ->where('date_livraison_prevue', '<', now()->subDays(7))
            ->whereNull('bon_livraison')
            ->with('commande.demandeDevis.createdBy')
            ->get();

        foreach ($livraisonsEnRetard as $livraison) {
            Mail::to($livraison->commande->demandeDevis->createdBy->email)
                ->send(new RelanceLivraisonEmail($livraison));

            Notification::make()
                ->title('📦 Relance livraison en retard')
                ->body('Livraison attendue depuis plus de 7 jours - Merci de confirmer réception')
                ->warning()
                ->sendToDatabase([$livraison->commande->demandeDevis->createdBy]);
        }

        Log::info('Relances livraisons envoyées', ['count' => $livraisonsEnRetard->count()]);
    }
}

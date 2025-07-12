<?php
namespace App\Jobs;

use App\Models\{Livraison, User};
use App\Mail\RelanceLivraisonEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Mail, Log};
use Filament\Notifications\Notification;

class RelanceLivraisonEnRetard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('🔄 Début job RelanceLivraisonEnRetard');

        // 📅 Livraisons en retard : pas de confirmation sous 7 jours
        $livraisonsEnRetard = Livraison::with(['commande.demandeDevis.createdBy'])
            ->where('statut_reception', 'en_attente')
            ->where('date_livraison_prevue', '<', now()->subDays(7))
            ->whereDoesntHave('media', function ($query) {
                $query->where('collection_name', 'bons_livraison');
            })
            ->get();

        $countRelances = 0;

        foreach ($livraisonsEnRetard as $livraison) {
            $serviceDemandeur = $livraison->commande->demandeDevis->createdBy;
            
            if (!$serviceDemandeur) {
                Log::warning("Service demandeur introuvable pour livraison {$livraison->id}");
                continue;
            }

            // 📧 Email relance personnalisé
            try {
                Mail::to($serviceDemandeur->email)
                    ->queue(new RelanceLivraisonEmail($livraison));

                // 🔔 Notification Filament dans l'interface
                Notification::make()
                    ->title('📦 Relance livraison en retard')
                    ->body("Livraison attendue depuis plus de 7 jours - Merci de confirmer réception")
                    ->warning()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('voir')
                            ->label('Voir livraison')
                            ->url("/admin/livraisons/{$livraison->id}")
                            ->button()
                    ])
                    ->sendToDatabase([$serviceDemandeur]);

                $countRelances++;
                
                Log::info("Relance envoyée", [
                    'livraison_id' => $livraison->id,
                    'user_email' => $serviceDemandeur->email,
                    'jours_retard' => now()->diffInDays($livraison->date_livraison_prevue)
                ]);

            } catch (\Exception $e) {
                Log::error("Erreur envoi relance livraison {$livraison->id}: " . $e->getMessage());
            }
        }

        // 📊 Log final avec statistiques
        Log::info("✅ Job RelanceLivraisonEnRetard terminé", [
            'livraisons_en_retard' => $livraisonsEnRetard->count(),
            'relances_envoyees' => $countRelances,
            'date_execution' => now()->toDateTimeString()
        ]);

        // 🚨 Notification admin si beaucoup de retards
        if ($countRelances > 5) {
            $adminUsers = User::role('admin')->get();
            if ($adminUsers->count() > 0) {
                Notification::make()
                    ->title('⚠️ Alerte retards livraisons')
                    ->body("{$countRelances} relances envoyées - Vérifier suivi fournisseurs")
                    ->danger()
                    ->sendToDatabase($adminUsers);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('❌ Échec job RelanceLivraisonEnRetard: ' . $exception->getMessage());
    }
}

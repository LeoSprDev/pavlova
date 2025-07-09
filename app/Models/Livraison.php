<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Livraison extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'commande_id',
        'date_livraison_prevue',
        'date_livraison_reelle',
        'statut_reception',
        'conforme',
        'anomalies',
        'actions_correctives',
        'verifie_par',
        'bon_livraison',
        'photos_reception',
    ];

    protected $casts = [
        'date_livraison_prevue' => 'date',
        'date_livraison_reelle' => 'date',
        'conforme' => 'boolean',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    protected static function booted(): void
    {
        static::updating(function (Livraison $livraison) {
            if ($livraison->isDirty('conforme') && $livraison->conforme) {
                $livraison->updateBudgetReel();
                $livraison->sendNotificationLivraisonConforme();
            }

            if ($livraison->isDirty('anomalies') && !empty($livraison->anomalies)) {
                $livraison->sendAlerteAnomalies();
            }

            if ($livraison->isDirty('statut_reception') && $livraison->statut_reception === 'recu_conforme') {
                $livraison->finaliserDemandeDevis();
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('bon_livraison')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->singleFile()
            ->useFallbackUrl('/images/no-delivery.pdf');

        $this->addMediaCollection('photos_reception')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    public function updateBudgetReel(): void
    {
        $demandeDevis = $this->commande->demandeDevis;
        $budgetLigne = $demandeDevis->budgetLigne;

        $montantReel = $demandeDevis->prix_fournisseur_final ?? $demandeDevis->prix_total_ttc;
        $budgetLigne->increment('montant_depense_reel', $montantReel);

        Log::info("Budget mis à jour automatiquement", [
            'budget_ligne_id' => $budgetLigne->id,
            'montant_ajoute' => $montantReel,
            'nouveau_total' => $budgetLigne->montant_depense_reel
        ]);
    }

    public function sendNotificationLivraisonConforme(): void
    {
        $demandeDevis = $this->commande->demandeDevis;

        if ($demandeDevis->createdBy) {
            Mail::to($demandeDevis->createdBy->email)
                ->send(new \App\Mail\LivraisonConformeConfirmeeEmail($this));
        }

        Notification::make()
            ->title('✅ Livraison conforme validée')
            ->body("Livraison {$this->id} - Budget automatiquement mis à jour : {$demandeDevis->prix_fournisseur_final}€")
            ->success()
            ->sendToDatabase(User::role('responsable-budget')->get());
    }

    public function sendAlerteAnomalies(): void
    {
        Notification::make()
            ->title('⚠️ Anomalies livraison détectées')
            ->body("Livraison {$this->id} : {$this->anomalies}")
            ->warning()
            ->sendToDatabase(User::role('responsable-budget')->get());
    }

    public function finaliserDemandeDevis(): void
    {
        $this->commande->demandeDevis->update([
            'statut' => 'delivered',
            'date_finalisation' => now()
        ]);
    }
}

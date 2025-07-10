<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\{Log, Mail};
use App\Models\User;
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
        'bons_livraison',
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
        // AUTOMATISATION WORKFLOW COMPLÈTE
        static::updating(function (Livraison $livraison) {
            // 🔄 Si marqué conforme → déclencher finalisation automatique
            if ($livraison->isDirty('conforme') && $livraison->conforme) {
                $livraison->finaliserLivraisonComplete();
            }

            // ⚠️ Si anomalies détectées → alertes immédiates
            if ($livraison->isDirty('anomalies') && !empty($livraison->anomalies)) {
                $livraison->envoyerAlerteAnomalies();
            }

            // 📦 Si statut = reçu_conforme + bon livraison → workflow final
            if ($livraison->isDirty('statut_reception') &&
                $livraison->statut_reception === 'recu_conforme' &&
                $livraison->getMedia('bons_livraison')->count() > 0) {
                $livraison->declencherWorkflowFinal();
            }
        });

        // 🔔 Après création livraison → programmer relances
        static::created(function (Livraison $livraison) {
            // Job relance dans 7 jours si pas de confirmation
            \App\Jobs\RelanceLivraisonEnRetard::dispatch()
                ->delay(now()->addDays(7));
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('bons_livraison')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->singleFile()
            ->useFallbackUrl('/images/no-delivery.pdf');

        $this->addMediaCollection('photos_reception')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    public function finaliserLivraisonComplete(): void
    {
        Log::info("🏁 Finalisation livraison complète", ['livraison_id' => $this->id]);

        try {
            // 1. Mise à jour budget automatique avec prix fournisseur final
            $this->updateBudgetAvecPrixFournisseur();

            // 2. Finaliser statut demande de devis
            $this->finaliserDemandeDevis();

            // 3. Notifications à tous les acteurs
            $this->envoyerNotificationsFinales();

            // 4. Log traçabilité complète
            $this->creerLogFinalisationComplete();

        } catch (\Exception $e) {
            Log::error("Erreur finalisation livraison {$this->id}: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateBudgetAvecPrixFournisseur(): void
    {
        $demandeDevis = $this->commande->demandeDevis;
        $budgetLigne = $demandeDevis->budgetLigne;

        // 💰 Utiliser prix fournisseur final ou prix initial
        $montantReel = $demandeDevis->prix_fournisseur_final ?? $demandeDevis->prix_total_ttc;

        // ✅ Mise à jour atomique budget
        $ancienMontant = $budgetLigne->montant_depense_reel;
        $budgetLigne->increment('montant_depense_reel', $montantReel);

        Log::info("💰 Budget mis à jour automatiquement", [
            'budget_ligne_id' => $budgetLigne->id,
            'ancien_montant' => $ancienMontant,
            'montant_ajoute' => $montantReel,
            'nouveau_total' => $budgetLigne->fresh()->montant_depense_reel,
            'livraison_id' => $this->id
        ]);
    }

    public function finaliserDemandeDevis(): void
    {
        $this->commande->demandeDevis->update([
            'statut' => 'delivered',
            'date_finalisation' => now(),
            'finalise_par' => auth()->id(),
            'commentaire_finalisation' => "Livraison conforme validée automatiquement"
        ]);

        Log::info("✅ Demande devis finalisée", [
            'demande_id' => $this->commande->demandeDevis->id,
            'livraison_id' => $this->id
        ]);
    }

    public function envoyerNotificationsFinales(): void
    {
        $demandeDevis = $this->commande->demandeDevis;

        // 📧 Email service demandeur original
        Mail::to($demandeDevis->createdBy->email)
            ->queue(new \App\Mail\LivraisonConformeConfirmeeEmail($this));

        // 🔔 Notification responsable budget
        $responsablesBudget = User::role('responsable-budget')->get();
        foreach ($responsablesBudget as $responsable) {
            Notification::make()
                ->title('✅ Livraison conforme - Budget mis à jour')
                ->body("Livraison {$this->id} validée - Montant: {$demandeDevis->prix_fournisseur_final}€")
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('voir_budget')
                        ->label('Voir budget')
                        ->url("/admin/budget-lignes/{$demandeDevis->budget_ligne_id}")
                        ->button()
                ])
                ->sendToDatabase([$responsable]);
        }

        Log::info("📧 Notifications finales envoyées", ['livraison_id' => $this->id]);
    }

    public function envoyerAlerteAnomalies(): void
    {
        // 🚨 Alerte immédiate responsables budget
        $responsablesBudget = User::role('responsable-budget')->get();

        Notification::make()
            ->title('⚠️ Anomalies livraison détectées')
            ->body("Livraison {$this->id} : {$this->anomalies}")
            ->danger()
            ->actions([
                \Filament\Notifications\Actions\Action::make('voir_livraison')
                    ->label('Voir détails')
                    ->url("/admin/livraisons/{$this->id}")
                    ->button()
            ])
            ->sendToDatabase($responsablesBudget);

        Log::warning("⚠️ Anomalies livraison signalées", [
            'livraison_id' => $this->id,
            'anomalies' => $this->anomalies
        ]);
    }

    public function declencherWorkflowFinal(): void
    {
        // 🎯 Transition finale workflow 6 étapes
        $this->update([
            'statut_reception' => 'recu_conforme',
            'date_validation_finale' => now(),
            'workflow_complete' => true
        ]);

        Log::info("🎯 Workflow 6 étapes complété", [
            'livraison_id' => $this->id,
            'demande_id' => $this->commande->demandeDevis->id
        ]);
    }

    public function creerLogFinalisationComplete(): void
    {
        Log::info('✔️ Log finalisation complète enregistré', [
            'livraison_id' => $this->id,
            'user_id' => auth()->id()
        ]);
    }
}

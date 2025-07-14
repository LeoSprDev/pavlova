<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\{DB, Log, Mail};
use App\Models\User;
use Filament\Notifications\Notification;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
        'photos_reception' => 'array',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function verificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifie_par');
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

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(150)
            ->height(150)
            ->sharpen(10);
    }

    public function markAsConforme(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->update([
                'statut_reception' => 'conforme',
                'conforme' => true,
                'verifie_par' => $user->id,
                'date_verification' => now(),
            ]);

            $this->updateBudgetAvecPrixFinal();
            $this->finaliserDemandeDevis();
            $this->envoyerNotificationsFinales();
        });
    }

    public function finaliserLivraisonComplete(): void
    {
        Log::info("🏁 Finalisation livraison complète", ['livraison_id' => $this->id]);

        try {
            // 1. Mise à jour budget automatique avec prix fournisseur final
            $this->updateBudgetAvecPrixFinal();

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

    private function updateBudgetAvecPrixFinal(): void
    {
        try {
            $demandeDevis = $this->commande->demandeDevis;
            
            if (!$demandeDevis) {
                Log::warning("Demande de devis introuvable pour la livraison", ['livraison_id' => $this->id]);
                return;
            }
            
            $budgetLigne = $demandeDevis->budgetLigne;
            
            if (!$budgetLigne) {
                Log::warning("Ligne budget introuvable pour la demande", ['demande_id' => $demandeDevis->id]);
                return;
            }

            // 💰 Utiliser prix fournisseur final ou prix initial
            $montantReel = $demandeDevis->prix_fournisseur_final ?? $demandeDevis->prix_total_ttc ?? 0;

            if ($montantReel > 0) {
                $budgetLigne->decrement('montant_engage', $demandeDevis->prix_total_ttc ?? 0);
                $budgetLigne->increment('montant_depense_reel', $montantReel);

                Log::info("💰 Budget mis à jour automatiquement", [
                    'budget_ligne_id' => $budgetLigne->id,
                    'montant_ajoute' => $montantReel,
                    'nouveau_total' => $budgetLigne->fresh()->montant_depense_reel,
                    'livraison_id' => $this->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Erreur mise à jour budget", [
                'livraison_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function finaliserDemandeDevis(): void
    {
        try {
            $demandeDevis = $this->commande->demandeDevis;
            
            if (!$demandeDevis) {
                Log::warning("Impossible de finaliser: demande de devis introuvable", ['livraison_id' => $this->id]);
                return;
            }

            $demandeDevis->update([
                'statut' => 'delivered',
                'date_finalisation' => now(),
                'finalise_par' => auth()->id() ?? 1,
                'commentaire_finalisation' => "Livraison conforme validée automatiquement"
            ]);

            Log::info("✅ Demande devis finalisée", [
                'demande_id' => $demandeDevis->id,
                'livraison_id' => $this->id
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur finalisation demande devis", [
                'livraison_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function envoyerNotificationsFinales(): void
    {
        try {
            $demandeDevis = $this->commande->demandeDevis;

            // 📧 Email service demandeur original (avec vérification)
            if ($demandeDevis && $demandeDevis->createdBy && $demandeDevis->createdBy->email) {
                try {
                    Mail::to($demandeDevis->createdBy->email)
                        ->queue(new \App\Mail\LivraisonConformeConfirmeeEmail($this));
                } catch (\Exception $e) {
                    Log::warning("Impossible d'envoyer l'email au créateur de la demande", [
                        'livraison_id' => $this->id,
                        'demande_id' => $demandeDevis->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                Log::warning("Créateur de demande introuvable pour l'email", [
                    'livraison_id' => $this->id,
                    'demande_id' => $demandeDevis->id ?? 'unknown'
                ]);
            }

            // 🔔 Notification responsable budget
            $responsablesBudget = User::role('responsable-budget')->get();
            foreach ($responsablesBudget as $responsable) {
                try {
                    Notification::make()
                        ->title('✅ Livraison conforme - Budget mis à jour')
                        ->body("Livraison {$this->id} validée - Montant: " . ($demandeDevis->prix_fournisseur_final ?? $demandeDevis->prix_total_ttc ?? 'N/A') . "€")
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('voir_budget')
                                ->label('Voir budget')
                                ->url("/admin/budget-lignes/{$demandeDevis->budget_ligne_id}")
                                ->button()
                        ])
                        ->sendToDatabase([$responsable]);
                } catch (\Exception $e) {
                    Log::warning("Impossible d'envoyer la notification au responsable budget", [
                        'livraison_id' => $this->id,
                        'user_id' => $responsable->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info("📧 Notifications finales envoyées", ['livraison_id' => $this->id]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi des notifications finales", [
                'livraison_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
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

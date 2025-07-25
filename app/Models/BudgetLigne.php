<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Models\User;
use App\Models\BudgetWarning;
use App\Services\WorkflowNotificationService;

class BudgetLigne extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'date_prevue',
        'intitule',
        'nature',
        'fournisseur_prevu',
        'base_calcul',
        'quantite',
        'montant_ht_prevu',
        'montant_ttc_prevu',
        'montant_depense_reel', // Added as per table schema, though often calculated
        'categorie',
        'type_depense',
        'commentaire_service',
        'commentaire_budget',
        'valide_budget',
        // 'depassement' is a virtual column in migration, not fillable
    ];

    protected $casts = [
        'date_prevue' => 'date',
        'montant_ht_prevu' => 'decimal:2',
        'montant_ttc_prevu' => 'decimal:2',
        'montant_depense_reel' => 'decimal:2',
        'quantite' => 'integer',
        'valide_budget' => 'string', // 'oui'|'non'|'potentiellement'
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function demandesAssociees(): HasMany
    {
        return $this->hasMany(DemandeDevis::class, 'budget_ligne_id');
    }

    public function demandesApprouvees(): HasMany
    {
        // The prompt uses 'delivered' for DemandeDevis status that counts towards consumption
        return $this->hasMany(DemandeDevis::class, 'budget_ligne_id')
                   ->where('statut', 'delivered');
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(BudgetEngagement::class);
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(BudgetWarning::class);
    }

    protected static function booted(): void
    {
        // Global scope for data partitioning based on user role
        // This scope is also defined in Filament Resource, ensure consistency or choose one place
        if (Auth::check() && Auth::user()->hasRole('service-demandeur')) {
            static::addGlobalScope('service', function (Builder $builder) {
                $builder->where('service_id', Auth::user()->service_id);
            });
        }
    }

    public function calculateBudgetRestant(): float
    {
        // Using montant_depense_reel if it's reliably updated,
        // otherwise sum from demandesApprouvees is safer.
        // The prompt uses demandesApprouvees sum.
        $depense_reelle = $this->demandesApprouvees()->sum('prix_total_ttc');
        return (float)$this->montant_ht_prevu - (float)$depense_reelle;
    }

    public function getTauxConsommation(): float
    {
        $depense = $this->demandesApprouvees()->sum('prix_total_ttc');
        return $this->montant_ht_prevu > 0 ? ((float)$depense / (float)$this->montant_ht_prevu) * 100 : 0;
    }

    public function getTauxUtilisation(): float
    {
        $utilise = $this->montant_depense_reel + $this->montant_engage;
        return $this->montant_ht_prevu > 0 ? ($utilise / $this->montant_ht_prevu) * 100 : 0;
    }

    public function isDepassementBudget(): bool
    {
        return $this->calculateBudgetRestant() < 0;
    }

    public function canAcceptNewDemande(float $montant): bool
    {
        return $this->verifierDisponibilite($montant);
    }

    // SCOPES
    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('valide_budget', 'oui');
    }

    public function scopeEnDepassement(Builder $query): Builder
    {
        // This logic relies on summing up approved demands rather than the virtual 'depassement' column
        // to ensure it's based on the same calculation as calculateBudgetRestant.
        return $query->whereHas('demandesApprouvees', function($q) {
            // Subquery to sum prix_total_ttc for delivered demands
        }, '>=', 1) // Placeholder for actual subquery sum comparison
        ->where(function($subQuery) {
            $subQuery->whereRaw('montant_ht_prevu < (SELECT SUM(prix_total_ttc) FROM demande_devis WHERE demande_devis.budget_ligne_id = budget_lignes.id AND demande_devis.statut = ?)', ['delivered']);
        });
    }

    // This method is used in Filament table, ensures 'montant_depense_reel' is accurate if not directly fillable
    public function getMontantDepenseReelCalculatedAttribute(): float
    {
        return (float) $this->demandesApprouvees()->sum('prix_total_ttc');
    }

    public function engagerBudget(float $montant, DemandeDevis $demande): bool
    {
        $disponible = $this->montant_ht_prevu - $this->montant_depense_reel - $this->montant_engage;
        $depassement = 0;
        if ($disponible < $montant) {
            $depassement = $montant - $disponible;
        }

        $this->increment('montant_engage', $montant);

        $engagement = $this->engagements()->create([
            'demande_devis_id' => $demande->id,
            'montant' => $montant,
            'date_engagement' => now(),
            'statut' => 'engage',
        ]);

        if ($depassement > 0) {
            $warning = $this->warnings()->create([
                'demande_devis_id' => $demande->id,
                'montant_engage' => $montant,
                'montant_depassement' => $depassement,
                'message' => 'Dépassement budget de ' . $depassement . ' €',
            ]);
            app(\App\Services\WorkflowNotificationService::class)->notifyBudgetWarning($warning);
        } elseif ($this->getTauxUtilisation() > 90) {
            $this->alerteSeuil();
        }

        return (bool)$engagement;
    }

    public function desengagerBudget(DemandeDevis $demande): bool
    {
        $engagement = $this->engagements()
            ->where('demande_devis_id', $demande->id)
            ->where('statut', 'engage')
            ->first();

        if ($engagement) {
            $this->decrement('montant_engage', $engagement->montant);
            $engagement->update([
                'statut' => 'degage',
                'date_degagement' => now(),
            ]);
            return true;
        }

        return false;
    }

    public function checkBudgetWarnings(): bool
    {
        return $this->warnings()->exists();
    }

    public function verifierDisponibilite(float $montant): bool
    {
        $disponible = $this->montant_ht_prevu - $this->montant_depense_reel - $this->montant_engage;
        return $disponible >= $montant;
    }

    private function alerteSeuil(): void
    {
        $responsables = User::role('responsable-budget')->get();

        foreach ($responsables as $user) {
            Notification::make()
                ->title('Seuil budget critique')
                ->body("Budget {$this->intitule} : " . round($this->getTauxUtilisation(), 1) . '% utilisé')
                ->warning()
                ->persistent()
                ->sendToDatabase($user);
        }
    }
}

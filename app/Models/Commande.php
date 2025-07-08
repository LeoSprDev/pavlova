<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Commande extends Model
{
    use HasFactory;

    protected $fillable = [
        'demande_devis_id',
        'numero_commande', // Auto-generated
        'date_commande',
        'commanditaire',
        'statut',
        'date_livraison_prevue',
        'montant_reel',
        'fournisseur_contact',
        'fournisseur_email',
        'conditions_paiement',
        'delai_livraison',
        'nb_relances',
    ];

    protected $casts = [
        'date_commande' => 'date',
        'date_livraison_prevue' => 'date',
        'montant_reel' => 'decimal:2',
        'nb_relances' => 'integer',
    ];

    public function demandeDevis(): BelongsTo
    {
        return $this->belongsTo(DemandeDevis::class);
    }

    public function livraison(): HasOne
    {
        return $this->hasOne(Livraison::class);
    }

    public function relances(): HasMany
    {
        // Assuming a RelanceFournisseur model will be created
        return $this->hasMany(RelanceFournisseur::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($commande) {
            if (empty($commande->numero_commande)) {
                $annee = date('Y');
                // Ensure the sequence is correctly scoped if there's a lot of traffic.
                // For most cases, this count is fine. Consider a dedicated sequence table for very high volume.
                $sequence = static::whereYear('created_at', $annee)->count() + 1;
                $commande->numero_commande = sprintf('CMD-%s-%04d', $annee, $sequence);
            }
        });

        // Update DemandeDevis and BudgetLigne upon commande/livraison changes if necessary
        // For example, when a commande is marked 'livree' and 'conforme'.
        // This is often handled at the point of action (e.g., when a Livraison is confirmed).
    }

    public function isEnRetard(): bool
    {
        // A command is late if delivery date is past AND no delivery record exists OR delivery is not 'recue'
        return $this->date_livraison_prevue < now() &&
               (!$this->livraison || $this->livraison->statut_reception !== 'recue');
    }

    public function joursRetard(): int
    {
        if (!$this->isEnRetard()) {
            return 0;
        }
        // Calculate difference from livraison_prevue to now, or to actual livraison if available and later
        $referenceDate = $this->livraison && $this->livraison->date_livraison > $this->date_livraison_prevue ?
                         $this->livraison->date_livraison :
                         now();

        return $referenceDate->diffInDays($this->date_livraison_prevue);
    }
}

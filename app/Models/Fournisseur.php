<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fournisseur extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'nom_commercial',
        'siret',
        'email',
        'telephone',
        'adresse',
        'statut',
        'last_used_at',
        'metadata',
        'ca_limite_annuel',
        'ca_limite_mensuel',
        'limite_active',
        'note_limite',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'metadata' => 'array',
        'limite_active' => 'boolean',
        'ca_limite_annuel' => 'decimal:2',
        'ca_limite_mensuel' => 'decimal:2',
    ];

    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeDevis::class, 'fournisseur_propose', 'nom');
    }

    public function getCAAttribute(): float
    {
        return $this->demandes()
            ->whereIn('statut', ['delivered_confirmed', 'ordered'])
            ->sum('prix_total_ttc');
    }

    public function getCAMensuel(int $annee = null, int $mois = null): float
    {
        $annee = $annee ?? now()->year;
        $mois = $mois ?? now()->month;
        
        return $this->demandes()
            ->whereIn('statut', ['delivered_confirmed', 'ordered'])
            ->whereYear('created_at', $annee)
            ->whereMonth('created_at', $mois)
            ->sum('prix_total_ttc');
    }

    public function getCAnnuel(int $annee = null): float
    {
        $annee = $annee ?? now()->year;
        
        return $this->demandes()
            ->whereIn('statut', ['delivered_confirmed', 'ordered'])
            ->whereYear('created_at', $annee)
            ->sum('prix_total_ttc');
    }

    public function canAcceptCommande(float $montant): bool
    {
        if (!$this->limite_active) {
            return true;
        }

        if ($this->ca_limite_mensuel && ($this->getCAMensuel() + $montant) > $this->ca_limite_mensuel) {
            return false;
        }

        if ($this->ca_limite_annuel && ($this->getCAnnuel() + $montant) > $this->ca_limite_annuel) {
            return false;
        }

        return true;
    }

    public function getLimiteStatusAttribute(): string
    {
        if (!$this->limite_active) {
            return 'Aucune limite';
        }

        $caMensuel = $this->getCAMensuel();
        $caAnnuel = $this->getCAnnuel();

        if ($this->ca_limite_mensuel && $caMensuel >= $this->ca_limite_mensuel) {
            return 'Limite mensuelle atteinte';
        }

        if ($this->ca_limite_annuel && $caAnnuel >= $this->ca_limite_annuel) {
            return 'Limite annuelle atteinte';
        }

        $pourcentageMensuel = $this->ca_limite_mensuel ? ($caMensuel / $this->ca_limite_mensuel) * 100 : 0;
        $pourcentageAnnuel = $this->ca_limite_annuel ? ($caAnnuel / $this->ca_limite_annuel) * 100 : 0;

        $pourcentageMax = max($pourcentageMensuel, $pourcentageAnnuel);

        if ($pourcentageMax > 90) {
            return 'Limite critique (>90%)';
        } elseif ($pourcentageMax > 75) {
            return 'Limite élevée (>75%)';
        } elseif ($pourcentageMax > 50) {
            return 'Limite modérée (>50%)';
        }

        return 'Dans les limites';
    }
}

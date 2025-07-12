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
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'metadata' => 'array',
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
}

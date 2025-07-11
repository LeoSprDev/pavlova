<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetEngagement extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_ligne_id',
        'demande_devis_id',
        'montant',
        'date_engagement',
        'date_degagement',
        'statut',
    ];

    protected $casts = [
        'date_engagement' => 'datetime',
        'date_degagement' => 'datetime',
        'montant' => 'decimal:2',
    ];

    public function budgetLigne(): BelongsTo
    {
        return $this->belongsTo(BudgetLigne::class);
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeDevis::class, 'demande_devis_id');
    }
}

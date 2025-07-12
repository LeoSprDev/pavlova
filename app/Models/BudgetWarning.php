<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetWarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_ligne_id',
        'demande_devis_id',
        'montant_engage',
        'montant_depassement',
        'message',
    ];

    protected $casts = [
        'montant_engage' => 'decimal:2',
        'montant_depassement' => 'decimal:2',
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

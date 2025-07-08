<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TacheAdministrative extends Model
{
    use HasFactory;

    protected $table = 'tache_administratives'; // Explicit table name

    protected $fillable = [
        'titre',
        'description',
        'priorite',
        'assigne_a', // user_id
        'budget_ligne_id',
        'demande_devis_id',
        'statut',
        'date_echeance',
    ];

    protected $casts = [
        'date_echeance' => 'datetime',
    ];

    public function assigneA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigne_a');
    }

    public function budgetLigne(): BelongsTo
    {
        return $this->belongsTo(BudgetLigne::class);
    }

    public function demandeDevis(): BelongsTo
    {
        return $this->belongsTo(DemandeDevis::class);
    }
}

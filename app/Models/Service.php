<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'code',
        'responsable_email',
        'budget_annuel_alloue',
        'description',
    ];

    protected $casts = [
        'budget_annuel_alloue' => 'decimal:2',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function budgetLignes(): HasMany
    {
        return $this->hasMany(BudgetLigne::class);
    }

    public function demandesDevis(): HasMany
    {
        return $this->hasMany(DemandeDevis::class, 'service_demandeur_id');
    }

    // Helper to get the main manager of the service
    public function responsable() {
        return $this->users()->where('is_service_responsable', true)->first();
    }
}

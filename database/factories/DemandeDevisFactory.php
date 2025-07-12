<?php
namespace Database\Factories;

use App\Models\DemandeDevis;
use Illuminate\Database\Eloquent\Factories\Factory;

class DemandeDevisFactory extends Factory
{
    protected $model = DemandeDevis::class;

    public function definition()
    {
        return [
            'service_demandeur_id' => \App\Models\Service::factory(),
            'budget_ligne_id' => \App\Models\BudgetLigne::factory(),
            'denomination' => $this->faker->word(),
            'justification_besoin' => $this->faker->sentence(),
            'date_besoin' => now()->addWeek(),
            'prix_total_ttc' => 100,
            'quantite' => 1,
            'prix_unitaire_ht' => 100,
            'statut' => 'pending',
            'created_by' => \App\Models\User::factory(),
        ];
    }
}

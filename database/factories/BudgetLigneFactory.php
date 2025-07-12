<?php
namespace Database\Factories;

use App\Models\BudgetLigne;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetLigneFactory extends Factory
{
    protected $model = BudgetLigne::class;

    public function definition()
    {
        return [
            'service_id' => \App\Models\Service::factory(),
            'intitule' => $this->faker->sentence(3),
            'date_prevue' => now()->addMonth(),
            'nature' => 'service',
            'fournisseur_prevu' => $this->faker->company(),
            'base_calcul' => 'estimation',
            'quantite' => 1,
            'montant_ht_prevu' => 1000,
            'montant_ttc_prevu' => 1200,
            'categorie' => 'service',
            'type_depense' => 'OPEX',
            'valide_budget' => 'oui',
        ];
    }
}

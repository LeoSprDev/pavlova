<?php
namespace Database\Factories;

use App\Models\Commande;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommandeFactory extends Factory
{
    protected $model = Commande::class;

    public function definition()
    {
        return [
            'demande_devis_id' => \App\Models\DemandeDevis::factory(),
            'numero_commande' => 'CMD-' . $this->faker->unique()->numberBetween(1000, 9999),
            'date_commande' => now(),
            'date_livraison_prevue' => now()->addWeek(),
            'commanditaire' => 'system',
            'montant_reel' => 0,
            'nb_relances' => 0,
        ];
    }
}

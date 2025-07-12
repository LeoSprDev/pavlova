<?php
namespace Database\Factories;

use App\Models\Livraison;
use Illuminate\Database\Eloquent\Factories\Factory;

class LivraisonFactory extends Factory
{
    protected $model = Livraison::class;

    public function definition()
    {
        return [
            'commande_id' => \App\Models\Commande::factory(),
            'date_livraison_prevue' => now()->addWeek(),
            'statut_reception' => 'en_attente',
            'conforme' => false,
            'verifie_par' => null,
        ];
    }
}

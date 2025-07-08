<?php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\BudgetLigne;

class BudgetSeuilDepasse
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BudgetLigne $ligne,
        public float $montantDepassement,
        public string $typeSeuil = 'depassement'
    ) {}
}

<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\BudgetLigne;
use App\Events\BudgetSeuilDepasse;

class VerificationBudgetsQuotidienne implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        BudgetLigne::where('valide_budget', 'oui')->chunk(100, function ($lignes) {
            foreach ($lignes as $ligne) {
                $budgetRestant = $ligne->calculateBudgetRestant();
                $tauxConsommation = $ligne->getTauxConsommation();

                // Alertes selon seuils
                if ($budgetRestant < 0) {
                    event(new BudgetSeuilDepasse($ligne, abs($budgetRestant), 'depassement'));
                } elseif ($tauxConsommation >= 95) {
                    event(new BudgetSeuilDepasse($ligne, $budgetRestant, 'alerte_95'));
                } elseif ($tauxConsommation >= 90) {
                    event(new BudgetSeuilDepasse($ligne, $budgetRestant, 'alerte_90'));
                }
            }
        });
    }
}

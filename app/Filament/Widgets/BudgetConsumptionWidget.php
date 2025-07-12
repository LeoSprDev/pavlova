<?php
namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\BudgetLigne;
use App\Models\DemandeDevis;

class BudgetConsumptionWidget extends ChartWidget
{
    protected static ?string $heading = 'Consommation Annuelle';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels = collect(range(1, 12))->map(fn($m) => now()->month($m)->format('M'));
        $data = [];
        foreach(range(1,12) as $month) {
            $budget = BudgetLigne::whereMonth('date_prevue', $month)->sum('montant_ttc_prevu');
            $consomme = DemandeDevis::whereMonth('date_finalisation', $month)
                ->where('statut', 'delivered_confirmed')
                ->sum('prix_total_ttc');
            $data[] = $budget > 0 ? round(($consomme / $budget) * 100,1) : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Taux de consommation %',
                    'data' => $data,
                    'backgroundColor' => '#2563eb',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

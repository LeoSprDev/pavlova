<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Filament\Widgets\ChartWidget;

class BudgetHeatmapWidget extends ChartWidget
{
    protected static ?string $heading = '🔥 Heatmap Consommation Budget';

    protected static string $color = 'info';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $services = Service::all();
        $months = collect(range(1, 12))->map(fn ($m) => now()->month($m)->format('M'));

        $datasets = [];
        foreach ($services as $service) {
            $data = [];
            foreach (range(1, 12) as $month) {
                $consommation = $service->budgetLignes()
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', $month)
                    ->sum('montant_depense_reel');
                $data[] = $consommation;
            }

            $datasets[] = [
                'label' => $service->nom,
                'data' => $data,
                'backgroundColor' => $this->getServiceColor($service),
                'borderRadius' => 4,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'plugins' => [
                'legend' => ['position' => 'top'],
            ],
            'animation' => [
                'duration' => 2000,
                'easing' => 'easeOutQuart',
            ],
        ];
    }

    private function getServiceColor(Service $service): string
    {
        $colors = ['#3b82f6', '#8b5cf6', '#ef4444', '#10b981', '#f59e0b'];

        return $colors[$service->id % count($colors)];
    }
}

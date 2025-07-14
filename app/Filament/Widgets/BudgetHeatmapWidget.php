<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class BudgetHeatmapWidget extends ChartWidget
{
    protected static ?string $heading = '🔥 Heatmap Consommation Budget';

    protected static string $color = 'info';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user?->hasAnyRole(['administrateur', 'responsable-budget', 'service-achat']) ?? false;
    }

    protected function getData(): array
    {
        $user = Auth::user();
        
        // Si c'est un responsable de service ou agent, ne montrer que son service
        if ($user && $user->hasAnyRole(['responsable-service', 'agent-service']) && $user->service_id) {
            $services = Service::where('id', $user->service_id)->get();
        } else {
            // Admin, responsable budget, service achat voient tous les services
            $services = Service::all();
        }
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

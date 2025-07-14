<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Fournisseur;
use App\Models\DemandeDevis;
use Illuminate\Support\Facades\DB;

class TopFournisseursWidget extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Fournisseurs par CA';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        try {
            $topFournisseurs = DemandeDevis::select('fournisseur_propose')
                ->selectRaw('SUM(prix_total_ttc) as total_ca')
                ->whereIn('statut', ['delivered', 'ordered'])
                ->whereNotNull('fournisseur_propose')
                ->groupBy('fournisseur_propose')
                ->orderByDesc('total_ca')
                ->limit(5)
                ->get();

            if ($topFournisseurs->isEmpty()) {
                return [
                    'datasets' => [
                        [
                            'label' => 'Chiffre d\'affaires',
                            'data' => [0],
                            'backgroundColor' => ['#f3f4f6'],
                        ],
                    ],
                    'labels' => ['Aucun fournisseur'],
                ];
            }

            $labels = $topFournisseurs->pluck('fournisseur_propose')->toArray();
            $data = $topFournisseurs->pluck('total_ca')->map(fn($value) => round($value, 2))->toArray();

            return [
                'datasets' => [
                    [
                        'label' => 'Chiffre d\'affaires (€)',
                        'data' => $data,
                        'backgroundColor' => [
                            '#f59e0b',
                            '#3b82f6',
                            '#10b981',
                            '#ef4444',
                            '#8b5cf6',
                        ],
                    ],
                ],
                'labels' => $labels,
            ];
        } catch (\Exception $e) {
            \Log::warning('Error in TopFournisseursWidget: ' . $e->getMessage());
            
            return [
                'datasets' => [
                    [
                        'label' => 'Erreur',
                        'data' => [0],
                        'backgroundColor' => ['#ef4444'],
                    ],
                ],
                'labels' => ['Erreur de chargement'],
            ];
        }
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Commande;
use App\Models\Livraison;
use Illuminate\Support\Facades\DB;

class CommandesLivraisonsWidget extends ChartWidget
{
    protected static ?string $heading = 'Commandes vs Livraisons (6 derniers mois)';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        try {
            $months = collect();
            $commandesData = [];
            $livraisonsData = [];

            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthLabel = $date->format('M Y');
                $months->push($monthLabel);

                // Compter les commandes créées ce mois
                $commandesCount = Commande::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                $commandesData[] = $commandesCount;

                // Compter les livraisons effectuées ce mois
                $livraisonsCount = Livraison::whereYear('date_livraison_effective', $date->year)
                    ->whereMonth('date_livraison_effective', $date->month)
                    ->count();
                $livraisonsData[] = $livraisonsCount;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Commandes',
                        'data' => $commandesData,
                        'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'borderWidth' => 2,
                        'fill' => false,
                    ],
                    [
                        'label' => 'Livraisons',
                        'data' => $livraisonsData,
                        'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                        'borderColor' => 'rgb(16, 185, 129)',
                        'borderWidth' => 2,
                        'fill' => false,
                    ],
                ],
                'labels' => $months->toArray(),
            ];
        } catch (\Exception $e) {
            \Log::warning('Error in CommandesLivraisonsWidget: ' . $e->getMessage());
            
            return [
                'datasets' => [
                    [
                        'label' => 'Erreur',
                        'data' => [0, 0, 0, 0, 0, 0],
                        'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                        'borderColor' => 'rgb(239, 68, 68)',
                    ],
                ],
                'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
            ];
        }
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
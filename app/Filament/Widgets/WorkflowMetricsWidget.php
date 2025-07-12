<?php

namespace App\Filament\Widgets;

use App\Models\DemandeDevis;
use Filament\Widgets\ChartWidget;

class WorkflowMetricsWidget extends ChartWidget
{
    protected static ?string $heading = '📈 Workflow Metrics';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = collect(range(1, 12))->map(fn ($m) => now()->month($m)->format('M'));
        $data = [];
        foreach (range(1, 12) as $m) {
            $data[] = DemandeDevis::whereMonth('created_at', $m)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Demandes',
                    'data' => $data,
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

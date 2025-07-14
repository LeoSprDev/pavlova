<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BudgetHeatmapWidget;
use App\Filament\Widgets\WorkflowMetricsWidget;
use App\Filament\Widgets\ExecutiveStatsWidget;
use App\Filament\Widgets\BudgetConsumptionWidget;
use Filament\Pages\Page;

class ExecutiveDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.executive-dashboard';

    protected static ?string $title = 'Dashboard Exécutif';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole(['administrateur', 'responsable-budget', 'service-achat']) ?? false;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExecutiveStatsWidget::class,
            BudgetHeatmapWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            BudgetConsumptionWidget::class,
            WorkflowMetricsWidget::class,
        ];
    }
}

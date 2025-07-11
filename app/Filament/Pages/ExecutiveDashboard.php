<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BudgetHeatmapWidget;
use App\Filament\Widgets\WorkflowMetricsWidget;
use Filament\Pages\Page;

class ExecutiveDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.executive-dashboard';

    protected static ?string $title = 'Dashboard Exécutif';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('responsable-direction');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BudgetHeatmapWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            WorkflowMetricsWidget::class,
        ];
    }
}

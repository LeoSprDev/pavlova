<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\{BudgetStatsWidget, WorkflowTimelineWidget, NotificationCenterWidget, WorkflowKanbanWidget};

class ServiceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.service-dashboard';
    protected static ?string $title = 'Dashboard Service';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return optional(auth()->user())->hasAnyRole(['agent-service', 'responsable-service', 'administrateur']) ?? false;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BudgetStatsWidget::class,
            NotificationCenterWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            WorkflowKanbanWidget::class,
            WorkflowTimelineWidget::class,
        ];
    }
}

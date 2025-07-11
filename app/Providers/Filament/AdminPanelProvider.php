<?php
namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationItem;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Pages;
use Filament\Widgets;
use Illuminate\Support\Facades\Auth;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->viteTheme([
                'resources/css/filament.css',
                'resources/css/filament-fixes.css',
                'resources/css/animations-polish.css',
            ])
            ->assets([
                Js::make('ux-intelligence', resource_path('js/ux-intelligence.js')),
            ])
            ->colors(['primary' => Color::Amber])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                \App\Filament\Widgets\BudgetStatsWidget::class,
                \App\Filament\Widgets\WorkflowTimelineWidget::class,
            ])
            ->topbar(false)
            ->navigationItems([
                NavigationItem::make('Dashboard')
                    ->url('/admin')
                    ->icon('heroicon-o-home'),
                NavigationItem::make('Demandes')
                    ->url('/admin/demande-devis')
                    ->icon('heroicon-o-document'),
            ]);
    }
}

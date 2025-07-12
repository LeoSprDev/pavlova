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
            ->middleware([
                'web',
                'auth:web',
            ])
            ->authGuard('web')
            ->assets([
                Css::make('filament-fixes', asset('css/filament-fixes.css')),
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
                    ->label('Dashboard')
                    ->url('/admin')
                    ->icon('heroicon-o-home')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.dashboard')),

                NavigationItem::make('Budget Lignes')
                    ->label('Budget Lignes')
                    ->url('/admin/budget-lignes')
                    ->icon('heroicon-o-banknotes')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.budget-lignes.*')),

                NavigationItem::make('Demandes Devis')
                    ->label('Demandes Devis')
                    ->url('/admin/demande-devis')
                    ->icon('heroicon-o-document-text')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.demande-devis.*')),

                NavigationItem::make('Commandes')
                    ->label('Commandes')
                    ->url('/admin/commandes')
                    ->icon('heroicon-o-shopping-cart')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.commandes.*')),
            ]);
    }
}

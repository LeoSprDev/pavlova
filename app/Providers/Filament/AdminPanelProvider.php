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
                \App\Filament\Widgets\TopFournisseursWidget::class,
                \App\Filament\Widgets\CommandesLivraisonsWidget::class,
                \App\Filament\Widgets\BudgetLignesTableWidget::class,
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
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.budget-lignes.*'))
                    ->visible(fn (): bool => Auth::check() && Auth::user()->hasAnyRole(['administrateur', 'responsable-budget', 'responsable-direction', 'responsable-service'])),

                NavigationItem::make('Demandes Devis')
                    ->label('Demandes Devis')
                    ->url('/admin/demande-devis')
                    ->icon('heroicon-o-document-text')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.demande-devis.*'))
                    ->visible(fn (): bool => Auth::check() && Auth::user()->hasAnyRole(['administrateur', 'agent-service', 'responsable-service', 'responsable-budget', 'service-achat'])),

                NavigationItem::make('Commandes')
                    ->label('Commandes')
                    ->url('/admin/commandes')
                    ->icon('heroicon-o-shopping-cart')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.commandes.*'))
                    ->visible(fn (): bool => Auth::check() && Auth::user()->hasAnyRole(['administrateur', 'service-achat', 'responsable-service'])),

                NavigationItem::make('Livraisons')
                    ->label('Livraisons')
                    ->url('/admin/livraisons')
                    ->icon('heroicon-o-truck')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.livraisons.*'))
                    ->visible(fn (): bool => Auth::check() && Auth::user()->hasAnyRole(['administrateur', 'service-achat', 'agent-service', 'responsable-service'])),

                NavigationItem::make('Services')
                    ->label('Services')
                    ->url('/admin/services')
                    ->icon('heroicon-o-building-office')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.services.*'))
                    ->visible(fn (): bool => Auth::check() && Auth::user()->hasAnyRole(['administrateur', 'responsable-budget', 'responsable-direction'])),

                NavigationItem::make('Utilisateurs')
                    ->label('Utilisateurs')
                    ->url('/admin/users')
                    ->icon('heroicon-o-users')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.users.*'))
                    ->visible(fn (): bool => Auth::check() && Auth::user()->hasAnyRole(['administrateur', 'responsable-service'])),

                NavigationItem::make('Rôles & Permissions')
                    ->label('Rôles & Permissions')
                    ->url('/admin/roles')
                    ->icon('heroicon-o-shield-check')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.roles.*'))
                    ->visible(fn (): bool => Auth::check() && Auth::user()->hasRole('administrateur')),
            ]);
    }
}

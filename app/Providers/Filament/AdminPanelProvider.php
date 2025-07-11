<?php
namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
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
            ->navigation(function () {
                $user = Auth::user();
                if (!$user) return [];

                $items = [];

                // Dashboard pour tous (URL sûre)
                $items[] = NavigationItem::make('Dashboard')
                    ->url('/admin')
                    ->icon('heroicon-o-home')
                    ->sort(1);

                // Navigation basique par rôle avec URLs sûres
                if ($user->hasAnyRole(['agent-service', 'service-demandeur'])) {
                    $items[] = NavigationGroup::make('Mon Espace')
                        ->items([
                            NavigationItem::make('Mes Demandes')
                                ->url('/admin/demande-devis')
                                ->icon('heroicon-o-document-text'),
                            NavigationItem::make('Nouvelle Demande')
                                ->url('/admin/demande-devis/create')
                                ->icon('heroicon-o-plus-circle'),
                        ])
                        ->sort(2);
                }

                if ($user->hasAnyRole(['responsable-budget', 'admin'])) {
                    $items[] = NavigationGroup::make('Budget')
                        ->items([
                            NavigationItem::make('Budget Lignes')
                                ->url('/admin/budget-lignes')
                                ->icon('heroicon-o-banknotes'),
                            NavigationItem::make('Demandes à Valider')
                                ->url('/admin/demande-devis')
                                ->icon('heroicon-o-check-circle'),
                        ])
                        ->sort(3);
                }

                if ($user->hasAnyRole(['service-achat', 'admin'])) {
                    $items[] = NavigationGroup::make('Achats')
                        ->items([
                            NavigationItem::make('Commandes')
                                ->url('/admin/commandes')
                                ->icon('heroicon-o-shopping-cart'),
                        ])
                        ->sort(4);
                }

                return $items;
            });
    }
}

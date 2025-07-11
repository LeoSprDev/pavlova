<?php
namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationBuilder;
use App\Filament\Resources\DemandeDevisResource;
use App\Filament\Resources\BudgetLigneResource;
use App\Filament\Resources\CommandeResource;
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
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder->groups([
                    NavigationGroup::make('Workflow')
                        ->items([
                            NavigationItem::make('Demandes')
                                ->icon('heroicon-o-document-text')
                                ->url(fn (): string => DemandeDevisResource::getUrl('index'))
                                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.demande-devis.*')),
                            NavigationItem::make('Budgets')
                                ->icon('heroicon-o-banknotes')
                                ->url(fn (): string => BudgetLigneResource::getUrl('index'))
                                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.budget-lignes.*')),
                            NavigationItem::make('Commandes')
                                ->icon('heroicon-o-shopping-cart')
                                ->url(fn (): string => CommandeResource::getUrl('index'))
                                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.commandes.*')),
                        ])
                ]);
            });
    }
}

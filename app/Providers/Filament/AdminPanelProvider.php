<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\AgentDashboard;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                AgentDashboard::class,
            ])
            ->navigation(function () {
                $user = Auth::user();

                if (! $user) {
                    return [];
                }

                $items = [];

                $items[] = NavigationItem::make('Dashboard')
                    ->url(fn (): string => route('filament.admin.pages.dashboard'))
                    ->icon('heroicon-o-home')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.dashboard'));

                if ($user->hasRole('agent-service')) {
                    $items[] = NavigationGroup::make('Mon Espace Agent')
                        ->items([
                            NavigationItem::make('Mes Demandes')
                                ->url(fn (): string => route('filament.admin.resources.mes-demandes-resource.index'))
                                ->icon('heroicon-o-document-text'),
                            NavigationItem::make('Nouvelle Demande')
                                ->url(fn (): string => route('filament.admin.resources.mes-demandes-resource.create'))
                                ->icon('heroicon-o-plus-circle'),
                            NavigationItem::make('Mes Livraisons')
                                ->url(fn (): string => route('filament.admin.resources.livraison-resource.index'))
                                ->icon('heroicon-o-truck'),
                        ]);
                }

                if ($user->hasRole('responsable-service')) {
                    $items[] = NavigationGroup::make('Gestion Service')
                        ->items([
                            NavigationItem::make('Demandes à Valider')
                                ->url(fn (): string => route('filament.admin.resources.demande-devis-resource.index'))
                                ->icon('heroicon-o-check-circle'),
                            NavigationItem::make('Budget Mon Service')
                                ->url(fn (): string => route('filament.admin.resources.budget-ligne-resource.index'))
                                ->icon('heroicon-o-currency-euro'),
                            NavigationItem::make('Mes Agents')
                                ->url(fn (): string => route('filament.admin.resources.user-resource.index'))
                                ->icon('heroicon-o-user-group'),
                        ]);
                }

                if ($user->hasRole('responsable-budget')) {
                    $items[] = NavigationGroup::make('Gestion Budget')
                        ->items([
                            NavigationItem::make('Demandes Budgétaires')
                                ->url(fn (): string => route('filament.admin.resources.demande-devis-resource.index'))
                                ->icon('heroicon-o-banknotes'),
                            NavigationItem::make('Budget Global')
                                ->url(fn (): string => route('filament.admin.resources.budget-ligne-resource.index'))
                                ->icon('heroicon-o-chart-bar'),
                        ]);
                }

                if ($user->hasRole('service-achat')) {
                    $items[] = NavigationGroup::make('Gestion Achat')
                        ->items([
                            NavigationItem::make('Demandes Achat')
                                ->url(fn (): string => route('filament.admin.resources.demande-devis-resource.index'))
                                ->icon('heroicon-o-shopping-cart'),
                            NavigationItem::make('Commandes')
                                ->url(fn (): string => route('filament.admin.resources.commande-resource.index'))
                                ->icon('heroicon-o-document-check'),
                            NavigationItem::make('Fournisseurs')
                                ->url(fn (): string => route('filament.admin.resources.fournisseur-resource.index'))
                                ->icon('heroicon-o-building-office'),
                        ]);
                }

                if ($user->hasRole('admin')) {
                    $items[] = NavigationGroup::make('Administration')
                        ->items([
                            NavigationItem::make('Utilisateurs')
                                ->url(fn (): string => route('filament.admin.resources.user-resource.index'))
                                ->icon('heroicon-o-users'),
                            NavigationItem::make('Services')
                                ->url(fn (): string => route('filament.admin.resources.service-resource.index'))
                                ->icon('heroicon-o-building-office-2'),
                        ]);
                }

                return $items;
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

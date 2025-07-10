<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use App\Models\DemandeDevis;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
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
            ->id('admin')
            ->path('admin')
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
            ])
            ->navigation(function () {
                $user = auth()->user();
                $items = [];

                $items[] = NavigationItem::make('Dashboard')
                    ->url('/admin')
                    ->icon('heroicon-o-home')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.dashboard'));

                if ($user && $user->hasRole('agent-service')) {
                    $items[] = NavigationGroup::make('Mon Espace Agent')
                        ->items([
                            NavigationItem::make('Mes Demandes')
                                ->url('/admin/mes-demandes')
                                ->icon('heroicon-o-document-text')
                                ->badge(fn () => DemandeDevis::where('created_by', auth()->id())
                                    ->whereIn('statut', ['pending', 'approved_service'])->count() ?: null)
                                ->badgeColor('info'),
                            NavigationItem::make('Nouvelle Demande')
                                ->url('/admin/mes-demandes/create')
                                ->icon('heroicon-o-plus-circle')
                                ->badge('Nouveau')
                                ->badgeColor('success'),
                            NavigationItem::make('Mes Livraisons')
                                ->url('/admin/livraisons?tableFilters[mon_service][isActive]=true')
                                ->icon('heroicon-o-truck')
                                ->badge(fn () => \App\Models\Livraison::whereHas('commande.demandeDevis',
                                    fn($q) => $q->where('created_by', auth()->id())
                                        ->where('statut_reception', 'en_attente'))->count() ?: null)
                                ->badgeColor('warning'),
                        ]);
                }

                if ($user && $user->hasRole('responsable-service')) {
                    $items[] = NavigationGroup::make('Gestion Service')
                        ->items([
                            NavigationItem::make('Demandes à Valider')
                                ->url('/admin/demande-devis?tableFilters[statut][value]=pending&tableFilters[mon_service][isActive]=true')
                                ->icon('heroicon-o-check-circle')
                                ->badge(fn () => DemandeDevis::where('statut', 'pending')
                                    ->whereHas('serviceDemandeur', fn($q) =>
                                        $q->where('id', auth()->user()->service_id))->count() ?: null)
                                ->badgeColor('warning'),
                            NavigationItem::make('Budget Mon Service')
                                ->url('/admin/budget-lignes?tableFilters[service_id][value]=' . (auth()->user()->service_id ?? ''))
                                ->icon('heroicon-o-currency-euro'),
                            NavigationItem::make('Mes Agents')
                                ->url('/admin/users?tableFilters[service_id][value]=' . (auth()->user()->service_id ?? ''))
                                ->icon('heroicon-o-user-group'),
                        ]);
                }

                if ($user && $user->hasRole('responsable-budget')) {
                    $items[] = NavigationGroup::make('Gestion Budget')
                        ->items([
                            NavigationItem::make('Demandes Budgétaires')
                                ->url('/admin/demande-devis?tableFilters[statut][value]=approved_service')
                                ->icon('heroicon-o-banknotes')
                                ->badge(fn () => DemandeDevis::where('statut', 'approved_service')->count() ?: null)
                                ->badgeColor('info'),
                            NavigationItem::make('Budget Global')
                                ->url('/admin/budget-lignes')
                                ->icon('heroicon-o-chart-bar'),
                            NavigationItem::make('Analytics Budget')
                                ->url('/admin/analytics/budget')
                                ->icon('heroicon-o-presentation-chart-line'),
                        ]);
                }

                if ($user && $user->hasRole('service-achat')) {
                    $items[] = NavigationGroup::make('Gestion Achat')
                        ->items([
                            NavigationItem::make('Demandes Achat')
                                ->url('/admin/demande-devis?tableFilters[statut][value]=approved_budget')
                                ->icon('heroicon-o-shopping-cart')
                                ->badge(fn () => DemandeDevis::where('statut', 'approved_budget')->count() ?: null)
                                ->badgeColor('success'),
                            NavigationItem::make('Commandes')
                                ->url('/admin/commandes')
                                ->icon('heroicon-o-document-check'),
                            NavigationItem::make('Fournisseurs')
                                ->url('/admin/fournisseurs')
                                ->icon('heroicon-o-building-office'),
                        ]);
                }

                if ($user && $user->hasRole('admin')) {
                    $items[] = NavigationGroup::make('Administration')
                        ->items([
                            NavigationItem::make('Utilisateurs')
                                ->url('/admin/users')
                                ->icon('heroicon-o-users'),
                            NavigationItem::make('Services')
                                ->url('/admin/services')
                                ->icon('heroicon-o-building-office-2'),
                            NavigationItem::make('Configuration')
                                ->url('/admin/settings')
                                ->icon('heroicon-o-cog-6-tooth'),
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
                \App\Http\Middleware\ForcePasswordChangeMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

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
use Filament\Navigation\NavigationBuilder;
use App\Models\DemandeDevis;
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
            ->colors(['primary' => Color::Amber])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->navigation(function () {
                $user = auth()->user();
                $builder = new NavigationBuilder();

                $builder->item(
                    NavigationItem::make('Dashboard')
                        ->url(fn (): string => route('filament.admin.pages.dashboard'))
                        ->icon('heroicon-o-home')
                        ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.dashboard'))
                );

                if (! $user) {
                    return $builder;
                }

                if ($user->hasRole('agent-service')) {
                    $builder->group(
                        NavigationGroup::make('Mon Espace Agent')
                            ->items([
                            NavigationItem::make('Mes Demandes')
                                ->url(fn (): string => route('filament.admin.resources.mes-demandes.index'))
                                ->icon('heroicon-o-document-text')
                                ->badge(
                                    fn () => DemandeDevis::where('created_by', auth()->id())
                                        ->whereIn('statut', ['pending', 'approved_service'])->count() ?: null,
                                    'info'
                                ),
                            NavigationItem::make('Nouvelle Demande')
                                ->url(fn (): string => route('filament.admin.resources.mes-demandes.create'))
                                ->icon('heroicon-o-plus-circle')
                                ->badge('Nouveau', 'success'),
                            NavigationItem::make('Mes Livraisons')
                                ->url(fn (): string => route('filament.admin.resources.livraisons.index', ['tableFilters[mon_service][isActive]' => 'true']))
                                ->icon('heroicon-o-truck')
                                ->badge(
                                    fn () => \App\Models\Livraison::whereHas('commande.demandeDevis',
                                        fn ($q) => $q->where('created_by', auth()->id())
                                            ->where('statut_reception', 'en_attente')
                                    )->count() ?: null,
                                    'warning'
                                ),
                            ])
                    );
                }

                if ($user->hasRole('responsable-service')) {
                    $builder->group(
                        NavigationGroup::make('Gestion Service')
                            ->items([
                            NavigationItem::make('Demandes à Valider')
                                ->url(fn (): string => route('filament.admin.resources.demande-devis.index', [
                                    'tableFilters[statut][value]' => 'pending',
                                    'tableFilters[mon_service][isActive]' => 'true',
                                ]))
                                ->icon('heroicon-o-check-circle')
                                ->badge(
                                    fn () => DemandeDevis::where('statut', 'pending')
                                        ->whereHas('serviceDemandeur', fn ($q) => $q->where('id', auth()->user()->service_id))->count() ?: null,
                                    'warning'
                                ),
                            NavigationItem::make('Budget Mon Service')
                                ->url(fn (): string => route('filament.admin.resources.budget-lignes.index', [
                                    'tableFilters[service_id][value]' => auth()->user()->service_id ?? '',
                                ]))
                                ->icon('heroicon-o-currency-euro'),
                            NavigationItem::make('Mes Agents')
                                ->url(fn (): string => route('filament.admin.resources.users.index', [
                                    'tableFilters[service_id][value]' => auth()->user()->service_id ?? '',
                                ]))
                                ->icon('heroicon-o-user-group'),
                            ])
                    );
                }

                if ($user->hasRole('responsable-budget')) {
                    $builder->group(
                        NavigationGroup::make('Gestion Budget')
                            ->items([
                            NavigationItem::make('Demandes Budgétaires')
                                ->url(fn (): string => route('filament.admin.resources.demande-devis.index', [
                                    'tableFilters[statut][value]' => 'approved_service',
                                ]))
                                ->icon('heroicon-o-banknotes')
                                ->badge(
                                    fn () => DemandeDevis::where('statut', 'approved_service')->count() ?: null,
                                    'info'
                                ),
                            NavigationItem::make('Budget Global')
                                ->url(fn (): string => route('filament.admin.resources.budget-lignes.index'))
                                ->icon('heroicon-o-chart-bar'),
                            NavigationItem::make('Analytics Budget')
                                ->url('/admin/analytics/budget')
                                ->icon('heroicon-o-presentation-chart-line'),
                            ])
                    );
                }

                if ($user->hasRole('service-achat')) {
                    $builder->group(
                        NavigationGroup::make('Gestion Achat')
                            ->items([
                            NavigationItem::make('Demandes Achat')
                                ->url(fn (): string => route('filament.admin.resources.demande-devis.index', [
                                    'tableFilters[statut][value]' => 'approved_budget',
                                ]))
                                ->icon('heroicon-o-shopping-cart')
                                ->badge(
                                    fn () => DemandeDevis::where('statut', 'approved_budget')->count() ?: null,
                                    'success'
                                ),
                            NavigationItem::make('Commandes')
                                ->url(fn (): string => route('filament.admin.resources.commandes.index'))
                                ->icon('heroicon-o-document-check'),
                            ])
                    );
                }

                if ($user->hasRole('administrateur')) {
                    $builder->group(
                        NavigationGroup::make('Administration')
                            ->items([
                            NavigationItem::make('Utilisateurs')
                                ->url(fn (): string => route('filament.admin.resources.users.index'))
                                ->icon('heroicon-o-users'),
                            NavigationItem::make('Configuration')
                                ->url('/admin/settings')
                                ->icon('heroicon-o-cog-6-tooth'),
                            ])
                    );
                }

                return $builder;
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


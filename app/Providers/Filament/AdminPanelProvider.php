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
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->navigation(function () {
                $user = auth()->user();
                $items = [];

                $items[] = NavigationItem::make('Dashboard')
                    ->url('/admin')
                    ->icon('heroicon-o-home');

                if ($user->hasRole('agent-service')) {
                    $items[] = NavigationGroup::make('Agent Service')
                        ->items([
                            NavigationItem::make('Mes Demandes')
                                ->url('/admin/mes-demandes')
                                ->icon('heroicon-o-document-text')
                                ->badge(fn () => DemandeDevis::where('created_by', auth()->id())
                                    ->where('statut', 'pending')->count() ?: null),
                            NavigationItem::make('Nouvelle Demande')
                                ->url('/admin/demande-devis/create')
                                ->icon('heroicon-o-plus'),
                        ]);
                }

                if ($user->hasRole('responsable-service')) {
                    $items[] = NavigationGroup::make('Responsable Service')
                        ->items([
                            NavigationItem::make('Demandes à Valider')
                                ->url('/admin/demande-devis?tableFilters[statut][value]=pending')
                                ->icon('heroicon-o-check-circle')
                                ->badge(fn () => DemandeDevis::where('statut', 'pending')
                                    ->whereHas('serviceDemandeur', fn($q) =>
                                        $q->where('id', auth()->user()->service_id))->count() ?: null),
                            NavigationItem::make('Budget Service')
                                ->url('/admin/budget-lignes?tableFilters[service_id][value]=' . auth()->user()->service_id)
                                ->icon('heroicon-o-currency-euro'),
                        ]);
                }

                if ($user->hasRole('responsable-budget')) {
                    $items[] = NavigationGroup::make('Budget')
                        ->items([
                            NavigationItem::make('Toutes Demandes')
                                ->url('/admin/demande-devis')
                                ->icon('heroicon-o-document-text'),
                            NavigationItem::make('Budget Global')
                                ->url('/admin/budget-lignes')
                                ->icon('heroicon-o-currency-euro'),
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

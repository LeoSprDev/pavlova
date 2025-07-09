<?php
namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Filament::serving(function () {
            Filament::getCurrentPanel()->navigation(function (\Filament\Navigation\NavigationBuilder $builder): \Filament\Navigation\NavigationBuilder {
                $user = auth()->user();
                $groups = [];

                if ($user && $user->hasRole('agent-service')) {
                    $groups[] = NavigationGroup::make('Agent')
                        ->items([
                            NavigationItem::make('Mes Demandes')
                                ->url('/admin/mes-demandes')
                                ->icon('heroicon-o-document-text'),
                            NavigationItem::make('Nouveau Devis')
                                ->url('/admin/demande-devis/create')
                                ->icon('heroicon-o-plus'),
                        ]);
                }

                if ($user && $user->hasRole('responsable-service')) {
                    $groups[] = NavigationGroup::make('Responsable Service')
                        ->items([
                            NavigationItem::make('Demandes à Valider')
                                ->url('/admin/demande-devis?tableFilters[statut][value]=pending')
                                ->icon('heroicon-o-check-circle'),
                            NavigationItem::make('Budget Service')
                                ->url('/admin/budget-lignes')
                                ->icon('heroicon-o-currency-euro'),
                        ]);
                }

                return $builder->groups($groups);
            });
        });
    }
}

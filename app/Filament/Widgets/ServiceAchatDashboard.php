<?php

namespace App\Filament\Widgets;

use App\Models\DemandeDevis;
use App\Models\Commande;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ServiceAchatDashboard extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }
        
        $demandesEnAttente = DemandeDevis::where('current_step', 'validation-achat')
            ->count();
            
        $demandesValidees = DemandeDevis::whereHas('approvalsHistory', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('status', 'approved')
                  ->where('step', 'validation-achat');
            })
            ->whereMonth('created_at', now()->month)
            ->count();

        $montantCommandes = Commande::whereMonth('created_at', now()->month)
            ->sum('montant_reel');

        $commandesEnCours = Commande::whereIn('statut', ['en_cours', 'confirmee'])
            ->count();

        return [
            Stat::make('Demandes à valider', $demandesEnAttente)
                ->description('En attente validation achat')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Demandes validées ce mois', $demandesValidees)
                ->description('Approuvées par vous')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Montant commandes ce mois', number_format($montantCommandes, 2, ',', ' ') . ' €')
                ->description('Commandes passées')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            
            Stat::make('Commandes en cours', $commandesEnCours)
                ->description('En attente livraison')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),
        ];
    }

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('service-achat');
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
}
<?php

namespace App\Filament\Widgets;

use App\Models\DemandeDevis;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ResponsableServiceDashboard extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->service) {
            return [];
        }
        
        $service = $user->service;
        
        $demandesEnAttente = DemandeDevis::where('service_demandeur_id', $service->id)
            ->where('current_step', 'validation-responsable-service')
            ->count();
            
        $demandesValidees = DemandeDevis::where('service_demandeur_id', $service->id)
            ->whereHas('approvalsHistory', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('status', 'approved')
                  ->where('step', 'validation-responsable-service');
            })
            ->whereMonth('created_at', now()->month)
            ->count();

        $budgetConsomme = DemandeDevis::where('service_demandeur_id', $service->id)
            ->whereIn('statut', ['approved_achat', 'delivered'])
            ->sum('prix_total_ttc');

        $totalDemandes = DemandeDevis::where('service_demandeur_id', $service->id)
            ->whereMonth('created_at', now()->month)
            ->count();

        return [
            Stat::make('Demandes en attente', $demandesEnAttente)
                ->description('À valider par vous')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Demandes validées ce mois', $demandesValidees)
                ->description('Sur ' . $totalDemandes . ' demandes totales')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Budget consommé', number_format($budgetConsomme, 2, ',', ' ') . ' €')
                ->description('Demandes approuvées achat')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            
            Stat::make('Service', $service->nom)
                ->description($service->code)
                ->descriptionIcon('heroicon-m-building-office')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('responsable-service') 
            && Auth::user()?->is_service_responsable;
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
}
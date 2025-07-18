<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\DemandeDevis;
use Illuminate\Support\HtmlString;

class NotificationCenterWidget extends Widget
{
    protected static string $view = 'filament.widgets.notification-center-widget';
    protected static ?int $sort = 1;
    protected static ?string $heading = 'Notifications';
    protected int | string | array $columnSpan = 'full';

    public function getNotifications(): array
    {
        $user = Auth::user();
        $notifications = [];

        if (!$user) {
            return [];
        }

        if ($user->hasRole('responsable-service')) {
            $pendingService = DemandeDevis::where('statut', 'pending')
                ->whereHas('serviceDemandeur', function($query) use ($user) {
                    $query->where('id', $user->service_id);
                })
                ->count();
            
            if ($pendingService > 0) {
                $notifications[] = [
                    'type' => 'warning',
                    'title' => 'Demandes en attente de validation service',
                    'message' => "{$pendingService} demande(s) de devis en attente de votre validation",
                    'action' => 'Voir les demandes',
                    'url' => route('filament.admin.resources.demande-devis.index', ['tableFilters[statut][value]' => 'pending'])
                ];
            }
        }

        if ($user->hasRole('responsable-budget')) {
            $pendingBudget = DemandeDevis::where('statut', 'approved_service')->count();
            
            if ($pendingBudget > 0) {
                $notifications[] = [
                    'type' => 'info',
                    'title' => 'Demandes en attente de validation budget',
                    'message' => "{$pendingBudget} demande(s) de devis en attente de votre validation budgétaire",
                    'action' => 'Voir les demandes',
                    'url' => route('filament.admin.resources.demande-devis.index', ['tableFilters[statut][value]' => 'approved_service'])
                ];
            }
        }

        if ($user->hasRole('service-achat')) {
            $pendingAchat = DemandeDevis::where('statut', 'approved_budget')->count();
            
            if ($pendingAchat > 0) {
                $notifications[] = [
                    'type' => 'success',
                    'title' => 'Demandes en attente de validation achat',
                    'message' => "{$pendingAchat} demande(s) de devis en attente de votre validation achat",
                    'action' => 'Voir les demandes',
                    'url' => route('filament.admin.resources.demande-devis.index', ['tableFilters[statut][value]' => 'approved_budget'])
                ];
            }
        }

        // Notifications pour les demandes avec dépassement budget
        if ($user->hasAnyRole(['responsable-service', 'responsable-budget', 'service-achat'])) {
            $budgetWarnings = DemandeDevis::whereIn('statut', ['pending', 'approved_service', 'approved_budget'])
                ->with('budgetLigne')
                ->get()
                ->filter(function($demande) {
                    if ($demande->budgetLigne) {
                        $restant = $demande->budgetLigne->calculateBudgetRestant();
                        return $demande->prix_total_ttc > $restant;
                    }
                    return false;
                })
                ->count();

            if ($budgetWarnings > 0) {
                $notifications[] = [
                    'type' => 'danger',
                    'title' => 'Demandes avec dépassement budget',
                    'message' => "{$budgetWarnings} demande(s) dépassent le budget disponible",
                    'action' => 'Voir les demandes',
                    'url' => route('filament.admin.resources.demande-devis.index')
                ];
            }
        }

        return $notifications;
    }

    protected function getViewData(): array
    {
        return [
            'notifications' => $this->getNotifications(),
        ];
    }
}

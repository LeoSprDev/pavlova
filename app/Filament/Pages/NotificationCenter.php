<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Models\DemandeDevis;
use Filament\Actions\Action;
use Filament\Support\Enums\MaxWidth;

class NotificationCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Notifications';
    protected static ?string $title = 'Centre de notifications';
    protected static string $view = 'filament.pages.notification-center';
    
    // public static function canAccess(): bool
    // {
    //     return auth()->check() && auth()->user()->hasAnyRole(['responsable-service', 'responsable-budget', 'service-achat', 'administrateur']);
    // }

    public function getNotifications(): array
    {
        $user = Auth::user();
        $notifications = [];

        if ($user->hasRole('responsable-service')) {
            $pendingService = DemandeDevis::where('statut', 'pending')
                ->whereHas('serviceDemandeur', function($query) use ($user) {
                    $query->where('id', $user->service_id);
                })
                ->with(['serviceDemandeur', 'createdBy'])
                ->get();
            
            foreach ($pendingService as $demande) {
                $notifications[] = [
                    'id' => $demande->id,
                    'type' => 'warning',
                    'title' => 'Demande en attente de validation service',
                    'message' => "Demande #{$demande->id} - {$demande->denomination} créée par {$demande->createdBy->name}",
                    'created_at' => $demande->created_at,
                    'action_url' => route('filament.admin.resources.demande-devis.view', $demande->id),
                    'action_label' => 'Valider',
                ];
            }
        }

        if ($user->hasRole('responsable-budget')) {
            $pendingBudget = DemandeDevis::where('statut', 'approved_service')
                ->with(['serviceDemandeur', 'createdBy'])
                ->get();
            
            foreach ($pendingBudget as $demande) {
                $notifications[] = [
                    'id' => $demande->id,
                    'type' => 'info',
                    'title' => 'Demande en attente de validation budget',
                    'message' => "Demande #{$demande->id} - {$demande->denomination} du service {$demande->serviceDemandeur->nom}",
                    'created_at' => $demande->created_at,
                    'action_url' => route('filament.admin.resources.demande-devis.view', $demande->id),
                    'action_label' => 'Valider budget',
                ];
            }
        }

        if ($user->hasRole('service-achat')) {
            $pendingAchat = DemandeDevis::where('statut', 'approved_budget')
                ->with(['serviceDemandeur', 'createdBy'])
                ->get();
            
            foreach ($pendingAchat as $demande) {
                $notifications[] = [
                    'id' => $demande->id,
                    'type' => 'success',
                    'title' => 'Demande en attente de validation achat',
                    'message' => "Demande #{$demande->id} - {$demande->denomination} du service {$demande->serviceDemandeur->nom}",
                    'created_at' => $demande->created_at,
                    'action_url' => route('filament.admin.resources.demande-devis.view', $demande->id),
                    'action_label' => 'Valider achat',
                ];
            }
        }

        // Trier par date de création décroissante
        usort($notifications, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $notifications;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualiser')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->redirect(request()->url())),
        ];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
}

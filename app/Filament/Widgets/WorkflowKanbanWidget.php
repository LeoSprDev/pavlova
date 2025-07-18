<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\DemandeDevis;
use Illuminate\Support\Facades\Auth;

class WorkflowKanbanWidget extends Widget
{
    protected static string $view = 'filament.widgets.workflow-kanban-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;
    
    // SÉCURITÉ : Filtrage par rôle
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            'service-achat', 'responsable-budget', 'responsable-direction', 'responsable-service', 'agent-service'
        ]) ?? false;
    }
    
    public function getKanbanData(): array
    {
        $user = Auth::user();
        $columns = $this->getColumnsForRole($user);
        
        $data = [];
        foreach ($columns as $status => $config) {
            $query = DemandeDevis::where('statut', $status)
                ->with(['serviceDemandeur', 'budgetLigne', 'createdBy']);
                
            // FILTRAGE SÉCURISÉ par rôle
            if ($user && $user->hasRole('service-demandeur') && $user->service_id) {
                $query->where('service_demandeur_id', $user->service_id);
            }
            
            $demandes = $query->latest()->limit(8)->get();
            
            $data[$status] = [
                'label' => $config['label'],
                'color' => $config['color'],
                'icon' => $config['icon'],
                'count' => $query->count(),
                'demandes' => $demandes,
                'action_available' => $config['action'] ?? false
            ];
        }
        
        return $data;
    }
    
    private function getColumnsForRole($user): array
    {
        if ($user && $user->hasRole('service-achat')) {
            return [
                'pending_achat' => [
                    'label' => 'À Valider Achat',
                    'color' => 'warning',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'action' => true
                ],
                'approved_achat' => [
                    'label' => 'Créer Commande', 
                    'color' => 'info',
                    'icon' => 'heroicon-o-shopping-cart',
                    'action' => true
                ],
                'ordered' => [
                    'label' => 'Commandes Passées',
                    'color' => 'success', 
                    'icon' => 'heroicon-o-check-circle'
                ],
                'pending_reception' => [
                    'label' => 'En Livraison',
                    'color' => 'purple',
                    'icon' => 'heroicon-o-truck'
                ]
            ];
        }
        
        // Vue globale pour responsable-budget et direction
        return [
            'pending_manager' => [
                'label' => 'Responsable Service',
                'color' => 'yellow',
                'icon' => 'heroicon-o-user'
            ],
            'pending_direction' => [
                'label' => 'Direction',
                'color' => 'blue', 
                'icon' => 'heroicon-o-building-office'
            ],
            'pending_achat' => [
                'label' => 'Service Achat',
                'color' => 'purple',
                'icon' => 'heroicon-o-shopping-bag'
            ],
            'ordered' => [
                'label' => 'Commandées',
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle'
            ]
        ];
    }
}
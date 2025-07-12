<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\DemandeDevis;
use Illuminate\Support\Facades\Auth;

class WorkflowKanbanWidget extends Widget
{
    protected static string $view = 'filament.widgets.workflow-kanban-widget';
    protected int | string | array $columnSpan = 'full';

    public function getKanbanColumns(): array
    {
        return [
            'pending_manager' => [
                'title' => '👤 Manager',
                'color' => 'yellow',
                'demandes' => $this->getDemandesByStatus('pending_manager'),
            ],
            'pending_direction' => [
                'title' => '🏢 Direction',
                'color' => 'blue',
                'demandes' => $this->getDemandesByStatus('pending_direction'),
            ],
            'pending_achat' => [
                'title' => '🛒 Achat',
                'color' => 'purple',
                'demandes' => $this->getDemandesByStatus('pending_achat'),
            ],
            'pending_delivery' => [
                'title' => '🚚 Livraison',
                'color' => 'orange',
                'demandes' => $this->getDemandesByStatus('pending_delivery'),
            ],
            'delivered_confirmed' => [
                'title' => '✅ Terminé',
                'color' => 'green',
                'demandes' => $this->getDemandesByStatus('delivered_confirmed'),
            ],
        ];
    }

    private function getDemandesByStatus(string $status)
    {
        $query = DemandeDevis::where('statut', $status)
            ->with(['serviceDemandeur', 'budgetLigne']);

        $user = Auth::user();
        if ($user && $user->hasRole('agent-service') && $user->service_id) {
            $query->where('service_demandeur_id', $user->service_id);
        }

        return $query->latest()->limit(10)->get();
    }

    public function getProgressPercentage(string $status): int
    {
        return match ($status) {
            'pending_manager' => 20,
            'pending_direction' => 40,
            'pending_achat' => 60,
            'pending_delivery' => 80,
            'delivered_confirmed' => 100,
            default => 10,
        };
    }
}

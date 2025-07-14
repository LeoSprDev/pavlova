<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\DemandeDevis;
use Illuminate\Support\Facades\Auth;

class WorkflowTimelineWidget extends Widget
{
    protected static string $view = 'filament.widgets.workflow-timeline-widget';
    protected static ?int $sort = 3;

    public function getDemandes()
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        if ($user->hasRole('agent-service')) {
            return DemandeDevis::where('service_demandeur_id', $user->service_id)
                ->whereIn('statut', ['pending', 'approved_service', 'approved_budget'])
                ->with(['serviceDemandeur', 'budgetLigne'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        if ($user->hasRole('responsable-service')) {
            return DemandeDevis::where('service_demandeur_id', $user->service_id)
                ->where('statut', 'pending')
                ->with(['serviceDemandeur', 'budgetLigne', 'createdBy'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return DemandeDevis::whereIn('statut', ['approved_service', 'approved_budget'])
            ->with(['serviceDemandeur', 'budgetLigne'])
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();
    }

    public function getWorkflowProgress(string $statut): int
    {
        return match ($statut) {
            'pending' => 20,
            'approved_service' => 40,
            'approved_budget' => 60,
            'approved_achat' => 80,
            'ordered' => 90,
            'delivered' => 100,
            default => 10,
        };
    }
}

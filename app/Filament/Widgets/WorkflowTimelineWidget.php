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

        if ($user->hasRole('agent-service')) {
            return DemandeDevis::where('service_demandeur_id', $user->service_id)
                ->whereIn('statut', ['pending_manager', 'pending_direction', 'pending_achat'])
                ->with(['serviceDemandeur', 'budgetLigne'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        if ($user->hasRole('manager-service')) {
            return DemandeDevis::where('service_demandeur_id', $user->service_id)
                ->where('statut', 'pending_manager')
                ->with(['serviceDemandeur', 'budgetLigne', 'createdBy'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return DemandeDevis::whereIn('statut', ['pending_direction', 'pending_achat'])
            ->with(['serviceDemandeur', 'budgetLigne'])
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();
    }
}

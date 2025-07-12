<?php
namespace App\Services;

use App\Models\{DemandeDevis, ProcessApproval};

class WorkflowIntelligenceService
{
    /**
     * Predict expected workflow duration in days based on history.
     */
    public function predictWorkflowDuration(DemandeDevis $demande): int
    {
        $similar = DemandeDevis::where('service_demandeur_id', $demande->service_demandeur_id)
            ->whereBetween('prix_total_ttc', [
                $demande->prix_total_ttc * 0.8,
                $demande->prix_total_ttc * 1.2,
            ])
            ->where('statut', 'delivered_confirmed')
            ->avg('workflow_duration_days');

        return $similar ? (int) round($similar) : 5;
    }

    /**
     * Detect slowest approval steps based on history.
     *
     * @return array<int, array>
     */
    public function detectBottlenecks(): array
    {
        return ProcessApproval::selectRaw('step, AVG(EXTRACT(EPOCH FROM (approved_at - created_at))/86400) as avg_days')
            ->groupBy('step')
            ->orderBy('avg_days', 'desc')
            ->get()
            ->toArray();
    }
}

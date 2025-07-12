<?php
namespace App\Services;

use App\Models\{AuditTrail, BudgetLigne, DemandeDevis, User};
use Illuminate\Support\Facades\Auth;

class AuditTrailService
{
    public function logAction(string $action, string $model, int $id, array $details = []): void
    {
        AuditTrail::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $model,
            'auditable_id' => $id,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function logApproval(DemandeDevis $demande, User $user, ?string $comment = null): void
    {
        $this->logAction('approved', DemandeDevis::class, $demande->id, [
            'previous_status' => $demande->getOriginal('statut'),
            'new_status' => $demande->statut,
            'approver_role' => $user->getRoleNames()->first(),
            'comment' => $comment,
            'montant' => $demande->prix_total_ttc,
        ]);
    }

    public function logRejection(DemandeDevis $demande, User $user, string $reason): void
    {
        $this->logAction('rejected', DemandeDevis::class, $demande->id, [
            'previous_status' => $demande->getOriginal('statut'),
            'rejector_role' => $user->getRoleNames()->first(),
            'reason' => $reason,
            'montant' => $demande->prix_total_ttc,
        ]);
    }

    public function logBudgetEngagement(BudgetLigne $ligne, float $montant, bool $warning = false): void
    {
        $this->logAction('budget_engaged', BudgetLigne::class, $ligne->id, [
            'montant_engage' => $montant,
            'has_warning' => $warning,
        ]);
    }

    public function getAuditHistory(string $model, int $id)
    {
        return AuditTrail::where('auditable_type', $model)
            ->where('auditable_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

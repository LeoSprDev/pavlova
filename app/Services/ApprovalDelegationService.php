<?php
namespace App\Services;

use App\Models\{User, UserAbsence, DemandeDevis};
use Carbon\Carbon;

class ApprovalDelegationService
{
    public function declareAbsence(User $user, Carbon $startDate, Carbon $endDate, User $delegate): void
    {
        UserAbsence::create([
            'user_id' => $user->id,
            'delegate_id' => $delegate->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
            'declared_at' => now(),
        ]);

        $this->notifyPendingDemandes($user, $delegate);
    }

    public function endAbsence(User $user): void
    {
        UserAbsence::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'ended', 'actual_end_date' => now()]);
    }

    public function getCurrentDelegate(User $user): ?User
    {
        $absence = UserAbsence::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        return $absence?->delegate;
    }

    public function getDelegatedFor(User $user)
    {
        return UserAbsence::where('delegate_id', $user->id)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with('user')
            ->get()
            ->pluck('user');
    }

    public function canApprove(User $user, DemandeDevis $demande): bool
    {
        if ($this->hasDirectApprovalRight($user, $demande)) {
            return true;
        }
        foreach ($this->getDelegatedFor($user) as $delegatedUser) {
            if ($this->hasDirectApprovalRight($delegatedUser, $demande)) {
                return true;
            }
        }
        return false;
    }

    private function hasDirectApprovalRight(User $user, DemandeDevis $demande): bool
    {
        $current = $demande->statut;
        return match($current) {
            'pending_manager' => $user->hasRole('manager-service') && $user->service_id === $demande->service_demandeur_id,
            'pending_direction' => $user->hasRole('responsable-direction'),
            'pending_achat' => $user->hasRole('service-achat'),
            default => false,
        };
    }

    private function notifyPendingDemandes(User $absentUser, User $delegate): void
    {
        $pending = DemandeDevis::where('statut', $this->getStatusForUser($absentUser))->get();
        foreach ($pending as $demande) {
            app(\App\Services\WorkflowNotificationService::class)
                ->notifyDelegation($demande, $absentUser, $delegate);
        }
    }

    private function getStatusForUser(User $user): string
    {
        if ($user->hasRole('manager-service')) {
            return 'pending_manager';
        }
        if ($user->hasRole('responsable-direction')) {
            return 'pending_direction';
        }
        if ($user->hasRole('service-achat')) {
            return 'pending_achat';
        }
        return 'pending';
    }
}

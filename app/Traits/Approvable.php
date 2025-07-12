<?php

namespace App\Traits;

use App\Models\ProcessApproval;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

trait Approvable
{
    public function approvals(): MorphMany
    {
        return $this->morphMany(ProcessApproval::class, 'approvable');
    }

    public function approve($user, $comment = null): bool
    {
        DB::transaction(function () use ($user, $comment) {
            $currentStep = $this->getCurrentApprovalStepKey();
            if (! $currentStep) {
                return false;
            }

            ProcessApproval::create([
                'approvable_type' => get_class($this),
                'approvable_id'   => $this->id,
                'user_id'         => $user->id,
                'step'            => $currentStep,
                'status'          => 'approved',
                'comment'         => $comment,
                'approved_at'     => now(),
            ]);

            $this->executeApprovalLogic($currentStep, $user);
            $this->moveToNextStepOrComplete();
            $this->sendApprovalNotifications($currentStep, $user);
        });

        return true;
    }

    public function reject($user, $comment): bool
    {
        $currentStep = $this->getCurrentApprovalStepKey();
        if (! $currentStep) {
            return false;
        }

        ProcessApproval::create([
            'approvable_type' => get_class($this),
            'approvable_id'   => $this->id,
            'user_id'         => $user->id,
            'step'            => $currentStep,
            'status'          => 'rejected',
            'comment'         => $comment,
            'approved_at'     => now(),
        ]);

        $this->update(['statut' => 'rejected']);

        Notification::make()
            ->title('Demande rejetée')
            ->body("Demande #{$this->id} rejetée: {$comment}")
            ->danger()
            ->sendToDatabase([$this->createdBy]);

        return true;
    }

    private function executeApprovalLogic($step, $user): void
    {
        switch ($step) {
            case 'pending_manager':
                $this->update(['statut' => 'approved_manager']);
                break;
            case 'pending_direction':
                $this->engagerBudget();
                $this->update(['statut' => 'approved_direction']);
                break;
            case 'pending_achat':
                $this->creerCommandeAutomatique($user);
                $this->update(['statut' => 'ordered']);
                break;
            case 'pending_delivery':
                $this->update(['statut' => 'pending_reception']);
                break;
            case 'reception-livraison':
                $this->finaliserBudgetDefinitif();
                $this->update(['statut' => 'delivered_confirmed']);
                break;
        }
    }

    private function moveToNextStepOrComplete(): void
    {
        $workflowSteps = [
            'pending_manager'    => 'pending_direction',
            'pending_direction'  => 'pending_achat',
            'pending_achat'      => 'pending_delivery',
            'pending_delivery'   => 'reception-livraison',
            'reception-livraison' => null,
        ];

        $nextStep = $workflowSteps[$this->current_step] ?? null;
        $this->update(['current_step' => $nextStep]);
    }

    public function getWorkflowProgress(string $statut): int
    {
        return match ($statut) {
            'pending_manager'       => 20,
            'approved_manager', 'pending_direction' => 40,
            'approved_direction', 'pending_achat' => 60,
            'ordered', 'pending_delivery' => 80,
            'delivered_confirmed'   => 100,
            default                => 10,
        };
    }

    private function engagerBudget(): void
    {
        $budgetLigne = $this->budgetLigne;
        $budgetLigne->increment('montant_engage', $this->prix_total_ttc);
    }

    private function creerCommandeAutomatique($user): void
    {
        \App\Models\Commande::create([
            'demande_devis_id' => $this->id,
            'numero_commande'  => 'CMD-' . now()->format('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT),
            'date_commande'    => now(),
            'commanditaire'    => $user->name,
            'statut'           => 'confirmee',
        ]);
    }

    private function finaliserBudgetDefinitif(): void
    {
        $budgetLigne = $this->budgetLigne;
        $montantReel = $this->prix_fournisseur_final ?? $this->prix_total_ttc;

        $budgetLigne->decrement('montant_engage', $this->prix_total_ttc);
        $budgetLigne->increment('montant_depense_reel', $montantReel);
    }

    private function sendApprovalNotifications($step, $user): void
    {
        Notification::make()
            ->title('Demande approuvée')
            ->body("Demande #{$this->id} approuvée à l'étape: {$step}")
            ->success()
            ->sendToDatabase([$this->createdBy]);
    }
    public function determineWorkflowPath(): array
    {
        $steps = [
            'pending_manager' => 'pending_direction',
            'pending_direction' => 'pending_achat',
            'pending_achat' => 'pending_delivery',
            'pending_delivery' => 'reception-livraison',
        ];

        if ($this->prix_total_ttc < 1000) {
            unset($steps['pending_direction']);
        }

        if ($this->serviceDemandeur && $this->serviceDemandeur->code === 'IT' && $this->urgence === 'critique') {
            return [
                'pending_manager' => 'pending_achat',
                'pending_achat' => 'pending_delivery',
                'pending_delivery' => 'reception-livraison',
            ];
        }

        return $steps;
    }

}

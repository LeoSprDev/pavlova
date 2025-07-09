<?php

namespace App\Traits;

use App\Models\ProcessApproval;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Approvable
{
    public function approvals(): MorphMany
    {
        return $this->morphMany(ProcessApproval::class, 'approvable');
    }

    public function approve($user, $comment = null): bool
    {
        $currentStep = $this->getCurrentApprovalStepKey();
        if (!$currentStep) return false;

        ProcessApproval::create([
            'approvable_type' => get_class($this),
            'approvable_id' => $this->id,
            'user_id' => $user->id,
            'step' => $currentStep,
            'status' => 'approved',
            'comment' => $comment,
        ]);

        $this->updateStatusAfterApproval();
        return true;
    }

    public function reject($user, $comment): bool
    {
        $currentStep = $this->getCurrentApprovalStepKey();
        if (!$currentStep) return false;

        ProcessApproval::create([
            'approvable_type' => get_class($this),
            'approvable_id' => $this->id,
            'user_id' => $user->id,
            'step' => $currentStep,
            'status' => 'rejected',
            'comment' => $comment,
        ]);

        $this->update(['statut' => 'rejected']);
        return true;
    }

    protected function updateStatusAfterApproval(): void
    {
        $steps = array_keys($this->approvalSteps());
        $currentStepIndex = array_search($this->getCurrentApprovalStepKey(), $steps);
        
        if ($currentStepIndex === false) return;
        
        $nextStepIndex = $currentStepIndex + 1;
        $currentStep = $steps[$currentStepIndex];
        
        $statusMapCurrent = [
            'responsable-service' => 'approved_service',
            'responsable-budget' => 'approved_budget',
            'service-achat' => 'approved_achat',
        ];

        $newStatus = $statusMapCurrent[$currentStep] ?? 'approved_budget';

        if ($nextStepIndex >= count($steps)) {
            $this->update(['statut' => $newStatus, 'current_step' => null]);
        } else {
            $nextStep = $steps[$nextStepIndex];
            $this->update([
                'statut' => $newStatus,
                'current_step' => $nextStep
            ]);
        }
    }

    public function getCurrentApprovalStepKey(): ?string
    {
        return $this->current_step ?? 'responsable-service';
    }

    public function getCurrentApprovalStepLabel(): ?string
    {
        $currentStepKey = $this->getCurrentApprovalStepKey();
        if ($currentStepKey) {
            $steps = $this->approvalSteps();
            return $steps[$currentStepKey]['label'] ?? $currentStepKey;
        }
        return $this->isFullyApproved() ? 'Terminé' : ($this->isRejected() ? 'Rejeté' : 'N/A');
    }

    public function isFullyApproved(): bool
    {
        return $this->statut === 'delivered';
    }

    public function isRejected(): bool
    {
        return $this->statut === 'rejected';
    }}
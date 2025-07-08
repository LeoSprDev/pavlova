<?php

namespace App\Contracts;

interface Approvable
{
    public function approvalWorkflow(): string;
    public function approvalSteps(): array;
    public function canBeApproved(): bool;
    public function getCurrentApprovalStepKey(): ?string;
    public function getCurrentApprovalStepLabel(): ?string;
    public function isFullyApproved(): bool;
    public function isRejected(): bool;
}
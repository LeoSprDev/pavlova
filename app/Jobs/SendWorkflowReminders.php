<?php
namespace App\Jobs;

use App\Models\DemandeDevis;
use App\Services\WorkflowNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWorkflowReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WorkflowNotificationService $service): void
    {
        DemandeDevis::whereIn('statut', [
            'pending_manager',
            'pending_direction',
            'pending_achat',
            'pending_delivery'
        ])->where('updated_at', '<', now()->subDays(2))
            ->each(fn($demande) => $service->notifyNextApprovers($demande));
    }
}

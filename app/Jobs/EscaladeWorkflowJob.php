<?php
namespace App\Jobs;

use App\Models\{DemandeDevis, User};
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EscaladeWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('🔄 Démarrage EscaladeWorkflowJob');

        DemandeDevis::whereNotNull('current_step')
            ->whereNotIn('statut', ['rejected', 'delivered_confirmed'])
            ->chunk(100, function ($demandes) {
                foreach ($demandes as $demande) {
                    $days = $demande->updated_at->diffInDays(now());

                    if ($days >= 10 && $demande->prix_total_ttc < 1000) {
                        $demande->update([
                            'statut' => 'auto_validated',
                            'current_step' => 'pending_delivery',
                        ]);
                        $this->notify($demande->createdBy, "Demande #{$demande->id} validée automatiquement");
                        continue;
                    }

                    if ($days >= 7) {
                        $direction = User::role('responsable-direction')->get();
                        $this->notify($direction, "Escalade direction pour demande #{$demande->id}");
                        continue;
                    }

                    if ($days >= 5) {
                        $hierarchy = User::role('responsable-budget')->get();
                        $this->notify($hierarchy, "Relance hiérarchie pour demande #{$demande->id}");
                        continue;
                    }

                    if ($days >= 3) {
                        $this->notify($demande->createdBy, "Rappel traitement demande #{$demande->id}");
                    }
                }
            });

        Log::info('✅ Fin EscaladeWorkflowJob');
    }

    private function notify($users, string $message): void
    {
        if (!$users) {
            return;
        }
        Notification::make()
            ->title('⏰ Escalade workflow')
            ->body($message)
            ->sendToDatabase($users instanceof \Illuminate\Support\Collection ? $users->all() : [$users]);
    }
}

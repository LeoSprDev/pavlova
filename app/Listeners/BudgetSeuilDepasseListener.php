<?php
namespace App\Listeners;

use App\Events\BudgetSeuilDepasse;
use App\Models\User;
use App\Notifications\BudgetDepassementNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BudgetSeuilDepasseListener
{
    public function handle(BudgetSeuilDepasse $event): void
    {
        // Notification responsable budget
        $responsableBudget = User::role('responsable-budget')->first();
        if ($responsableBudget) {
            $responsableBudget->notify(new BudgetDepassementNotification($event->ligne));
        }

        // Email responsable service
        if ($event->ligne->service->responsable_email) {
            // Mail::to($event->ligne->service->responsable_email)
            //     ->send(new BudgetDepassementMail($event->ligne, $event->montantDepassement));
        }

        // Log audit
        Log::warning('Budget dépassé', [
            'service_id' => $event->ligne->service_id,
            'ligne_id' => $event->ligne->id,
            'montant_prevu' => $event->ligne->montant_ht_prevu,
            'montant_depassement' => $event->montantDepassement,
            'type_seuil' => $event->typeSeuil
        ]);
    }
}

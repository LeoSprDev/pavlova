<?php

namespace App\Observers;

use App\Models\{DemandeDevis, BudgetLigne, User};
use App\Services\WorkflowNotificationService;
use Filament\Notifications\Notification;

class DemandeDevisObserver
{
    public function updating(DemandeDevis $demande): void
    {
        if ($demande->isDirty('statut')) {
            $oldStatus = $demande->getOriginal('statut');
            $newStatus = $demande->statut;

            if ($newStatus === 'approved_achat') {
                $demande->statut = 'ready_for_order';
                $newStatus = 'ready_for_order';
            }

            $this->handleBudgetEngagement($demande, $oldStatus, $newStatus);
            $this->sendWorkflowNotifications($demande, $newStatus);

            if ($newStatus === 'delivered') {
                $this->updateRelatedCommandStatus($demande);
            }

            if ($newStatus === 'delivered_confirmed') {
                $this->finalizeBudgetConsumption($demande);
                $this->finaliserWorkflowComplet($demande);
            }
        }
    }

    private function handleBudgetEngagement(DemandeDevis $demande, string $oldStatus, string $newStatus): void
    {
        $budgetLigne = $demande->budgetLigne;
        if (!$budgetLigne) {
            return;
        }

        if ($newStatus === 'approved_direction' && $oldStatus !== 'approved_direction') {
            $budgetLigne->engagerBudget($demande->prix_total_ttc, $demande);
        }

        if (in_array($newStatus, ['rejected', 'cancelled']) && in_array($oldStatus, ['approved_direction', 'approved_achat', 'ready_for_order', 'ordered'])) {
            $budgetLigne->desengagerBudget($demande);
        }
    }

    private function updateRelatedCommandStatus(DemandeDevis $demande): void
    {
        if ($demande->id) {
            $commande = \App\Models\Commande::where('demande_devis_id', $demande->id)->first();
            if ($commande && $commande->statut === 'en_cours') {
                $commande->update(['statut' => 'livree']);
            }
        }
    }

    private function sendWorkflowNotifications(DemandeDevis $demande, string $newStatus): void
    {
        $recipients = $this->getNotificationRecipients($demande, $newStatus);
        $service = new WorkflowNotificationService();

        foreach ($recipients as $user) {
            Notification::make()
                ->title($this->getNotificationTitle($newStatus))
                ->body($this->getNotificationBody($demande, $newStatus))
                ->icon($this->getNotificationIcon($newStatus))
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Voir demande')
                        ->url("/admin/demande-devis/{$demande->id}")
                        ->button(),
                ])
                ->sendToDatabase($user);
        }

        $service->notifyNextApprovers($demande);
    }


    private function finalizeBudgetConsumption(DemandeDevis $demande): void
    {
        $budgetLigne = $demande->budgetLigne;
        if ($budgetLigne) {
            $budgetLigne->decrement('montant_engage', $demande->prix_total_ttc);
            $budgetLigne->increment('montant_depense_reel', $demande->prix_total_ttc);
        }
    }


    private function finaliserWorkflowComplet(DemandeDevis $demande): void
    {
        \DB::transaction(function () use ($demande) {
            $demande->budgetLigne?->finaliserBudgetReel($demande->prix_total_ttc);
            $this->archiverWorkflow($demande);
            $this->envoyerNotificationsFinales($demande);
        });
    }

    private function archiverWorkflow(DemandeDevis $demande): void
    {
        // Placeholder for archive logic
    }

    private function envoyerNotificationsFinales(DemandeDevis $demande): void
    {
        Notification::make()
            ->title('Workflow terminé')
            ->body("Demande #{$demande->id} finalisée")
            ->sendToDatabase([$demande->createdBy]);
    }
    private function getNotificationRecipients(DemandeDevis $demande, string $status): array
    {
        return User::role($demande->approvalSteps()[$status]['role'] ?? 'responsable-budget')->get()->all();
    }

    private function getNotificationTitle(string $status): string
    {
        return match ($status) {
            'approved_direction' => 'Validation Direction',
            'approved_achat' => 'Validation Achat',
            'ready_for_order' => 'Préparation commande',
            'delivered_confirmed' => 'Livraison confirmée',
            'rejected' => 'Demande rejetée',
            default => 'Mise à jour de demande',
        };
    }

    private function getNotificationBody(DemandeDevis $demande, string $status): string
    {
        return "La demande {$demande->denomination} est maintenant {$status}.";
    }

    private function getNotificationIcon(string $status): string
    {
        return match ($status) {
            'approved_direction' => 'heroicon-o-check-circle',
            'approved_achat' => 'heroicon-o-shopping-cart',
            'ready_for_order' => 'heroicon-o-clipboard-document-check',
            'delivered_confirmed' => 'heroicon-o-truck',
            'rejected' => 'heroicon-o-x-circle',
            default => 'heroicon-o-information-circle',
        };
    }
}

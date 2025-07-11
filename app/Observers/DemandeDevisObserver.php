<?php

namespace App\Observers;

use App\Models\{DemandeDevis, BudgetLigne, Commande, User};
use Filament\Notifications\Notification;

class DemandeDevisObserver
{
    public function updating(DemandeDevis $demande): void
    {
        if ($demande->isDirty('statut')) {
            $oldStatus = $demande->getOriginal('statut');
            $newStatus = $demande->statut;

            $this->handleBudgetEngagement($demande, $oldStatus, $newStatus);
            $this->sendWorkflowNotifications($demande, $newStatus);

            if ($newStatus === 'approved_achat') {
                $this->createCommandeAutomatique($demande);
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
            if (! $budgetLigne->engagerBudget($demande->prix_total_ttc, $demande)) {
                throw new \Exception("Budget insuffisant pour engager {$demande->prix_total_ttc}€");
            }
        }

        if (in_array($newStatus, ['rejected', 'cancelled']) && in_array($oldStatus, ['approved_direction', 'approved_achat', 'ordered'])) {
            $budgetLigne->desengagerBudget($demande);
        }
    }

    private function sendWorkflowNotifications(DemandeDevis $demande, string $newStatus): void
    {
        $recipients = $this->getNotificationRecipients($demande, $newStatus);

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
    }

    private function createCommandeAutomatique(DemandeDevis $demande): void
    {
        if (!$demande->commande) {
            Commande::create([
                'demande_devis_id' => $demande->id,
                'numero_commande' => 'CMD-' . now()->format('Y') . '-' . str_pad($demande->id, 6, '0', STR_PAD_LEFT),
                'fournisseur_nom' => $demande->fournisseur_propose,
                'montant_ht' => $demande->prix_unitaire_ht * $demande->quantite,
                'montant_ttc' => $demande->prix_total_ttc,
                'statut' => 'en_cours',
                'date_commande' => now(),
                'service_demandeur_id' => $demande->service_demandeur_id,
            ]);
        }
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
            'delivered_confirmed' => 'heroicon-o-truck',
            'rejected' => 'heroicon-o-x-circle',
            default => 'heroicon-o-information-circle',
        };
    }
}

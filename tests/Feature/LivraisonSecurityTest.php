<?php
use App\Models\{Service, User, Livraison, BudgetLigne, DemandeDevis};
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\LivraisonConformeConfirmeeEmail;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ExtendedRolePermissionSeeder::class);
});

describe('Livraison Security & Workflow', function () {
    test('seul service demandeur peut confirmer SES livraisons', function () {
        $serviceA = Service::factory()->create();
        $serviceB = Service::factory()->create();

        $agentA = User::factory()->create(['service_id' => $serviceA->id]);
        $agentB = User::factory()->create(['service_id' => $serviceB->id]);
        $agentA->assignRole('agent-service');
        $agentB->assignRole('agent-service');

        $livraison = Livraison::factory()->create([
            'commande_id' => function () use ($serviceA) {
                $budget = BudgetLigne::factory()->create(['service_id' => $serviceA->id]);
                $demande = DemandeDevis::factory()->create([
                    'service_demandeur_id' => $serviceA->id,
                    'budget_ligne_id' => $budget->id,
                ]);
                return \App\Models\Commande::factory()->create(['demande_devis_id' => $demande->id])->id;
            },
        ]);

        $policy = new \App\Policies\LivraisonPolicy();

        expect($policy->update($agentA, $livraison))->toBeTrue();
        expect($policy->update($agentB, $livraison))->toBeFalse();
    });

    test('upload bon livraison OBLIGATOIRE pour finaliser', function () {
        $agent = User::factory()->create();
        $agent->assignRole('agent-service');

        $validator = \Illuminate\Support\Facades\Validator::make([
            'commande_id' => 1,
            'date_livraison_prevue' => now(),
            'statut_reception' => 'recu_conforme',
        ], [
            'bons_livraison' => 'required'
        ]);

        expect($validator->fails())->toBeTrue();
    });

    test('budget mis à jour automatiquement après livraison conforme', function () {
        $budgetLigne = BudgetLigne::factory()->create(['montant_depense_reel' => 1000]);
        $demandeDevis = DemandeDevis::factory()->create([
            'budget_ligne_id' => $budgetLigne->id,
            'prix_fournisseur_final' => 600,
            'prix_total_ttc' => 500
        ]);
        $commande = \App\Models\Commande::factory()->create(['demande_devis_id' => $demandeDevis->id]);
        $livraison = Livraison::factory()->create([
            'commande_id' => $commande->id,
            'conforme' => false,
        ]);

        $livraison->update(['conforme' => true]);
        $livraison->sendNotificationLivraisonConforme();

        expect((float) $budgetLigne->fresh()->montant_depense_reel)->toBe(1600.0);
    });

    test('notifications envoyées après validation livraison', function () {
        Notification::fake();
        Mail::fake();

        $responsable = User::factory()->create();
        $responsable->assignRole('responsable-budget');

        $livraison = Livraison::factory()->create();

        $livraison->update(['conforme' => true]);

        Mail::assertSent(LivraisonConformeConfirmeeEmail::class);
    });
});

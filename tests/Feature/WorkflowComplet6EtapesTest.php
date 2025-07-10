<?php
use App\Models\{Service, User, BudgetLigne, DemandeDevis, Livraison};
use App\Jobs\RelanceLivraisonEnRetard;
use App\Mail\{LivraisonConformeConfirmeeEmail, RelanceLivraisonEmail};
use Illuminate\Support\Facades\{Mail, Queue, Storage};

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ExtendedRolePermissionSeeder::class);
});

describe('Workflow Complet 6 Étapes', function () {
    test('workflow end-to-end avec devis fournisseur et livraison', function () {
        Mail::fake();
        Queue::fake();

        // Setup données test
        $service = Service::factory()->create();
        $budget = BudgetLigne::factory()->create([
            'service_id' => $service->id,
            'montant_ht_prevu' => 5000,
            'montant_depense_reel' => 1000
        ]);

        $agent = User::factory()->create(['service_id' => $service->id]);
        $agent->assignRole('agent-service');

        // ÉTAPE 1: Agent crée demande
        $demande = DemandeDevis::factory()->create([
            'service_demandeur_id' => $service->id,
            'budget_ligne_id' => $budget->id,
            'created_by' => $agent->id,
            'prix_total_ttc' => 800,
            'statut' => 'pending'
        ]);

        // ÉTAPE 5: Service achat upload devis fournisseur
        $demande->update([
            'statut' => 'approved_achat',
            'prix_fournisseur_final' => 750,
            'devis_fournisseur_valide' => true
        ]);

        // ÉTAPE 6: Service demandeur réceptionne + upload bon
        Storage::fake('local');
        $commande = \App\Models\Commande::factory()->create([
            'demande_devis_id' => $demande->id
        ]);

        $livraison = Livraison::factory()->create([
            'commande_id' => $commande->id,
            'statut_reception' => 'en_attente',
            'conforme' => false
        ]);

        $livraison->addMediaFromString('Bon de livraison PDF content')
            ->usingName('bon_livraison.pdf')
            ->toMediaCollection('bons_livraison');

        $livraison->update([
            'conforme' => true,
            'statut_reception' => 'recu_conforme'
        ]);

        expect($demande->fresh()->statut)->toBe('delivered');
        expect($budget->fresh()->montant_depense_reel)->toBe(1750.0);

        Mail::assertQueued(LivraisonConformeConfirmeeEmail::class);
    });

    test('job relance livraison en retard fonctionne', function () {
        Mail::fake();

        $livraison = Livraison::factory()->create([
            'date_livraison_prevue' => now()->subDays(8),
            'statut_reception' => 'en_attente'
        ]);

        (new RelanceLivraisonEnRetard)->handle();

        Mail::assertQueued(RelanceLivraisonEmail::class);
    });
});

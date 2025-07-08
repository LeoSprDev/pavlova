<?php

use App\Models\{Service, User, BudgetLigne, DemandeDevis};

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $this->service = Service::factory()->create();
    $this->budget = BudgetLigne::factory()->create([
        'service_id' => $this->service->id,
        'montant_ht_prevu' => 2000,
        'valide_budget' => 'oui'
    ]);

    $this->demandeur = User::factory()->create(['service_id' => $this->service->id]);
    $this->demandeur->assignRole('service-demandeur');

    $this->responsableBudget = User::factory()->create();
    $this->responsableBudget->assignRole('responsable-budget');

    $this->serviceAchat = User::factory()->create();
    $this->serviceAchat->assignRole('service-achat');
});

test('workflow complet demande vers livraison fonctionne', function () {
    // Création demande
    $response = $this->actingAs($this->demandeur)
        ->post('/admin/demande-devis', [
            'budget_ligne_id' => $this->budget->id,
            'denomination' => 'Test Produit',
            'prix_total_ttc' => 1000,
            'quantite' => 1,
            'prix_unitaire_ht' => 833.33,
            'fournisseur_propose' => 'Test Fournisseur',
            'justification_besoin' => 'Test justification',
            'urgence' => 'normale',
            'date_besoin' => now()->addDays(30)->format('Y-m-d')
        ]);

    expect($response->status())->toBe(201);

    $demande = DemandeDevis::first();
    expect($demande->statut)->toBe('pending');

    // Validation budget
    $this->actingAs($this->responsableBudget);
    $demande->approve($this->responsableBudget, 'Approuvé budget');
    expect($demande->fresh()->statut)->toBe('approved_budget');

    // Validation achat
    $this->actingAs($this->serviceAchat);
    $demande->approve($this->serviceAchat, 'Approuvé achat');
    expect($demande->fresh()->statut)->toBe('approved_achat');
});

test('cloisonnement service strict empêche accès non autorisé', function () {
    $autreService = Service::factory()->create();
    $autreBudget = BudgetLigne::factory()->create(['service_id' => $autreService->id]);

    $this->actingAs($this->demandeur)
        ->get("/admin/budget-lignes/{$autreBudget->id}")
        ->assertStatus(403);
});

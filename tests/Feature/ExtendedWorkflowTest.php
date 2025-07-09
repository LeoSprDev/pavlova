<?php
use App\Models\{Service, User, BudgetLigne, DemandeDevis};

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ExtendedRolePermissionSeeder::class);

    $this->service = Service::factory()->create();
    $this->budget = BudgetLigne::factory()->create([
        'service_id' => $this->service->id,
        'montant_ht_prevu' => 1000,
        'valide_budget' => 'oui'
    ]);

    $this->agent = User::factory()->create(['service_id' => $this->service->id]);
    $this->agent->assignRole('agent-service');

    $this->responsableService = User::factory()->create(['service_id' => $this->service->id, 'is_service_responsable' => true]);
    $this->responsableService->assignRole('responsable-service');

    $this->responsableBudget = User::factory()->create();
    $this->responsableBudget->assignRole('responsable-budget');

    $this->serviceAchat = User::factory()->create();
    $this->serviceAchat->assignRole('service-achat');
});

test('workflow 4 niveaux fonctionne', function () {
    $demande = DemandeDevis::factory()->create([
        'service_demandeur_id' => $this->service->id,
        'budget_ligne_id' => $this->budget->id,
        'created_by' => $this->agent->id,
        'statut' => 'pending',
    ]);

    $demande->approve($this->responsableService, 'ok');
    expect($demande->fresh()->statut)->toBe('approved_service');

    $demande->approve($this->responsableBudget, 'ok');
    expect($demande->fresh()->statut)->toBe('approved_budget');

    $demande->approve($this->serviceAchat, 'ok');
    expect($demande->fresh()->statut)->toBe('approved_achat');
});

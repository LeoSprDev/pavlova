<?php
use App\Mail\{LivraisonConformeConfirmeeEmail, RelanceLivraisonEmail};
use App\Models\{Livraison, Service, BudgetLigne, DemandeDevis};

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ExtendedRolePermissionSeeder::class);
});

test('template livraison conforme contient bonnes données', function () {
    $service = Service::factory()->create();
    $budget = BudgetLigne::factory()->create(['service_id' => $service->id]);
    $demande = DemandeDevis::factory()->create([
        'service_demandeur_id' => $service->id,
        'budget_ligne_id' => $budget->id,
    ]);
    $commande = \App\Models\Commande::factory()->create(['demande_devis_id' => $demande->id]);
    $livraison = Livraison::factory()->create(['commande_id' => $commande->id]);
    $email = new LivraisonConformeConfirmeeEmail($livraison);

    $rendered = $email->build()->render();

    expect($rendered)->toContain('Livraison Conforme Validée');
    expect($rendered)->toContain($livraison->commande->demandeDevis->denomination);
    expect($rendered)->toContain('budget ligne');
});

test('template relance contient alerte retard', function () {
    $service = Service::factory()->create();
    $budget = BudgetLigne::factory()->create(['service_id' => $service->id]);
    $demande = DemandeDevis::factory()->create([
        'service_demandeur_id' => $service->id,
        'budget_ligne_id' => $budget->id,
    ]);
    $commande = \App\Models\Commande::factory()->create(['demande_devis_id' => $demande->id]);
    $livraison = Livraison::factory()->create([
        'commande_id' => $commande->id,
        'date_livraison_prevue' => now()->subDays(10)
    ]);
    $email = new RelanceLivraisonEmail($livraison);

    $rendered = $email->build()->render();

    expect($rendered)->toContain('En retard de');
    expect($rendered)->toContain('ACTION REQUISE');
    expect($rendered)->toContain('CONFIRMER RÉCEPTION');
});

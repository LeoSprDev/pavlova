<?php
use App\Models\{Service, User, BudgetLigne, DemandeDevis};
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(\Database\Seeders\ExtendedRolePermissionSeeder::class);
    Spatie\Permission\Models\Role::firstOrCreate(['name' => 'responsable-budget']);
});

test('workflow 6 etapes avec automatisations finales', function () {
    Mail::fake();

    $service = Service::factory()->create();
    $budget = BudgetLigne::factory()->create([
        'service_id' => $service->id,
        'montant_ht_prevu' => 5000,
        'montant_depense_reel' => 1000
    ]);

    $agent = User::factory()->create(['service_id' => $service->id]);
    $agent->assignRole('agent-service');

    $demande = DemandeDevis::factory()->create([
        'service_demandeur_id' => $service->id,
        'budget_ligne_id' => $budget->id,
        'created_by' => $agent->id,
        'prix_total_ttc' => 800,
        'statut' => 'approved_achat',
        'prix_fournisseur_final' => 750
    ]);

    $commande = \App\Models\Commande::factory()->create([
        'demande_devis_id' => $demande->id
    ]);

    $livraison = \App\Models\Livraison::factory()->create([
        'commande_id' => $commande->id,
        'statut_reception' => 'en_attente'
    ]);

    $pdfBase64 = base64_encode('%PDF-1.4 test');
    $livraison->addMediaFromBase64($pdfBase64, 'application/pdf')
        ->usingFileName('bon_livraison.pdf')
        ->toMediaCollection('bons_livraison');

    $livraison->update([
        'conforme' => true,
        'statut_reception' => 'recu_conforme'
    ]);

    expect($demande->fresh()->statut)->toBe('delivered');
    expect((float) $budget->fresh()->montant_depense_reel)->toBe(1750.0);
    expect($livraison->fresh()->workflow_complete)->toBeTrue();

    Mail::assertQueued(\App\Mail\LivraisonConformeConfirmeeEmail::class);
});

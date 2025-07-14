<?php
use App\Models\{User, Service, DemandeDevis};

beforeEach(function () {
    $this->seed(\Database\Seeders\ExtendedRolePermissionSeeder::class);
});

test('navigation agent service affiche bonnes sections', function () {
    $service = Service::factory()->create();
    $agent = User::factory()->create(['service_id' => $service->id]);
    $agent->assignRole('agent-service');

    $response = $this->actingAs($agent)->get('/admin');

    $response->assertStatus(200);
    $response->assertSee('Mon Espace Agent');
    $response->assertSee('Mes Demandes');
    $response->assertSee('Nouvelle Demande');
    $response->assertDontSee('Gestion Budget');
});

test('navigation responsable service affiche validation', function () {
    $service = Service::factory()->create();
    $responsable = User::factory()->create([
        'service_id' => $service->id,
        'is_service_responsable' => true
    ]);
    $responsable->assignRole('responsable-service');

    $response = $this->actingAs($responsable)->get('/admin');

    $response->assertSee('Gestion Service');
    $response->assertSee('Demandes à Valider');
    $response->assertSee('Budget Mon Service');
});

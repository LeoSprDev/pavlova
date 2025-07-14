<?php
use App\Models\{Service, User, BudgetLigne, DemandeDevis};

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ExtendedRolePermissionSeeder::class);

    $this->service = Service::factory()->create();
    $this->budget = BudgetLigne::factory()->create(['service_id' => $this->service->id]);

    $this->agent = User::factory()->create(['service_id' => $this->service->id]);
    $this->agent->assignRole('agent-service');
});

test('mes demandes accessible', function () {
    $this->actingAs($this->agent)
        ->get('/admin/mes-demandes')
        ->assertStatus(200);
});

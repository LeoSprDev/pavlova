<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\DemandeDevis;
use App\Models\BudgetLigne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class WorkflowExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function agent_can_create_demande_for_responsable_validation()
    {
        $service = Service::factory()->create();
        $agent = User::factory()->create([
            'service_id' => $service->id,
            'is_service_responsable' => false
        ]);
        $agent->assignRole('agent-service');

        $budgetLigne = BudgetLigne::factory()->create([
            'service_id' => $service->id,
            'valide_budget' => 'oui'
        ]);

        $this->actingAs($agent);
        
        $demande = DemandeDevis::create([
            'user_id' => $agent->id,
            'service_demandeur_id' => $service->id,
            'budget_ligne_id' => $budgetLigne->id,
            'denomination' => 'Test demande',
            'prix_total_ttc' => 100.00,
            'current_step' => 'validation-responsable-service',
            'statut' => 'pending'
        ]);

        $this->assertDatabaseHas('demande_devis', [
            'id' => $demande->id,
            'current_step' => 'validation-responsable-service'
        ]);
    }

    /** @test */
    public function responsable_service_can_validate_only_own_service_requests()
    {
        $service1 = Service::factory()->create();
        $service2 = Service::factory()->create();
        
        $responsable = User::factory()->create([
            'service_id' => $service1->id,
            'is_service_responsable' => true
        ]);
        $responsable->assignRole('responsable-service');

        $budgetLigne = BudgetLigne::factory()->create([
            'service_id' => $service2->id,
            'valide_budget' => 'oui'
        ]);

        $demande = DemandeDevis::factory()->create([
            'service_demandeur_id' => $service2->id,
            'budget_ligne_id' => $budgetLigne->id,
            'current_step' => 'validation-responsable-service'
        ]);

        $this->assertTrue($responsable->isResponsableOfService($service1));
        $this->assertFalse($responsable->isResponsableOfService($service2));
    }

    /** @test */
    public function service_has_active_scope()
    {
        $activeService = Service::factory()->create(['actif' => true]);
        $inactiveService = Service::factory()->create(['actif' => false]);

        $activeServices = Service::actifs()->get();
        
        $this->assertTrue($activeServices->contains($activeService));
        $this->assertFalse($activeServices->contains($inactiveService));
    }

    /** @test */
    public function workflow_maintains_retrocompatibility()
    {
        $service = Service::factory()->create();
        $budgetLigne = BudgetLigne::factory()->create([
            'service_id' => $service->id,
            'valide_budget' => 'oui'
        ]);

        $demande = DemandeDevis::factory()->create([
            'service_demandeur_id' => $service->id,
            'budget_ligne_id' => $budgetLigne->id,
            'current_step' => 'validation-budget'
        ]);

        $this->assertTrue($demande->canBeApproved());
        $this->assertEquals('validation-budget', $demande->getCurrentApprovalStepKey());
    }

    /** @test */
    public function service_can_get_agents_and_responsables()
    {
        $service = Service::factory()->create();
        
        $agent = User::factory()->create([
            'service_id' => $service->id,
            'is_service_responsable' => false
        ]);
        
        $responsable = User::factory()->create([
            'service_id' => $service->id,
            'is_service_responsable' => true
        ]);

        $this->assertTrue($service->agents->contains($agent));
        $this->assertFalse($service->agents->contains($responsable));
        
        $this->assertTrue($service->responsables->contains($responsable));
        $this->assertFalse($service->responsables->contains($agent));
    }
}

<?php
use App\Models\{User, Service};
use Filament\Facades\Filament;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ExtendedRolePermissionSeeder::class);
});

test('navigation filament admin accessible', function () {
    $service = Service::factory()->create();
    $agent = User::factory()->create(['service_id' => $service->id]);
    $agent->assignRole('agent-service');

    $response = $this->actingAs($agent)->get('/admin');

    $response->assertStatus(200);
    $response->assertDontSee('Route [filament.admin.resources.budget-lignes.index] not defined');
    $response->assertSee('Mon Espace Agent');
});

test('navigation responsable budget fonctionne', function () {
    $responsable = User::factory()->create();
    $responsable->assignRole('responsable-budget');

    $response = $this->actingAs($responsable)->get('/admin');

    $response->assertStatus(200);
    $response->assertSee('Gestion Budget');
    $response->assertDontSee('RouteNotFoundException');
});

test('toutes les routes navigation sont valides', function () {
    $admin = User::factory()->create();
    $admin->assignRole('administrateur');

    $this->actingAs($admin);

    $panel = Filament::getPanel('admin');
    Filament::setCurrentPanel($panel);
    $navigation = $panel->getNavigation();

    foreach ($navigation as $group) {
        foreach ($group->getItems() as $item) {
            $url = $item->getUrl();
            expect($url)->not()->toBeNull();
            expect(fn () => $this->get($url))->not()->toThrow(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);
        }
    }
});

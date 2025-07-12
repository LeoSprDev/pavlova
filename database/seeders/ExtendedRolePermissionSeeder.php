<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use App\Models\{User, Service};

class ExtendedRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Créer les nouveaux rôles
        $agentService = Role::firstOrCreate(['name' => 'agent-service']);
        $responsableService = Role::firstOrCreate(['name' => 'responsable-service']);

        Permission::firstOrCreate(['name' => 'view_own_demandes']);
        Permission::firstOrCreate(['name' => 'create_demande_devis']);
        Permission::firstOrCreate(['name' => 'update_own_demandes']);
        Permission::firstOrCreate(['name' => 'validate_service_requests']);
        Permission::firstOrCreate(['name' => 'view_service_demandes']);
        Permission::firstOrCreate(['name' => 'approve_service_demande']);

        // Permissions pour agent-service
        $agentService->givePermissionTo([
            'view_own_demandes',
            'create_demande_devis',
            'update_own_demandes',
        ]);

        // Permissions pour responsable-service
        $responsableService->givePermissionTo([
            'validate_service_requests',
            'view_service_demandes',
            'approve_service_demande',
        ]);

        // Créer utilisateurs test avec nouveaux rôles
        foreach (['IT', 'RH', 'MKT'] as $serviceCode) {
            $service = Service::firstOrCreate(
                ['code' => 'S' . $serviceCode],
                ['nom' => $serviceCode]
            );

            // Agent du service
            $agent = User::create([
                'name' => "Agent {$serviceCode}",
                'email' => "agent.{$serviceCode}@test.local",
                'password' => Hash::make('password'),
                'service_id' => $service->id,
                'force_password_change' => true,
            ]);
            $agent->assignRole('agent-service');

            // Responsable du service
            $responsable = User::create([
                'name' => "Responsable {$serviceCode}",
                'email' => "responsable.{$serviceCode}@test.local",
                'password' => Hash::make('password'),
                'service_id' => $service->id,
                'is_service_responsable' => true,
                'force_password_change' => false,
            ]);
            $responsable->assignRole('responsable-service');
        }
    }
}

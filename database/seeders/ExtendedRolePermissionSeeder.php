<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class ExtendedRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@test.local')->first();
        if (! $adminUser) {
            echo "Compte admin@test.local introuvable!"; return;
        }

        $agentService = Role::firstOrCreate(['name' => 'agent-service']);
        $responsableService = Role::firstOrCreate(['name' => 'responsable-service']);

        Permission::firstOrCreate(['name' => 'manage_users_extended']);
        Permission::firstOrCreate(['name' => 'view_own_demandes']);
        Permission::firstOrCreate(['name' => 'create_own_demandes']);
        Permission::firstOrCreate(['name' => 'validate_service_requests']);

        $adminRole = Role::findByName('administrateur');
        $adminRole->givePermissionTo(['manage_users_extended']);
    }
}

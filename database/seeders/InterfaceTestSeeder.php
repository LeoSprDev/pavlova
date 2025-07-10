<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Service, BudgetLigne, DemandeDevis};
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class InterfaceTestSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'agent-service', 'responsable-service', 'responsable-budget', 'service-achat', 'reception-livraison'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $serviceIT = Service::firstOrCreate(['nom' => 'Service IT', 'code' => 'IT']);
        $serviceRH = Service::firstOrCreate(['nom' => 'Ressources Humaines', 'code' => 'RH']);

        BudgetLigne::firstOrCreate([
            'service_id' => $serviceIT->id,
            'intitule' => 'Matériel Informatique',
            'montant_ht_prevu' => 10000,
            'montant_depense_reel' => 3500,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@codex.local'],
            ['name' => 'Admin Codex', 'password' => Hash::make('password'), 'service_id' => $serviceIT->id]
        );
        $admin->assignRole('admin');

        $agent = User::firstOrCreate(
            ['email' => 'agent@codex.local'],
            ['name' => 'Agent Test', 'password' => Hash::make('password'), 'service_id' => $serviceIT->id]
        );
        $agent->assignRole('agent-service');

        $responsable = User::firstOrCreate(
            ['email' => 'responsable@codex.local'],
            ['name' => 'Responsable Test', 'password' => Hash::make('password'), 'service_id' => $serviceIT->id]
        );
        $responsable->assignRole('responsable-service');

        DemandeDevis::factory(5)->create([
            'created_by' => $agent->id,
            'service_demandeur_id' => $serviceIT->id,
            'statut' => 'pending',
        ]);
    }
}

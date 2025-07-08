<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Service;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // UTILISATEURS POUR TESTS HUMAINS
        $services = Service::all();
        
        // 1. Administrateur général
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('administrateur');
        
        // 2. Responsable Budget
        $rb = User::create([
            'name' => 'Responsable Budget',
            'email' => 'budget@test.local', 
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $rb->assignRole('responsable-budget');
        
        // 3. Service Achat
        $sa = User::create([
            'name' => 'Service Achat User', // Changed name for clarity
            'email' => 'achat@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $sa->assignRole('service-achat');

        // Get IT and RH services created in DemoDataCompletSeeder
        $serviceIT = Service::where('code', 'IT')->first();
        $serviceRH = Service::where('code', 'RH')->first();

        // 4. Utilisateurs spécifiques pour le nouveau workflow
        if ($serviceIT) {
            $agentIT = User::firstOrCreate(
                ['email' => 'agent.service.it@test.local'],
                [
                    'name' => 'Agent Service IT (Didier)',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'service_id' => $serviceIT->id,
                ]
            );
            $agentIT->assignRole('agent-service');

            $responsableIT = User::firstOrCreate(
                ['email' => 'responsable.service.it@test.local'],
                [
                    'name' => 'Responsable Service IT',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'service_id' => $serviceIT->id,
                    'is_service_responsable' => true,
                ]
            );
            $responsableIT->assignRole('responsable-service');
        }

        if ($serviceRH) {
            $agentRH = User::firstOrCreate(
                ['email' => 'agent.service.rh@test.local'],
                [
                    'name' => 'Agent Service RH',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'service_id' => $serviceRH->id,
                ]
            );
            $agentRH->assignRole('agent-service');

            $responsableRH = User::firstOrCreate(
                ['email' => 'responsable.service.rh@test.local'],
                [
                    'name' => 'Responsable Service RH',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'service_id' => $serviceRH->id,
                    'is_service_responsable' => true,
                ]
            );
            $responsableRH->assignRole('responsable-service');
        }
        
        // Optionnel: Conserver la création générique des anciens "service-demandeur"
        // si d'autres services existent et qu'on veut maintenir ces comptes.
        // Sinon, cette boucle peut être supprimée ou modifiée.
        // Pour l'instant, je la commente pour éviter confusion avec les nouveaux rôles.
        /*
        foreach($services as $service) {
            // Skip IT and RH if we only want the new specific users for them
            if (($serviceIT && $service->id === $serviceIT->id) || ($serviceRH && $service->id === $serviceRH->id)) {
                continue;
            }
            $demandeur = User::firstOrCreate(
                ['email' => "demandeur.{$service->code}@test.local"],
                [
                    'name' => "Demandeur {$service->nom} (Ancien Rôle)",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'service_id' => $service->id,
                ]
            );
            $demandeur->assignRole('service-demandeur');
        }
        */
    }
}

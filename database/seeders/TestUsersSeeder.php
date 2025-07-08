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
            'name' => 'Service Achat',
            'email' => 'achat@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $sa->assignRole('service-achat');
        
        // 4. Demandeurs par service
        foreach($services as $service) {
            $demandeur = User::create([
                'name' => "Demandeur {$service->nom}",
                'email' => "demandeur.{$service->code}@test.local",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'service_id' => $service->id,
            ]);
            $demandeur->assignRole('service-demandeur');
        }
    }
}

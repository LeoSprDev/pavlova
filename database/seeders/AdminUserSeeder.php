<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Service;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer ou récupérer le service Administration
        $serviceAdmin = Service::firstOrCreate([
            'code' => 'ADMIN'
        ], [
            'nom' => 'Administration',
            'description' => 'Service administration système',
            'actif' => true,
            'budget_annuel_alloue' => 0,
            'responsable_email' => 'admin@test.local'
        ]);

        // Créer ou mettre à jour l'utilisateur admin
        $admin = User::updateOrCreate([
            'email' => 'admin@test.local'
        ], [
            'name' => 'Administrateur',
            'password' => Hash::make('password'), // Mot de passe par défaut
            'service_id' => $serviceAdmin->id,
            'is_service_responsable' => true,
            'email_verified_at' => now()
        ]);

        // S'assurer que l'admin a le rôle administrateur
        if (!$admin->hasRole('administrateur')) {
            $admin->assignRole('administrateur');
        }

        $this->command->info('Utilisateur admin@test.local configuré avec succès');
        $this->command->info('Service: ' . $serviceAdmin->nom);
        $this->command->info('Rôles: ' . $admin->roles->pluck('name')->implode(', '));
    }
}
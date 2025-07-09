<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Service;
use Illuminate\Support\Facades\Hash;

class SetupAdminCommand extends Command
{
    protected $signature = 'admin:setup 
                            {--email=admin@test.local : Admin email address}
                            {--password=password : Admin password}
                            {--name=Administrateur : Admin name}';

    protected $description = 'Setup admin user with admin@test.local email';

    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        $this->info("Configuration du compte administrateur...");

        // Créer ou récupérer le service Administration
        $serviceAdmin = Service::firstOrCreate([
            'code' => 'ADMIN'
        ], [
            'nom' => 'Administration',
            'description' => 'Service administration système',
            'actif' => true,
            'budget_annuel_alloue' => 0,
            'responsable_email' => $email
        ]);

        // Créer ou mettre à jour l'utilisateur admin
        $admin = User::updateOrCreate([
            'email' => $email
        ], [
            'name' => $name,
            'password' => Hash::make($password),
            'service_id' => $serviceAdmin->id,
            'is_service_responsable' => true,
            'email_verified_at' => now()
        ]);

        // S'assurer que l'admin a le rôle administrateur
        if (!$admin->hasRole('administrateur')) {
            $admin->assignRole('administrateur');
            $this->info("Rôle administrateur assigné");
        }

        $this->info("✅ Compte administrateur configuré avec succès:");
        $this->line("📧 Email: {$admin->email}");
        $this->line("👤 Nom: {$admin->name}");
        $this->line("🏢 Service: {$serviceAdmin->nom} ({$serviceAdmin->code})");
        $this->line("🔑 Rôles: " . $admin->roles->pluck('name')->implode(', '));
        $this->line("🌐 Accès admin: " . ($admin->canAccessPanel(new \Filament\Panel('admin')) ? 'Autorisé' : 'Refusé'));
        
        $this->warn("⚠️  Mot de passe par défaut: {$password}");
        $this->warn("⚠️  Changez le mot de passe après la première connexion!");

        return 0;
    }
}
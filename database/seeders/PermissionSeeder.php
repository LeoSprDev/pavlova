<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Réinitialise le cache des permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions de base
        $permissions = [
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            
            'view_any_service',
            'view_service',
            'create_service',
            'update_service',
            'delete_service',
            
            'view_any_budget_ligne',
            'view_budget_ligne',
            'create_budget_ligne',
            'update_budget_ligne',
            'delete_budget_ligne',
            'validate_budget',
            
            'view_any_demande_devis',
            'view_demande_devis',
            'create_demande_devis',
            'update_demande_devis',
            'delete_demande_devis',
            'approve_demande_service',
            'approve_demande_budget',
            'approve_demande_achat',
            
            'view_any_commande',
            'view_commande',
            'create_commande',
            'update_commande',
            'delete_commande',
            
            'view_any_livraison',
            'view_livraison',
            'update_livraison',
            'validate_reception',
            
            'view_any_role',
            'view_role',
            'create_role',
            'update_role',
            'delete_role',
            'assign_roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assigner des permissions aux rôles existants
        $adminRole = Role::findByName('administrateur');
        if ($adminRole) {
            $adminRole->givePermissionTo(Permission::all());
        }

        $budgetRole = Role::findByName('responsable-budget');
        if ($budgetRole) {
            $budgetRole->givePermissionTo([
                'view_any_budget_ligne', 'view_budget_ligne', 'create_budget_ligne', 
                'update_budget_ligne', 'validate_budget',
                'view_any_demande_devis', 'view_demande_devis', 'approve_demande_budget',
                'view_any_service', 'view_service',
                'view_any_user', 'view_user',
            ]);
        }

        $serviceRole = Role::findByName('responsable-service');
        if ($serviceRole) {
            $serviceRole->givePermissionTo([
                'view_any_budget_ligne', 'view_budget_ligne',
                'view_any_demande_devis', 'view_demande_devis', 'approve_demande_service',
                'view_any_commande', 'view_commande',
                'view_any_livraison', 'view_livraison', 'validate_reception',
                'view_any_user', 'view_user', 'update_user',
            ]);
        }

        $achatRole = Role::findByName('service-achat');
        if ($achatRole) {
            $achatRole->givePermissionTo([
                'view_any_demande_devis', 'view_demande_devis', 'approve_demande_achat',
                'view_any_commande', 'view_commande', 'create_commande', 'update_commande',
                'view_any_livraison', 'view_livraison',
                'view_any_budget_ligne', 'view_budget_ligne',
            ]);
        }

        $agentRole = Role::findByName('agent-service');
        if ($agentRole) {
            $agentRole->givePermissionTo([
                'view_any_demande_devis', 'view_demande_devis',
                'view_any_livraison', 'view_livraison', 'update_livraison',
            ]);
        }

        $demandeurRole = Role::findByName('service-demandeur');
        if ($demandeurRole) {
            $demandeurRole->givePermissionTo([
                'view_any_demande_devis', 'view_demande_devis', 'create_demande_devis', 'update_demande_devis',
                'view_any_budget_ligne', 'view_budget_ligne',
            ]);
        }
    }
}
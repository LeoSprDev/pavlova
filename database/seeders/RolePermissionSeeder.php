<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Gate;
use App\Models\BudgetLigne;
use App\Models\DemandeDevis;
use App\Models\Commande;
use App\Models\Livraison;
use App\Policies\BudgetLignePolicy;
use App\Policies\DemandeDevisPolicy;
use App\Policies\CommandePolicy;
use App\Policies\LivraisonPolicy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // For resetting auto-increment

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Exception $e) {
            // Ignore cache errors in testing
        }

        // Truncate tables to ensure clean slate, especially for IDs if re-running
        // Be careful with truncate in production if there's existing data not managed by seeders
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        DB::table('permissions')->delete();
        DB::table('roles')->delete();
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('model_has_permissions')->delete();
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }


        // PERMISSIONS GRANULAIRES BUDGET
        Permission::create(['name' => 'view_any_budget_ligne', 'guard_name' => 'web']);
        Permission::create(['name' => 'view_budget_ligne', 'guard_name' => 'web']);
        Permission::create(['name' => 'create_budget_ligne', 'guard_name' => 'web']);
        Permission::create(['name' => 'update_budget_ligne', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete_budget_ligne', 'guard_name' => 'web']);
        Permission::create(['name' => 'validate_budget_ligne', 'guard_name' => 'web']); // For RB to validate/approve a budget line itself
        Permission::create(['name' => 'reallocate_budget', 'guard_name' => 'web']);

        // PERMISSIONS DEMANDES DEVIS
        Permission::create(['name' => 'view_any_demande_devis', 'guard_name' => 'web']);
        Permission::create(['name' => 'view_demande_devis', 'guard_name' => 'web']);
        Permission::create(['name' => 'create_demande_devis', 'guard_name' => 'web']);
        Permission::create(['name' => 'update_demande_devis', 'guard_name' => 'web']); // e.g. before submission or if returned for info
        Permission::create(['name' => 'delete_demande_devis', 'guard_name' => 'web']);
        Permission::create(['name' => 'approve_budget_demande', 'guard_name' => 'web']); // Step 1 approval
        Permission::create(['name' => 'approve_achat_demande', 'guard_name' => 'web']);  // Step 2 approval
        Permission::create(['name' => 'approve_reception_demande', 'guard_name' => 'web']); // Step 3 approval
        Permission::create(['name' => 'reject_demande', 'guard_name' => 'web']); // General rejection permission for any step
        Permission::create(['name' => 'bulk_validate_demandes', 'guard_name' => 'web']); // For bulk actions

        // PERMISSIONS COMMANDES ET LIVRAISONS
        Permission::create(['name' => 'view_any_commande', 'guard_name' => 'web']);
        Permission::create(['name' => 'view_commande', 'guard_name' => 'web']); // Added for individual view
        Permission::create(['name' => 'create_commande', 'guard_name' => 'web']); // Typically by service achat
        Permission::create(['name' => 'update_commande', 'guard_name' => 'web']);
        Permission::create(['name' => 'cancel_commande', 'guard_name' => 'web']);
        Permission::create(['name' => 'view_any_livraison', 'guard_name' => 'web']);
        Permission::create(['name' => 'view_livraison', 'guard_name' => 'web']); // Added for individual view
        Permission::create(['name' => 'create_livraison', 'guard_name' => 'web']); // Added for recording a delivery
        Permission::create(['name' => 'validate_livraison', 'guard_name' => 'web']); // Confirming delivery details
        Permission::create(['name' => 'upload_bon_livraison', 'guard_name' => 'web']);

        // PERMISSIONS EXPORTS ET RAPPORTS
        Permission::create(['name' => 'export_budget_service', 'guard_name' => 'web']);
        Permission::create(['name' => 'export_budget_global', 'guard_name' => 'web']);
        Permission::create(['name' => 'view_analytics_service', 'guard_name' => 'web']); // For service-specific dashboard/stats
        Permission::create(['name' => 'view_analytics_global', 'guard_name' => 'web']); // For global dashboard/stats
        Permission::create(['name' => 'generate_reports', 'guard_name' => 'web']); // General permission for other reports

        // PERMISSIONS ADMINISTRATION
        Permission::create(['name' => 'manage_users', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage_services', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage_roles', 'guard_name' => 'web']);
        Permission::create(['name' => 'view_system_logs', 'guard_name' => 'web']);
        Permission::create(['name' => 'configure_workflow', 'guard_name' => 'web']);


        // RÔLE SERVICE DEMANDEUR (CLOISONNÉ)
        $service_demandeur = Role::create(['name' => 'service-demandeur', 'guard_name' => 'web']);
        $service_demandeur->givePermissionTo([
            'view_budget_ligne',        // Policy will enforce own service
            'create_demande_devis',
            'view_demande_devis',       // Policy will enforce own demands
            'update_demande_devis',     // Policy will enforce conditions (e.g. before validation)
            'approve_reception_demande', // NIVEAU 3 WORKFLOW - Policy will check if user is the original requester or from the service
            'validate_livraison',       // For confirming their own deliveries
            'upload_bon_livraison',     // For their deliveries
            'export_budget_service',    // Policy/Resource will filter for own service
            'view_analytics_service',   // For their service dashboard
            'view_any_budget_ligne',    // To list their own budget lines
            'view_any_demande_devis',   // To list their own demands
            'view_any_livraison',       // To list their own deliveries
            'view_livraison',           // To view their own delivery
            'create_livraison',         // To record a delivery for their order
        ]);

        // RÔLE RESPONSABLE BUDGET (GLOBAL)
        $responsable_budget = Role::create(['name' => 'responsable-budget', 'guard_name' => 'web']);
        $responsable_budget->givePermissionTo([
            'view_any_budget_ligne',    // TOUS SERVICES
            'view_budget_ligne',
            'create_budget_ligne',      // Can create budget lines for services
            'update_budget_ligne',      // Can update any budget line
            'delete_budget_ligne',      // Can delete budget lines (with conditions)
            'validate_budget_ligne',    // Validate the budget line itself (e.g. initial approval of the line)
            'reallocate_budget',
            'view_any_demande_devis',   // TOUTES DEMANDES
            'view_demande_devis',
            'approve_budget_demande',   // NIVEAU 1 WORKFLOW
            'reject_demande',           // Can reject at their step
            'bulk_validate_demandes',   // Bulk approval/rejection at their step
            'export_budget_global',
            'view_analytics_global',
            'generate_reports',
            'manage_services',          // Can manage service entities
            'view_any_commande',        // View all orders for context
            'view_commande',
            'view_any_livraison',       // View all deliveries for context
            'view_livraison',
        ]);

        // RÔLE SERVICE ACHAT (SPÉCIALISÉ)
        $service_achat = Role::create(['name' => 'service-achat', 'guard_name' => 'web']);
        $service_achat->givePermissionTo([
            'view_any_demande_devis',   // Policy/Resource will filter for demands at their approval stage
            'view_demande_devis',
            'approve_achat_demande',    // NIVEAU 2 WORKFLOW
            'reject_demande',           // Can reject at their step
            'view_any_commande',
            'view_commande',
            'create_commande',          // Primary role for creating POs
            'update_commande',
            'cancel_commande',
            'view_any_livraison',       // For tracking deliveries related to their orders
            'view_livraison',
            'export_budget_global',     // For analysis and purchasing optimization
            'view_analytics_global',
            'generate_reports',
        ]);

        // RÔLE ADMINISTRATEUR SYSTÈME
        $admin = Role::create(['name' => 'administrateur', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all()); // Admin gets all permissions

        // Enregistrement Policies (bien que Gate::policy soit souvent dans AuthServiceProvider,
        // le prompt le met ici, donc on le garde pour suivre)
        // Assurez-vous que ces classes Policy existent dans App\Policies
        if (class_exists(BudgetLignePolicy::class)) Gate::policy(BudgetLigne::class, BudgetLignePolicy::class);
        if (class_exists(DemandeDevisPolicy::class)) Gate::policy(DemandeDevis::class, DemandeDevisPolicy::class);
        if (class_exists(CommandePolicy::class)) Gate::policy(Commande::class, CommandePolicy::class); // Assuming CommandePolicy will be created
        if (class_exists(LivraisonPolicy::class)) Gate::policy(Livraison::class, LivraisonPolicy::class); // Assuming LivraisonPolicy will be created

        Log::info('Rôles et permissions configurés avec succès.', [
            'roles_count' => Role::count(),
            'permissions_count' => Permission::count()
        ]);
    }
}

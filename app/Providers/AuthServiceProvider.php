<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate; // Make sure Gate is imported
use App\Models\BudgetLigne;
use App\Policies\BudgetLignePolicy;
use App\Models\DemandeDevis;
use App\Policies\DemandeDevisPolicy;
use App\Models\Commande;
use App\Policies\CommandePolicy;
use App\Models\Livraison;
use App\Policies\LivraisonPolicy;
use App\Models\Service; // Assuming a ServicePolicy might be needed
use App\Policies\ServicePolicy; // Assuming a ServicePolicy might be needed
use App\Models\User; // Assuming a UserPolicy might be needed
use App\Policies\UserPolicy; // Assuming a UserPolicy might be needed


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        BudgetLigne::class => BudgetLignePolicy::class,
        DemandeDevis::class => DemandeDevisPolicy::class,
        Commande::class => CommandePolicy::class,
        Livraison::class => LivraisonPolicy::class,
        Service::class => ServicePolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Implicitly grant "Super Admin" (or your admin role name) all permissions
        // This works by checking if the user has a role named 'administrateur'.
        // You can also define a specific Gate for this if you prefer.
        Gate::before(function ($user, $ability) {
            return $user->hasRole('administrateur') ? true : null;
        });
    }
}

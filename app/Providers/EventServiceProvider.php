<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\BudgetSeuilDepasse;
use App\Listeners\BudgetSeuilDepasseListener;
use App\Models\{DemandeDevis, BudgetLigne, Commande};
use App\Observers\{DemandeDevisObserver, BudgetLigneObserver, CommandeObserver};

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        BudgetSeuilDepasse::class => [
            BudgetSeuilDepasseListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        DemandeDevis::observe(DemandeDevisObserver::class);
        BudgetLigne::observe(BudgetLigneObserver::class);
        Commande::observe(CommandeObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\VerificationBudgetsQuotidienne;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->job(new VerificationBudgetsQuotidienne)->dailyAt('02:00'); // Run daily at 2 AM

        // AJOUTER:
        $schedule->job(new \App\Jobs\VerificationBudgetsQuotidienne)->dailyAt('08:00');

        // Vérifier commandes en retard et relancer
        $schedule->call(function () {
            \App\Models\Commande::where('date_livraison_prevue', '<', now())
                ->whereDoesntHave('livraison')
                ->chunk(50, function ($commandes) {
                    foreach ($commandes as $commande) {
                        if ($commande->nb_relances < 3) {
                            \App\Jobs\RelanceFournisseurJob::dispatch($commande, $commande->nb_relances + 1);
                        }
                    }
                });
        })->dailyAt('10:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\VerificationBudgetsQuotidienne;
use App\Jobs\RelanceLivraisonEnRetard;
use App\Jobs\SendWorkflowReminders;
use App\Jobs\SendDigestNotifications;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\WorkflowEscaladeCommand::class,
        \App\Console\Commands\WorkflowMaintenanceCommand::class,
        \App\Console\Commands\SendDeadlineReminders::class,
        \App\Console\Commands\SendWeeklyReports::class,
        \App\Console\Commands\CleanOldNotifications::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->job(new VerificationBudgetsQuotidienne)->dailyAt('02:00'); // Run daily at 2 AM
        $schedule->job(new \App\Jobs\VerificationBudgetsQuotidienne)->dailyAt('08:00');

        // 🕘 Relances livraisons en retard - tous les jours à 9h00
        $schedule->job(RelanceLivraisonEnRetard::class)
            ->dailyAt('09:00')
            ->name('relance_livraisons_retard')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler.log'));
        $schedule->command('workflow:escalade')->dailyAt('11:00');

        $schedule->job(SendWorkflowReminders::class)
            ->dailyAt('08:30')
            ->withoutOverlapping();

        $schedule->job(SendDigestNotifications::class)
            ->dailyAt('17:00')
            ->withoutOverlapping();

        $schedule->command('send:deadline-reminders')->dailyAt('09:15');
        $schedule->command('send:weekly-reports')->mondays()->at('08:00');
        $schedule->command('notifications:cleanup')->dailyAt('01:00');

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

        // 📊 Log quotidien pour traçabilité
        $schedule->call(function () {
            Log::info('⏰ Scheduler actif', ['date' => now()->toDateTimeString()]);
        })->daily();
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

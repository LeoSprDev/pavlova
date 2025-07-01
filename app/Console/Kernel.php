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

        // Example for RelanceFournisseurJob (might need a command to dispatch it or a more complex scheduler)
        // This job is typically dispatched on demand or after certain events,
        // but a general check for overdue orders could be scheduled.
        // $schedule->command('app:dispatch-relances-fournisseurs')->dailyAt('03:00');
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

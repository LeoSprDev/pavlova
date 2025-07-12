<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendWeeklyReports extends Command
{
    protected $signature = 'send:weekly-reports';
    protected $description = 'Envoie le rapport hebdomadaire de synthèse';

    public function handle()
    {
        \App\Jobs\SendWeeklyReports::dispatch();
        $this->info('Rapports hebdomadaires envoyés');
        return self::SUCCESS;
    }
}

<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    protected $signature = 'send:deadline-reminders';
    protected $description = 'Envoie les relances de workflow en attente';

    public function handle()
    {
        \App\Jobs\SendDeadlineReminders::dispatch();
        $this->info('Relances workflow programmées');
        return self::SUCCESS;
    }
}

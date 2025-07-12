<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\EscaladeWorkflowJob;

class WorkflowMaintenanceCommand extends Command
{
    protected $signature = 'workflow:maintenance';

    protected $description = 'Tâches de maintenance périodiques du workflow';

    public function handle(): int
    {
        EscaladeWorkflowJob::dispatch();
        $this->info('Maintenance : escalade lancée');

        return Command::SUCCESS;
    }
}

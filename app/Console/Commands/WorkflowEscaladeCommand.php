<?php
namespace App\Console\Commands;

use App\Jobs\EscaladeWorkflowJob;
use Illuminate\Console\Command;

class WorkflowEscaladeCommand extends Command
{
    protected $signature = 'workflow:escalade {--dry-run}';

    protected $description = 'Lance l\'escalade automatique des workflows en attente';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->info('Dry run - EscaladeWorkflowJob would be dispatched');
            return Command::SUCCESS;
        }

        EscaladeWorkflowJob::dispatch();
        $this->info('EscaladeWorkflowJob dispatché');

        return Command::SUCCESS;
    }
}

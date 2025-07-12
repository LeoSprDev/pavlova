<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanOldNotifications extends Command
{
    protected $signature = 'notifications:cleanup';
    protected $description = 'Supprime les anciennes notifications';

    public function handle()
    {
        \DB::table('notifications')->where('created_at', '<', now()->subMonths(3))->delete();
        $this->info('Notifications obsolètes supprimées');
        return self::SUCCESS;
    }
}

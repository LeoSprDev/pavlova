<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\BudgetWarningNotificationMail;
use App\Models\{BudgetWarning, User};
use Illuminate\Support\Facades\Mail;

class SendBudgetWarningAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public BudgetWarning $warning
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)
            ->queue(new BudgetWarningNotificationMail($this->warning, $this->user));
    }
}

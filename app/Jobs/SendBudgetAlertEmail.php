<?php

namespace App\Jobs;

use App\Mail\BudgetAlertEmail;
use App\Models\BudgetLigne;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBudgetAlertEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public BudgetLigne $budgetLigne,
        public int $seuil
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)
            ->queue(new BudgetAlertEmail($this->budgetLigne, $this->seuil));
    }
}

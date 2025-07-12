<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\WeeklyReportMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $users = User::role(['responsable-direction', 'responsable-budget'])->get();

        foreach ($users as $user) {
            Mail::to($user->email)->queue(new WeeklyReportMail($user));
        }
    }
}

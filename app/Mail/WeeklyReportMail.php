<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class WeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function build()
    {
        return $this->subject('Rapport hebdomadaire')
            ->view('emails.workflow.weekly-report')
            ->with([
                'userName' => $this->user->name,
            ]);
    }
}

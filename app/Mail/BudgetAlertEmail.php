<?php

namespace App\Mail;

use App\Models\BudgetLigne;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BudgetAlertEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BudgetLigne $budgetLigne,
        public int $seuil
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚨 Alerte budget {$this->seuil}%",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.notifications.budget-alert',
            with: [
                'budgetLigne' => $this->budgetLigne,
                'seuil' => $this->seuil,
            ],
        );
    }
}

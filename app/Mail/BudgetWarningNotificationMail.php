<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\{BudgetWarning, User};

class BudgetWarningNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BudgetWarning $warning,
        public User $user
    ) {}

    public function build()
    {
        $budgetLigne = $this->warning->budgetLigne;

        return $this->subject('⚠️ Dépassement Budget - ' . $budgetLigne->intitule)
            ->view('emails.workflow.budget-warning')
            ->with([
                'userName' => $this->user->name,
                'budgetIntitule' => $budgetLigne->intitule,
                'montantDepassement' => $this->warning->montant_depassement,
                'budgetTotal' => $budgetLigne->montant_ht_prevu,
                'serviceConcerne' => $budgetLigne->service->nom,
                'actionUrl' => url("/admin/budget-lignes/{$budgetLigne->id}"),
                'warningMessage' => $this->warning->message,
            ]);
    }
}

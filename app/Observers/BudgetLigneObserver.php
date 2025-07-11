<?php

namespace App\Observers;

use App\Jobs\SendBudgetAlertEmail;
use App\Models\BudgetLigne;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class BudgetLigneObserver
{
    public function updated(BudgetLigne $ligne): void
    {
        if ($ligne->wasChanged(['montant_depense_reel', 'montant_engage'])) {
            $taux = round($ligne->getTauxUtilisation(), 1);
            $this->checkThresholds($ligne, $taux);
            if ($taux > 95) {
                $this->escalateCritical($ligne, $taux);
            }
        }
    }

    private function checkThresholds(BudgetLigne $ligne, float $taux): void
    {
        foreach ([80, 90, 95, 100] as $seuil) {
            if ($taux >= $seuil && ! Cache::has($this->cacheKey($ligne->id, $seuil))) {
                $this->sendBudgetAlert($ligne, $seuil, $taux);
                Cache::put($this->cacheKey($ligne->id, $seuil), true, now()->addHours(12));
            }
        }
    }

    private function sendBudgetAlert(BudgetLigne $ligne, int $seuil, float $taux): void
    {
        $recipients = User::role('responsable-budget')->get();

        foreach ($recipients as $user) {
            Notification::make()
                ->title("🚨 Budget {$seuil}% atteint")
                ->body("La ligne '{$ligne->intitule}' est utilisée à {$taux}%")
                ->color('danger')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Voir budget')
                        ->url("/admin/budget-lignes/{$ligne->id}")
                        ->button(),
                ])
                ->sendToDatabase($user);

            if ($seuil >= 90) {
                SendBudgetAlertEmail::dispatch($user, $ligne, $seuil);
            }
        }
    }

    private function escalateCritical(BudgetLigne $ligne, float $taux): void
    {
        if (Cache::has($this->cacheKey($ligne->id, 'escalade'))) {
            return;
        }
        $direction = User::role('responsable-direction')->get();
        foreach ($direction as $user) {
            Notification::make()
                ->title('❗ Budget critique')
                ->body("La ligne '{$ligne->intitule}' dépasse {$taux}%")
                ->danger()
                ->sendToDatabase($user);
        }
        Cache::put($this->cacheKey($ligne->id, 'escalade'), true, now()->addHours(12));
    }

    private function cacheKey(int $id, string|int $suffix): string
    {
        return "budget-alert-{$id}-{$suffix}";
    }
}

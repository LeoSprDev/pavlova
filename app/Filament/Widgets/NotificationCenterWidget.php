<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class NotificationCenterWidget extends Widget
{
    protected static string $view = 'filament.widgets.notification-center-widget';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    public function getNotifications(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [
                'unread' => collect(),
                'budget_warnings' => collect(),
                'pending_approvals' => collect(),
            ];
        }

        return [
            'unread' => $user->unreadNotifications()->limit(5)->get(),
            'budget_warnings' => $this->getBudgetWarnings($user),
            'pending_approvals' => $this->getPendingApprovals($user),
        ];
    }

    private function getBudgetWarnings($user)
    {
        if ($user->hasRole('responsable-budget')) {
            return \App\Models\BudgetWarning::with('budgetLigne')
                ->latest()->limit(3)->get();
        }

        return collect();
    }

    private function getPendingApprovals($user)
    {
        $status = $this->getStatusForRole($user);

        return $status
            ? \App\Models\DemandeDevis::where('statut', $status)
                ->limit(5)->get()
            : collect();
    }

    private function getStatusForRole($user): ?string
    {
        return match (true) {
            $user->hasRole('manager-service') => 'pending_manager',
            $user->hasRole('responsable-direction') => 'pending_direction',
            $user->hasRole('service-achat') => 'pending_achat',
            default => null,
        };
    }

    public function getNotificationBadgeCount(): int
    {
        $user = Auth::user();

        return $user ? $user->unreadNotifications()->count() : 0;
    }

    public function markAsRead(string $notificationId): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $user->notifications()->where('id', $notificationId)->update(['read_at' => now()]);
        $this->dispatch('notification-marked-read');
    }
}

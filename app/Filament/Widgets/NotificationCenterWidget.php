<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class NotificationCenterWidget extends Widget
{
    protected static string $view = 'filament.widgets.notification-center-widget';
    protected int | string | array $columnSpan = 1;

    public function getNotifications()
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return $user->notifications()
            ->whereNull('read_at')
            ->latest()
            ->limit(5)
            ->get();
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

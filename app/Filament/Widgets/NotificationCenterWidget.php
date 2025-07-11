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
        return Auth::user()
            ->notifications()
            ->whereNull('read_at')
            ->latest()
            ->limit(5)
            ->get();
    }

    public function getNotificationBadgeCount(): int
    {
        return Auth::user()->unreadNotifications()->count();
    }

    public function markAsRead(string $notificationId): void
    {
        Auth::user()->notifications()->where('id', $notificationId)->update(['read_at' => now()]);
        $this->dispatch('notification-marked-read');
    }
}

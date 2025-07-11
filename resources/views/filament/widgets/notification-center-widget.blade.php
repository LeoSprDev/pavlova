<x-filament-widgets::widget>
    <div class="space-y-2">
        @foreach($this->getNotifications() as $notification)
            <div class="bg-white dark:bg-gray-700 rounded p-2 shadow flex items-start justify-between">
                <div class="text-sm">
                    <p class="font-semibold">{{ $notification->data['titre'] ?? 'Notification' }}</p>
                    <p class="text-gray-500">{{ $notification->data['message'] ?? '' }}</p>
                </div>
                <button wire:click="markAsRead('{{ $notification->id }}')" class="text-xs text-blue-600">ok</button>
            </div>
        @endforeach
        @if($this->getNotifications()->isEmpty())
            <p class="text-sm text-gray-500 text-center">Aucune notification</p>
        @endif
    </div>
</x-filament-widgets::widget>

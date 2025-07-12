<x-filament-widgets::widget>
    @php($data = $this->getNotifications())
    <div class="space-y-6">
        <div>
            <h3 class="font-semibold mb-2">Notifications</h3>
            <div class="space-y-2">
                @forelse($data['unread'] as $notification)
                    <div class="bg-white dark:bg-gray-700 rounded p-2 shadow flex items-start justify-between">
                        <div class="text-sm">
                            <p class="font-semibold">{{ $notification->data['titre'] ?? 'Notification' }}</p>
                            <p class="text-gray-500">{{ $notification->data['message'] ?? '' }}</p>
                        </div>
                        <button wire:click="markAsRead('{{ $notification->id }}')" class="text-xs text-blue-600">ok</button>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center">Aucune notification</p>
                @endforelse
            </div>
        </div>

        @if($data['budget_warnings']->isNotEmpty())
            <div>
                <h3 class="font-semibold mb-2 text-red-600">Alertes Budget</h3>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach($data['budget_warnings'] as $warning)
                        <li>{{ $warning->message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($data['pending_approvals']->isNotEmpty())
            <div>
                <h3 class="font-semibold mb-2 text-yellow-600">Demandes à approuver</h3>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach($data['pending_approvals'] as $demande)
                        <li>
                            <a href="/admin/demande-devis/{{ $demande->id }}" class="underline">
                                {{ $demande->denomination }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>

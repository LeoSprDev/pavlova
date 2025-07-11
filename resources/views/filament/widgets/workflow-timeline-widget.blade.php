<x-filament-widgets::widget class="fi-workflow-timeline-widget">
    <x-filament::section>
        <x-slot name="heading">
            Timeline Workflow
        </x-slot>

        <div class="space-y-4 max-h-96 overflow-y-auto">
            @forelse($this->getDemandes() as $demande)
                <div class="flex items-start space-x-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                    <div class="flex-shrink-0">
                        @switch($demande->statut)
                            @case('pending_manager')
                                <div class="w-4 h-4 bg-yellow-400 rounded-full animate-pulse shadow-lg">
                                    <div class="absolute inset-0 bg-yellow-400 rounded-full animate-ping opacity-75"></div>
                                </div>
                                @break
                            @case('pending_direction')
                                <div class="w-4 h-4 bg-blue-400 rounded-full animate-pulse shadow-lg">
                                    <div class="absolute inset-0 bg-blue-400 rounded-full animate-ping opacity-75"></div>
                                </div>
                                @break
                            @case('pending_achat')
                                <div class="w-4 h-4 bg-purple-400 rounded-full animate-pulse shadow-lg">
                                    <div class="absolute inset-0 bg-purple-400 rounded-full animate-ping opacity-75"></div>
                                </div>
                                @break
                            @case('ordered')
                                <div class="w-4 h-4 bg-orange-400 rounded-full animate-pulse shadow-lg">
                                    <div class="absolute inset-0 bg-orange-400 rounded-full animate-ping opacity-75"></div>
                                </div>
                                @break
                            @case('pending_delivery')
                                <div class="w-4 h-4 bg-indigo-400 rounded-full animate-pulse shadow-lg"></div>
                                @break
                            @default
                                <div class="w-4 h-4 bg-green-400 rounded-full shadow-lg"></div>
                        @endswitch
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $demande->denomination }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $demande->serviceDemandeur->nom }} • {{ number_format($demande->prix_total_ttc, 2) }}€
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $demande->created_at->diffForHumans() }}
                        </p>
                    </div>

                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-all duration-200
                            @switch($demande->statut)
                                @case('pending_manager')
                                    bg-yellow-100 text-yellow-800 animate-pulse border border-yellow-300
                                    @break
                                @case('pending_direction')
                                    bg-blue-100 text-blue-800 animate-pulse border border-blue-300
                                    @break
                                @case('pending_achat')
                                    bg-purple-100 text-purple-800 animate-pulse border border-purple-300
                                    @break
                                @case('ordered')
                                    bg-orange-100 text-orange-800 animate-pulse border border-orange-300
                                    @break
                                @case('pending_delivery')
                                    bg-indigo-100 text-indigo-800 border border-indigo-300
                                    @break
                                @default
                                    bg-green-100 text-green-800 border border-green-300
                            @endswitch
                        ">
                            @switch($demande->statut)
                                @case('pending_manager')
                                    ⏳ Manager
                                    @break
                                @case('pending_direction')
                                    🎯 Direction
                                    @break
                                @case('pending_achat')
                                    🛒 Achat
                                    @break
                                @case('ordered')
                                    📦 Commandé
                                    @break
                                @case('pending_delivery')
                                    🚚 Livraison
                                    @break
                                @default
                                    ✅ Terminé
                            @endswitch
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <div class="text-6xl mb-4">🎯</div>
                    <p class="text-lg font-medium">Aucune demande en cours</p>
                    <p class="text-sm">Toutes les demandes sont traitées !</p>
                </div>
            @endforelse
        </div>

        @if($this->getDemandes()->count() > 0)
            <div class="mt-4 text-center">
                <x-filament::button
                    tag="a"
                    href="/admin/demande-devis"
                    size="sm"
                    color="gray"
                >
                    Voir toutes les demandes
                </x-filament::button>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

<script>
    setInterval(() => {
        if (typeof Livewire !== 'undefined') {
            Livewire.emit('refreshWidget');
        }
    }, 30000);
</script>

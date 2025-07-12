<x-filament-widgets::widget class="fi-workflow-timeline-widget">
    <x-filament::section>
        <x-slot name="heading">
            🚀 Timeline Workflow 5 Niveaux - {{ $this->getDemandes()->count() }} demandes
        </x-slot>

        <div class="space-y-4 max-h-96 overflow-y-auto">
            @forelse($this->getDemandes() as $demande)
                <div class="workflow-item flex items-start space-x-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200 cursor-pointer" onclick="window.open('/admin/demande-devis/{{ $demande->id }}', '_blank')">
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-200 rounded-b-lg overflow-hidden">
                        <div class="h-full bg-blue-500 transition-all duration-500" style="width: {{ $this->getWorkflowProgress($demande->statut) }}%"></div>
                    </div>
                    <div class="flex-shrink-0 relative">
                        @switch($demande->statut)
                            @case('pending_manager')
                                <div class="w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
                                @break
                            @case('approved_manager')
                            @case('pending_direction')
                                <div class="w-4 h-4 bg-blue-400 rounded-full animate-pulse"></div>
                                @break
                            @case('approved_direction')
                            @case('pending_achat')
                                <div class="w-4 h-4 bg-purple-400 rounded-full animate-pulse"></div>
                                @break
                            @case('ordered')
                            @case('pending_delivery')
                                <div class="w-4 h-4 bg-orange-400 rounded-full animate-pulse"></div>
                                @break
                            @case('delivered_confirmed')
                                <div class="w-4 h-4 bg-green-400 rounded-full"></div>
                                @break
                            @default
                                <div class="w-4 h-4 bg-gray-400 rounded-full"></div>
                        @endswitch
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $demande->denomination }}
                            </p>
                            <span class="px-2 py-1 text-xs rounded-full
                                @switch($demande->statut)
                                    @case('pending_manager') bg-yellow-100 text-yellow-800 @break
                                    @case('pending_direction') bg-blue-100 text-blue-800 @break
                                    @case('pending_achat') bg-purple-100 text-purple-800 @break
                                    @case('pending_delivery') bg-orange-100 text-orange-800 @break
                                    @case('delivered_confirmed') bg-green-100 text-green-800 @break
                                    @default bg-gray-100 text-gray-800 @break
                                @endswitch">
                                @switch($demande->statut)
                                    @case('pending_manager') 👤 Manager @break
                                    @case('pending_direction') 🏢 Direction @break
                                    @case('pending_achat') 🛒 Achat @break
                                    @case('pending_delivery') 🚚 Livraison @break
                                    @case('delivered_confirmed') ✅ Terminé @break
                                    @default ⏳ En cours @break
                                @endswitch
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ number_format($demande->prix_total_ttc, 2) }} € • {{ $demande->serviceDemandeur->nom }}
                            • {{ $demande->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-gray-500">
                    <div class="text-6xl mb-4">🎯</div>
                    <p class="text-lg font-medium">Aucune demande en cours</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<script>
setInterval(() => {
    if (typeof Livewire !== 'undefined') {
        Livewire.emit('refreshWidget');
    }
}, 60000);
</script>

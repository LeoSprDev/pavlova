<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            📋 Workflow Kanban - Vue Métier
        </x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($this->getKanbanData() as $status => $column)
            <div class="kanban-column bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <!-- Header colonne -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-user class="w-5 h-5 text-{{ $column['color'] }}-500" />
                        <h3 class="font-semibold text-sm">{{ $column['label'] }}</h3>
                    </div>
                    <span class="bg-{{ $column['color'] }}-100 text-{{ $column['color'] }}-800 text-xs px-2 py-1 rounded-full">
                        {{ $column['count'] }}
                    </span>
                </div>
                
                <!-- Cartes demandes -->
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($column['demandes'] as $demande)
                    <div class="kanban-card bg-white dark:bg-gray-700 p-3 rounded border border-gray-200 hover:shadow-md transition-shadow cursor-pointer"
                         onclick="window.open('/admin/demande-devis/{{ $demande->id }}', '_blank')">
                         
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-medium text-sm truncate">
                                {{ Str::limit($demande->denomination, 25) }}
                            </h4>
                            <span class="text-xs text-gray-500">
                                #{{ $demande->id }}
                            </span>
                        </div>
                        
                        <div class="text-xs text-gray-600 space-y-1">
                            <div>💰 {{ number_format($demande->prix_total_ttc, 0) }}€</div>
                            <div>🏢 {{ $demande->serviceDemandeur?->nom }}</div>
                            <div>⏱️ {{ $demande->created_at->diffForHumans() }}</div>
                        </div>
                        
                        @if($column['action_available'] ?? false)
                        <div class="mt-2 pt-2 border-t">
                            <button class="text-xs bg-{{ $column['color'] }}-500 text-white px-2 py-1 rounded hover:bg-{{ $column['color'] }}-600">
                                {{ $status === 'pending_achat' ? 'Valider' : 'Créer Commande' }}
                            </button>
                        </div>
                        @endif
                    </div>
                    @endforeach
                    
                    @if($column['demandes']->isEmpty())
                    <div class="text-center text-gray-400 text-sm py-8">
                        Aucune demande
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Auto-refresh -->
        <script>
            setInterval(() => {
                if (typeof Livewire !== 'undefined') {
                    Livewire.emit('$refresh');
                }
            }, 30000); // Refresh toutes les 30 secondes
        </script>
    </x-filament::section>
</x-filament-widgets::widget>

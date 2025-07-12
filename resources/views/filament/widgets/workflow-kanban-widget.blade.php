<x-filament-widgets::widget class="fi-workflow-kanban-widget">
    <div class="grid grid-cols-5 gap-4 h-96">
        @foreach($this->getKanbanColumns() as $status => $column)
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-{{ $column['color'] }}-600">
                        {{ $column['title'] }}
                    </h3>
                    <span class="bg-{{ $column['color'] }}-100 text-{{ $column['color'] }}-800 px-2 py-1 rounded-full text-xs">
                        {{ $column['demandes']->count() }}
                    </span>
                </div>

                <div class="space-y-3">
                    @foreach($column['demandes'] as $demande)
                        <div class="kanban-card bg-white dark:bg-gray-700 p-3 rounded-lg shadow-sm border-l-4 border-{{ $column['color'] }}-400 hover:shadow-md transition-all duration-200 cursor-pointer transform hover:scale-105" onclick="window.open('/admin/demande-devis/{{ $demande->id }}', '_blank')">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium text-sm truncate">{{ $demande->denomination }}</h4>
                                <span class="text-xs text-gray-500">#{{ $demande->id }}</span>
                            </div>

                            <p class="text-xs text-gray-600 mb-2 truncate">{{ $demande->serviceDemandeur->nom }}</p>

                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-{{ $column['color'] }}-600 text-sm">
                                    {{ number_format($demande->prix_total_ttc, 2) }}€
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $demande->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <div class="mt-2 w-full bg-gray-200 rounded-full h-1">
                                <div class="bg-{{ $column['color'] }}-500 h-1 rounded-full transition-all duration-1000 ease-out" style="width: {{ $this->getProgressPercentage($status) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>

<style>
.kanban-card {
    animation: slideIn 0.3s ease-out;
}
@keyframes slideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.kanban-card:hover {
    animation: pulse 1s infinite;
}
</style>

<script>
setInterval(() => {
    if (typeof Livewire !== 'undefined' && !document.hidden) {
        Livewire.emit('refreshWidget');
    }
}, 30000);

document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        Livewire.emit('refreshWidget');
    }
});
</script>

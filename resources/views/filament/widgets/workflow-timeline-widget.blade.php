<x-filament-widgets::widget class="fi-workflow-timeline-widget">
    <x-filament::section>
        <x-slot name="heading">
            Timeline Workflow
        </x-slot>

        <div class="space-y-4">
            @forelse($this->getDemandes() as $demande)
                <div class="flex items-start space-x-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex-shrink-0">
                        @switch($demande->statut)
                            @case('pending_manager')
                                <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                                @break
                            @case('pending_direction')
                                <div class="w-3 h-3 bg-blue-400 rounded-full"></div>
                                @break
                            @case('pending_achat')
                                <div class="w-3 h-3 bg-purple-400 rounded-full"></div>
                                @break
                            @default
                                <div class="w-3 h-3 bg-green-400 rounded-full"></div>
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
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @switch($demande->statut)
                                @case('pending_manager')
                                    bg-yellow-100 text-yellow-800
                                    @break
                                @case('pending_direction')
                                    bg-blue-100 text-blue-800
                                    @break
                                @case('pending_achat')
                                    bg-purple-100 text-purple-800
                                    @break
                                @default
                                    bg-green-100 text-green-800
                            @endswitch
                        ">
                            @switch($demande->statut)
                                @case('pending_manager')
                                    Manager
                                    @break
                                @case('pending_direction')
                                    Direction
                                    @break
                                @case('pending_achat')
                                    Achat
                                    @break
                                @default
                                    Terminé
                            @endswitch
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Aucune demande en cours
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

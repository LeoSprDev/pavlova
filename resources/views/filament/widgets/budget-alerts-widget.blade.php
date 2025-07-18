<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🚨 Alertes Budgétaires - Intelligence Métier
        </x-slot>
        
        @php
            $alertsData = $this->getAlertsData();
        @endphp
        
        <!-- Statistiques globales -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="text-center p-4 bg-green-50 dark:bg-green-900 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ $alertsData['stats']['services_ok'] }}</div>
                <div class="text-sm text-green-800">Services OK</div>
            </div>
            <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600">{{ $alertsData['stats']['services_attention'] }}</div>
                <div class="text-sm text-yellow-800">En attention</div>
            </div>
            <div class="text-center p-4 bg-red-50 dark:bg-red-900 rounded-lg">
                <div class="text-2xl font-bold text-red-600">{{ $alertsData['stats']['services_depassement'] }}</div>
                <div class="text-sm text-red-800">Dépassements</div>
            </div>
        </div>
        
        <!-- Alertes critiques -->
        @if(count($alertsData['critiques']) > 0)
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-red-600 mb-3">🔴 Alertes Critiques</h3>
            <div class="space-y-3">
                @foreach($alertsData['critiques'] as $alert)
                <div class="p-4 bg-red-50 dark:bg-red-900 border-l-4 border-red-500 rounded">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-semibold text-red-800">{{ $alert['service'] }}</div>
                            <div class="text-red-700 text-sm">{{ $alert['message'] }}</div>
                            <div class="text-red-600 text-xs mt-1">{{ $alert['action'] }}</div>
                        </div>
                        <a href="{{ $alert['url'] }}" class="text-red-600 hover:text-red-800 text-sm">
                            Voir détails →
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        
        <!-- Alertes warning -->
        @if(count($alertsData['avertissements']) > 0)
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-yellow-600 mb-3">🟠 Alertes Attention</h3>
            <div class="space-y-3">
                @foreach($alertsData['avertissements'] as $alert)
                <div class="p-4 bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-500 rounded">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-semibold text-yellow-800">{{ $alert['service'] }} - {{ $alert['ligne'] }}</div>
                            <div class="text-yellow-700 text-sm">{{ $alert['message'] }}</div>
                            <div class="text-yellow-600 text-xs mt-1">{{ $alert['action'] }}</div>
                        </div>
                        <div class="text-yellow-600 font-bold">
                            {{ $alert['taux'] }}%
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        
        <!-- Informations -->
        @if(count($alertsData['info']) > 0)
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-blue-600 mb-3">🔵 Informations</h3>
            <div class="space-y-3">
                @foreach($alertsData['info'] as $alert)
                <div class="p-4 bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-500 rounded">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-semibold text-blue-800">{{ $alert['service'] }} - {{ $alert['demande'] }}</div>
                            <div class="text-blue-700 text-sm">{{ $alert['message'] }}</div>
                            <div class="text-blue-600 text-xs mt-1">{{ $alert['action'] }}</div>
                        </div>
                        <div class="text-blue-600 text-sm">
                            {{ $alert['jours'] }}j
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        
        <!-- Aucune alerte -->
        @if(count($alertsData['critiques']) === 0 && count($alertsData['avertissements']) === 0 && count($alertsData['info']) === 0)
        <div class="text-center py-8">
            <div class="text-green-600 text-lg mb-2">✅ Tout va bien !</div>
            <div class="text-gray-600">Aucune alerte budgétaire à signaler</div>
        </div>
        @endif
        
        <!-- Auto-refresh -->
        <script>
            setInterval(() => {
                if (typeof Livewire !== 'undefined') {
                    Livewire.emit('$refresh');
                }
            }, 60000); // Refresh toutes les 60 secondes
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
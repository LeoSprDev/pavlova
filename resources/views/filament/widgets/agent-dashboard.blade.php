<div class="space-y-6">
    {{-- Actions Prioritaires --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Mes Actions Prioritaires</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="/admin/demandes-devis/create" class="bg-green-50 border border-green-200 rounded-lg p-4 hover:bg-green-100 transition-colors">
                <div class="flex items-center">
                    <div class="bg-green-500 rounded-full p-2 mr-3">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-green-900">Nouvelle Demande</p>
                        <p class="text-sm text-green-700">Créer une demande de devis</p>
                    </div>
                </div>
            </a>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="bg-yellow-500 rounded-full p-2 mr-3">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-yellow-900">{{ $mesDemandesEnAttente }} En Attente</p>
                        <p class="text-sm text-yellow-700">Demandes en validation</p>
                    </div>
                </div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="bg-blue-500 rounded-full p-2 mr-3">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-blue-900">{{ $demandesApprouvees }} Approuvées</p>
                        <p class="text-sm text-blue-700">Cette semaine</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mes Demandes Récentes --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Mes Demandes Récentes</h3>
        <div class="space-y-3">
            @forelse($dernieresDemandes as $demande)
            <div class="flex items-center justify-between border-l-4 border-{{ $demande->statut === 'pending' ? 'yellow' : ($demande->statut === 'delivered' ? 'green' : 'blue') }}-400 bg-gray-50 p-3 rounded-r-lg">
                <div>
                    <p class="font-medium">{{ $demande->denomination }}</p>
                    <p class="text-sm text-gray-600">{{ number_format($demande->prix_total_ttc, 2) }}€ - {{ $demande->created_at->format('d/m/Y') }}</p>
                </div>
                <span class="px-3 py-1 text-xs font-medium rounded-full {{ $demande->statut === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($demande->statut === 'delivered' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800') }}">
                    {{ ucfirst(str_replace('_', ' ', $demande->statut)) }}
                </span>
            </div>
            @empty
            <p class="text-gray-500 text-center py-4">Aucune demande récente</p>
            @endforelse
        </div>
    </div>
</div>

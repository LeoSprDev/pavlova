<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Test de la page notifications</h2>
            
            @php
                $notifications = $this->getNotifications();
                $totalNotifications = count($notifications);
            @endphp
            
            <p>Nombre total de notifications : {{ $totalNotifications }}</p>
            
            @if($totalNotifications > 0)
                <div class="mt-4 space-y-2">
                    @foreach($notifications as $notification)
                        <div class="p-3 bg-gray-100 rounded">
                            <strong>{{ $notification['title'] }}</strong><br>
                            {{ $notification['message'] }}
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 mt-4">Aucune notification</p>
            @endif
        </div>
        
        <!-- Version complète cachée pour le moment -->
        <div style="display: none;">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @php
                $warningCount = count(array_filter($notifications, fn($n) => $n['type'] === 'warning'));
                $infoCount = count(array_filter($notifications, fn($n) => $n['type'] === 'info'));
                $successCount = count(array_filter($notifications, fn($n) => $n['type'] === 'success'));
            @endphp
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2C5.03 2 1 6.03 1 11c0 4.97 4.03 9 9 9s9-4.03 9-9c0-4.97-4.03-9-9-9zM9 17l-5-5 1.41-1.41L9 14.17l7.59-7.59L18 8l-9 9z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $totalNotifications }}</h3>
                        <p class="text-sm text-gray-600">Total notifications</p>
                    </div>
                </div>
            </div>
            
            @if($warningCount > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100">
                        <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $warningCount }}</h3>
                        <p class="text-sm text-gray-600">Validations service</p>
                    </div>
                </div>
            </div>
            @endif
            
            @if($infoCount > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L18 8l-8 8z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $infoCount }}</h3>
                        <p class="text-sm text-gray-600">Validations budget</p>
                    </div>
                </div>
            </div>
            @endif
            
            @if($successCount > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $successCount }}</h3>
                        <p class="text-sm text-gray-600">Validations achat</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Notifications en attente</h2>
            </div>
            
            <div class="divide-y divide-gray-200">
                @forelse ($notifications as $notification)
                    <div class="p-6 flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            @if($notification['type'] === 'warning')
                                <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @elseif($notification['type'] === 'info')
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @elseif($notification['type'] === 'success')
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900">{{ $notification['title'] }}</p>
                                <p class="text-sm text-gray-500">{{ $notification['created_at']->diffForHumans() }}</p>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ $notification['message'] }}</p>
                        </div>
                        
                        <div class="flex-shrink-0">
                            <a href="{{ $notification['action_url'] }}" 
                               class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white 
                               @if($notification['type'] === 'warning') bg-yellow-600 hover:bg-yellow-700 
                               @elseif($notification['type'] === 'info') bg-blue-600 hover:bg-blue-700 
                               @elseif($notification['type'] === 'success') bg-green-600 hover:bg-green-700 
                               @endif focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ $notification['action_label'] }}
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 17H4l5 5v-5zm6-10a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Aucune notification</h3>
                        <p class="mt-2 text-sm text-gray-500">Vous êtes à jour ! Toutes les tâches sont terminées.</p>
                    </div>
                @endforelse
            </div>
        </div>
        </div>
    </div>
</x-filament-panels::page>

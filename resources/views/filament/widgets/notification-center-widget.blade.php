<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Notifications') }}
        </x-slot>
        
        <div class="space-y-4">
            @forelse ($notifications as $notification)
                <div class="flex items-start space-x-3 p-3 rounded-lg border 
                    @if($notification['type'] === 'warning') bg-yellow-50 border-yellow-200 
                    @elseif($notification['type'] === 'info') bg-blue-50 border-blue-200 
                    @elseif($notification['type'] === 'success') bg-green-50 border-green-200 
                    @elseif($notification['type'] === 'danger') bg-red-50 border-red-200 
                    @else bg-gray-50 border-gray-200 @endif">
                    
                    <div class="flex-shrink-0">
                        @if($notification['type'] === 'warning')
                            <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        @elseif($notification['type'] === 'info')
                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        @elseif($notification['type'] === 'success')
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        @elseif($notification['type'] === 'danger')
                            <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $notification['title'] }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $notification['message'] }}
                        </p>
                    </div>
                    
                    <div class="flex-shrink-0">
                        <a href="{{ $notification['url'] }}" 
                           class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-md 
                           @if($notification['type'] === 'warning') text-yellow-700 bg-yellow-100 hover:bg-yellow-200 
                           @elseif($notification['type'] === 'info') text-blue-700 bg-blue-100 hover:bg-blue-200 
                           @elseif($notification['type'] === 'success') text-green-700 bg-green-100 hover:bg-green-200 
                           @elseif($notification['type'] === 'danger') text-red-700 bg-red-100 hover:bg-red-200 
                           @else text-gray-700 bg-gray-100 hover:bg-gray-200 @endif">
                            {{ $notification['action'] }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 17H4l5 5v-5zm6-10a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <p class="text-sm">Aucune notification en attente</p>
                    <p class="text-xs text-gray-400 mt-1">Vous êtes à jour !</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

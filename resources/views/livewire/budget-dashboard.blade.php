<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($stats as $stat)
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-{{ $stat->getColor() ?? 'gray' }}-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ $stat->getLabel() }}</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stat->getValue() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                @if($stat->getDescription())
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm text-gray-500">{{ $stat->getDescription() }}</div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

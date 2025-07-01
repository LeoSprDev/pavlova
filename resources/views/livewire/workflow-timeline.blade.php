<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Suivi Workflow</h3>
        @if($canUserAct && $currentStep)
            <div class="flex space-x-2">
                <button wire:click="approveStep"
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    Approuver
                </button>
                <button wire:click="rejectStep"
                        class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                    Rejeter
                </button>
            </div>
        @endif
    </div>

    <div class="flow-root">
        <ul class="-mb-8">
            @foreach($timelineEvents as $index => $event)
                <li>
                    <div class="relative pb-8">
                        @if(!$loop->last)
                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                        @endif
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full bg-{{ $event['color'] }}-500 flex items-center justify-center ring-8 ring-white">
                                    @svg($event['icon'], 'h-5 w-5 text-white')
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                <div>
                                    <p class="text-sm text-gray-500">
                                        <strong class="font-medium text-gray-900">{{ $event['title'] }}</strong>
                                        {{ $event['description'] }}
                                    </p>
                                    @if(isset($event['comment']) && $event['comment'])
                                        <div class="mt-2 text-sm text-gray-600 italic">
                                            "{{ $event['comment'] }}"
                                        </div>
                                    @endif
                                </div>
                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                    {{ $event['date']->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>

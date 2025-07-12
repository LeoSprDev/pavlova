<x-filament-widgets::widget class="fi-advanced-animations-widget">
    <div class="space-y-6">
        <!-- Progress Rings Animés -->
        <div class="grid grid-cols-3 gap-6">
            @foreach($this->getKPIs() as $kpi)
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center w-32 h-32 mb-4">
                        <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="50" fill="none" stroke="currentColor" stroke-width="8" class="text-gray-200 dark:text-gray-700" />
                            <circle cx="60" cy="60" r="50" fill="none" stroke="currentColor" stroke-width="8" stroke-linecap="round" class="text-{{ $kpi['color'] }}-500 transition-all duration-2000 ease-out" style="stroke-dasharray: {{ 2 * pi() * 50 }}; stroke-dashoffset: {{ 2 * pi() * 50 * (1 - $kpi['percentage'] / 100) }};" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white counter" data-target="{{ $kpi['percentage'] }}">0</span>
                            <span class="text-sm text-gray-500 ml-1">%</span>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $kpi['label'] }}</h3>
                    <p class="text-xs text-gray-500">{{ $kpi['description'] }}</p>
                </div>
            @endforeach
        </div>

        <!-- Cards avec morphing animations -->
        <div class="grid grid-cols-2 gap-4">
            @foreach($this->getMetrics() as $metric)
                <div class="metric-card bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 transform transition-all duration-300 hover:scale-105 hover:shadow-xl hover:-translate-y-1" onmouseenter="this.style.background = 'linear-gradient(135deg, {{ $metric['gradient'][0] }}, {{ $metric['gradient'][1] }})'" onmouseleave="this.style.background = ''">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $metric['label'] }}</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white number-animation" data-value="{{ $metric['value'] }}">0</p>
                            <p class="text-sm {{ $metric['trend'] > 0 ? 'text-green-600' : 'text-red-600' }} flex items-center">
                                @if($metric['trend'] > 0)
                                    <svg class="w-4 h-4 mr-1 animate-bounce" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 mr-1 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                                {{ abs($metric['trend']) }}%
                            </p>
                        </div>
                        <div class="icon-container w-12 h-12 bg-{{ $metric['color'] }}-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-{{ $metric['color'] }}-600 transform transition-transform duration-500 hover:rotate-12" fill="currentColor">
                                {!! $metric['icon'] !!}
                            </svg>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>

<style>
@keyframes countUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes shimmer {
    0% { background-position: -200px 0; }
    100% { background-position: calc(200px + 100%) 0; }
}

.counter {
    animation: countUp 0.6s ease-out;
}

.number-animation {
    background: linear-gradient(90deg, #f0f0f0 0px, #e0e0e0 40px, #f0f0f0 80px);
    background-size: 200px;
    animation: shimmer 1.5s infinite;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.metric-card {
    background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid rgba(0,0,0,0.05);
    backdrop-filter: blur(10px);
}

.icon-container {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.metric-card:hover .icon-container {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.dark .metric-card {
    background: linear-gradient(145deg, #1f2937 0%, #111827 100%);
    border: 1px solid rgba(255,255,255,0.1);
}

.dark .number-animation {
    background: linear-gradient(90deg, #374151 0px, #4b5563 40px, #374151 80px);
    background-size: 200px;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 2000;
        const start = performance.now();

        function updateCounter(timestamp) {
            const progress = Math.min((timestamp - start) / duration, 1);
            const current = Math.floor(progress * target);
            counter.textContent = current;
            if (progress < 1) requestAnimationFrame(updateCounter);
        }
        requestAnimationFrame(updateCounter);
    });

    const numbers = document.querySelectorAll('.number-animation');
    numbers.forEach(number => {
        const target = parseFloat(number.getAttribute('data-value'));
        const duration = 1500;
        const start = performance.now();
        function updateNumber(timestamp) {
            const progress = Math.min((timestamp - start) / duration, 1);
            const current = progress * target;
            number.textContent = Number.isInteger(target) ? Math.floor(current).toLocaleString() : current.toFixed(1);
            if (progress < 1) requestAnimationFrame(updateNumber);
        }
        requestAnimationFrame(updateNumber);
    });
});

setInterval(() => {
    if (typeof Livewire !== 'undefined' && !document.hidden) {
        document.querySelectorAll('.metric-card').forEach(card => {
            card.style.opacity = '0.7';
            card.style.transform = 'scale(0.98)';
        });
        Livewire.emit('refreshWidget');
        setTimeout(() => {
            document.querySelectorAll('.metric-card').forEach(card => {
                card.style.opacity = '1';
                card.style.transform = 'scale(1)';
            });
        }, 500);
    }
}, 30000);
</script>

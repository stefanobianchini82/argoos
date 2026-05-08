<div wire:poll.30s>
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h1 class="text-lg font-semibold text-gray-900">Hosts</h1>
            <span class="text-sm text-gray-400">{{ $hosts->count() }} host{{ $hosts->count() !== 1 ? 's' : '' }}</span>
        </div>
        <a href="/hosts/create"
           class="inline-flex items-center gap-1.5 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-1.5 transition-colors">
            + New host
        </a>
    </div>

    @if($hosts->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <p class="text-sm">No hosts registered yet.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($hosts as $host)
                <a href="/hosts/{{ $host->id }}"
                   class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-sm transition-all">

                    <div class="flex items-start justify-between mb-4">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ $host->label }}</p>
                            @if($host->ip)
                                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $host->ip }}</p>
                            @endif
                        </div>
                        @if($host->isOnline())
                            <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2 py-0.5 ml-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Online
                            </span>
                        @else
                            <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-full px-2 py-0.5 ml-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                Offline
                            </span>
                        @endif
                    </div>

                    @if($host->latestMetric)
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-gray-50 rounded-lg px-3 py-2">
                                <p class="text-xs text-gray-400 mb-0.5">CPU</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ number_format($host->latestMetric->cpu_usage, 1) }}%
                                </p>
                            </div>
                            <div class="bg-gray-50 rounded-lg px-3 py-2">
                                <p class="text-xs text-gray-400 mb-0.5">RAM</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ number_format($host->latestMetric->ram_used / $host->latestMetric->ram_total * 100, 1) }}%
                                </p>
                            </div>
                            <div class="bg-gray-50 rounded-lg px-3 py-2">
                                <p class="text-xs text-gray-400 mb-0.5">Load</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ number_format($host->latestMetric->load_avg_1, 2) }}
                                </p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-3">
                            Last seen {{ $host->last_seen_at?->diffForHumans() ?? 'never' }}
                        </p>
                    @else
                        <p class="text-sm text-gray-400">No metrics received yet.</p>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</div>

<div wire:poll.30s>
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Hosts</h1>
            <span class="text-sm text-gray-400 dark:text-gray-500">{{ $hosts->count() }} host{{ $hosts->count() !== 1 ? 's' : '' }}</span>
        </div>
        <a href="/hosts/create"
           class="inline-flex items-center gap-1.5 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-1.5 transition-colors">
            + New host
        </a>
    </div>

    @if($this->availableTags->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2 mb-6">
            <button
                wire:click="$set('filterTag', '')"
                @class([
                    'px-3 py-1 rounded-full text-xs font-medium border transition-colors',
                    'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 border-gray-900 dark:border-gray-100' => $filterTag === '',
                    'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:border-gray-500 dark:hover:border-gray-400' => $filterTag !== '',
                ])
            >All</button>
            @foreach($this->availableTags as $tagName)
                <button
                    wire:click="$set('filterTag', '{{ $tagName }}')"
                    @class([
                        'px-3 py-1 rounded-full text-xs font-medium border transition-colors',
                        'bg-indigo-600 text-white border-indigo-600' => $filterTag === $tagName,
                        'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:border-indigo-400 dark:hover:border-indigo-500' => $filterTag !== $tagName,
                    ])
                >{{ $tagName }}</button>
            @endforeach
        </div>
    @endif

    @if($hosts->isEmpty())
        <div class="text-center py-16 text-gray-400 dark:text-gray-500">
            <p class="text-sm">No hosts registered yet.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($hosts as $host)
                <a href="/hosts/{{ $host->id }}"
                   class="block bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:border-indigo-300 dark:hover:border-indigo-500 hover:shadow-sm transition-all">

                    <div class="flex items-start justify-between mb-4">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $host->label }}</p>
                            @if($host->ip)
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 font-mono">{{ $host->ip }}</p>
                            @endif
                            @if($host->tags->isNotEmpty())
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    @foreach($host->tags as $tag)
                                        <span class="inline-block px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @if($host->isOnline())
                            <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-full px-2 py-0.5 ml-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Online
                            </span>
                        @else
                            <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-full px-2 py-0.5 ml-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                Offline
                            </span>
                        @endif
                    </div>

                    @if($host->latestMetric)
                        <div class="grid grid-cols-2 gap-3">
                            <div @class([
                                'rounded-lg px-3 py-2',
                                'bg-gray-50 dark:bg-gray-800'        => $host->latestMetric->cpu_usage < 80,
                                'bg-yellow-50 dark:bg-yellow-900/30' => $host->latestMetric->cpu_usage >= 80 && $host->latestMetric->cpu_usage < 90,
                                'bg-red-50 dark:bg-red-900/30'       => $host->latestMetric->cpu_usage >= 90,
                            ])>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">CPU</p>
                                <p @class([
                                    'text-sm font-semibold',
                                    'text-gray-800 dark:text-gray-200'     => $host->latestMetric->cpu_usage < 80,
                                    'text-yellow-700 dark:text-yellow-400' => $host->latestMetric->cpu_usage >= 80 && $host->latestMetric->cpu_usage < 90,
                                    'text-red-700 dark:text-red-400'       => $host->latestMetric->cpu_usage >= 90,
                                ])>{{ number_format($host->latestMetric->cpu_usage, 1) }}%</p>
                            </div>
                            <div @class([
                                'rounded-lg px-3 py-2',
                                'bg-gray-50 dark:bg-gray-800'        => $host->latestMetric->ram_percentage < 80,
                                'bg-yellow-50 dark:bg-yellow-900/30' => $host->latestMetric->ram_percentage >= 80 && $host->latestMetric->ram_percentage < 90,
                                'bg-red-50 dark:bg-red-900/30'       => $host->latestMetric->ram_percentage >= 90,
                            ])>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">RAM</p>
                                <p @class([
                                    'text-sm font-semibold',
                                    'text-gray-800 dark:text-gray-200'     => $host->latestMetric->ram_percentage < 80,
                                    'text-yellow-700 dark:text-yellow-400' => $host->latestMetric->ram_percentage >= 80 && $host->latestMetric->ram_percentage < 90,
                                    'text-red-700 dark:text-red-400'       => $host->latestMetric->ram_percentage >= 90,
                                ])>{{ number_format($host->latestMetric->ram_percentage, 1) }}%</p>
                            </div>
                            <div @class([
                                'rounded-lg px-3 py-2',
                                'bg-gray-50 dark:bg-gray-800'        => ($diskUsagePct[$host->id] ?? 0) < 80,
                                'bg-yellow-50 dark:bg-yellow-900/30' => ($diskUsagePct[$host->id] ?? 0) >= 80 && ($diskUsagePct[$host->id] ?? 0) < 90,
                                'bg-red-50 dark:bg-red-900/30'       => ($diskUsagePct[$host->id] ?? 0) >= 90,
                            ])>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Disk</p>
                                <p @class([
                                    'text-sm font-semibold',
                                    'text-gray-800 dark:text-gray-200'     => ($diskUsagePct[$host->id] ?? 0) < 80,
                                    'text-yellow-700 dark:text-yellow-400' => ($diskUsagePct[$host->id] ?? 0) >= 80 && ($diskUsagePct[$host->id] ?? 0) < 90,
                                    'text-red-700 dark:text-red-400'       => ($diskUsagePct[$host->id] ?? 0) >= 90,
                                ])>
                                    @isset($diskUsagePct[$host->id])
                                        {{ number_format($diskUsagePct[$host->id], 1) }}%
                                    @else
                                        —
                                    @endisset
                                </p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg px-3 py-2">
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Load</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                    {{ number_format($host->latestMetric->load_avg_1, 2) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-3">
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Last seen {{ $host->last_seen_at?->diffForHumans() ?? 'never' }}
                            </p>
                            @if($host->latestMetric->formatted_uptime)
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    up {{ $host->latestMetric->formatted_uptime }}
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500">No metrics received yet.</p>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</div>

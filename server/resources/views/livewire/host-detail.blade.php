<div wire:poll.10s>
    {{-- Header --}}
    <x-breadcrumbs :items="[
        ['label' => 'Hosts', 'url' => '/'],
        ['label' => $this->host->label],
    ]" />

    <div class="flex items-center gap-3 mb-8">
        <div class="min-w-0 flex-1">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->host->label }}</h1>
            @if($this->host->ip)
                <p class="text-sm text-gray-400 dark:text-gray-500 font-mono mt-0.5">{{ $this->host->ip }}</p>
            @endif
        </div>
        @if($this->host->isOnline())
            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-full px-2 py-0.5">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                Online
            </span>
        @else
            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-full px-2 py-0.5">
                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                Offline
            </span>
        @endif
        <div class="flex items-center gap-2 ml-2">
            <a href="{{ route('hosts.alerts', $this->host) }}"
               class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 transition-colors">
                Alerts
            </a>
            <a href="{{ route('hosts.checks', $this->host) }}"
               class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 transition-colors">
                Checks
            </a>
            <a href="/hosts/{{ $this->host->id }}/edit"
               class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 transition-colors">
                Edit
            </a>
            <button
                wire:click="confirmDelete"
                class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 border border-red-200 dark:border-red-800 hover:border-red-400 dark:hover:border-red-600 rounded-lg px-3 py-1.5 transition-colors"
            >
                Delete
            </button>
        </div>
    </div>

    @if($confirmingDelete)
        <div class="mb-6 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 p-4 flex items-center justify-between gap-4">
            <p class="text-sm text-red-700 dark:text-red-400">
                Delete <strong>{{ $this->host->label }}</strong>? This will permanently remove all metrics and disk data.
            </p>
            <div class="flex items-center gap-2 shrink-0">
                <button wire:click="cancelDelete"
                        class="text-sm px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    Cancel
                </button>
                <button wire:click="deleteHost"
                        class="text-sm px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white transition-colors">
                    Delete
                </button>
            </div>
        </div>
    @endif

    @if($latestMetric)
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
            Collected {{ $latestMetric->collected_at->diffForHumans() }}
            &mdash; {{ $latestMetric->collected_at->format('Y-m-d H:i:s') }}
        </p>

        {{-- Live metrics grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">

            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">CPU Usage</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($latestMetric->cpu_usage, 1) }}<span class="text-sm font-normal text-gray-400 dark:text-gray-500">%</span></p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">RAM Used</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ number_format($latestMetric->ram_used / 1024 / 1024 / 1024, 1) }}<span class="text-sm font-normal text-gray-400 dark:text-gray-500"> GB</span>
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    of {{ number_format($latestMetric->ram_total / 1024 / 1024 / 1024, 1) }} GB
                    ({{ number_format($latestMetric->ram_used / $latestMetric->ram_total * 100, 0) }}%)
                </p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Load Avg</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ number_format($latestMetric->load_avg_1, 2) }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    5m: {{ number_format($latestMetric->load_avg_5, 2) }}
                    &middot;
                    15m: {{ number_format($latestMetric->load_avg_15, 2) }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Disk I/O</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    ↑ {{ number_format($latestMetric->disk_write_bytes / 1024, 0) }} <span class="font-normal text-gray-400 dark:text-gray-500">KB/s</span>
                </p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5">
                    ↓ {{ number_format($latestMetric->disk_read_bytes / 1024, 0) }} <span class="font-normal text-gray-400 dark:text-gray-500">KB/s</span>
                </p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Network</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    ↑ {{ number_format($latestMetric->net_tx_bytes / 1024, 0) }} <span class="font-normal text-gray-400 dark:text-gray-500">KB/s</span>
                </p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5">
                    ↓ {{ number_format($latestMetric->net_rx_bytes / 1024, 0) }} <span class="font-normal text-gray-400 dark:text-gray-500">KB/s</span>
                </p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Uptime</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $latestMetric->formatted_uptime ?? '—' }}</p>
            </div>

        </div>

        {{-- Historical charts --}}
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">History</h2>
            <div class="flex items-center gap-1">
                @foreach([['1h','1h'], ['6h','6h'], ['24h','24h'], ['7d','7d']] as [$val, $label])
                    <button
                        wire:click="setRange('{{ $val }}')"
                        class="text-xs font-medium px-3 py-1 rounded-lg transition-colors
                            {{ $range === $val
                                ? 'bg-indigo-600 text-white'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        @if(count($chartData['labels']) === 0)
            <div wire:key="chart-empty" class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 py-12 text-center text-sm text-gray-400 dark:text-gray-500 mb-8">
                No data for this time range.
            </div>
        @else
            <div
                wire:key="chart-live"
                wire:ignore
                x-data="metricCharts(@js($chartData))"
                @charts-updated.window="update($event.detail.data)"
            >
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">CPU Usage (%)</p>
                        <div class="h-32">
                            <canvas x-ref="cpuChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">RAM Usage (%)</p>
                        <div class="h-32">
                            <canvas x-ref="ramChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">Disk I/O (KB/s)</p>
                        <div class="h-32">
                            <canvas x-ref="diskChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">Network (KB/s)</p>
                        <div class="h-32">
                            <canvas x-ref="netChart"></canvas>
                        </div>
                    </div>

                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-8">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">Load Average (1m)</p>
                    <div class="h-28">
                        <canvas x-ref="loadChart"></canvas>
                    </div>
                </div>
            </div>
        @endif

        {{-- Disk partitions --}}
        @if($latestPartitions->isNotEmpty())
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Disk Partitions</h2>
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800">
                            <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Mount</th>
                            <th class="text-right text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Total</th>
                            <th class="text-right text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Used</th>
                            <th class="text-right text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Free</th>
                            <th class="text-right text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Usage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($latestPartitions as $partition)
                            @php $usagePct = $partition->total > 0 ? round($partition->used / $partition->total * 100) : 0; @endphp
                            <tr>
                                <td class="px-4 py-3 font-mono text-gray-700 dark:text-gray-300">{{ $partition->mount_point }}</td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ number_format($partition->total / 1024 / 1024 / 1024, 1) }} GB</td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ number_format($partition->used / 1024 / 1024 / 1024, 1) }} GB</td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ number_format($partition->free / 1024 / 1024 / 1024, 1) }} GB</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full
                                        @if($usagePct >= 90) bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                        @elseif($usagePct >= 75) bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400
                                        @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400
                                        @endif">
                                        {{ $usagePct }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        <div class="text-center py-16 text-gray-400 dark:text-gray-500">
            <p class="text-sm">No metrics received yet for this host.</p>
        </div>
    @endif
</div>

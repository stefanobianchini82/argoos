<div wire:poll.10s>
    {{-- Header --}}
    <div class="mb-6">
        <a href="/" class="text-sm text-gray-400 hover:text-gray-600 transition-colors">← All hosts</a>
    </div>

    <div class="flex items-center gap-3 mb-8">
        <div class="min-w-0">
            <h1 class="text-lg font-semibold text-gray-900">{{ $this->host->label }}</h1>
            @if($this->host->ip)
                <p class="text-sm text-gray-400 font-mono mt-0.5">{{ $this->host->ip }}</p>
            @endif
        </div>
        @if($this->host->isOnline())
            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2 py-0.5">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                Online
            </span>
        @else
            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-full px-2 py-0.5">
                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                Offline
            </span>
        @endif
    </div>

    @if($latestMetric)
        <p class="text-xs text-gray-400 mb-4">
            Collected {{ $latestMetric->collected_at->diffForHumans() }}
            &mdash; {{ $latestMetric->collected_at->format('Y-m-d H:i:s') }}
        </p>

        {{-- Metrics grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-400 mb-1">CPU Usage</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($latestMetric->cpu_usage, 1) }}<span class="text-sm font-normal text-gray-400">%</span></p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-400 mb-1">RAM Used</p>
                <p class="text-2xl font-semibold text-gray-900">
                    {{ number_format($latestMetric->ram_used / 1024 / 1024 / 1024, 1) }}<span class="text-sm font-normal text-gray-400"> GB</span>
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    of {{ number_format($latestMetric->ram_total / 1024 / 1024 / 1024, 1) }} GB
                    ({{ number_format($latestMetric->ram_used / $latestMetric->ram_total * 100, 0) }}%)
                </p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-400 mb-1">Load Avg</p>
                <p class="text-lg font-semibold text-gray-900">{{ number_format($latestMetric->load_avg_1, 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    5m: {{ number_format($latestMetric->load_avg_5, 2) }}
                    &middot;
                    15m: {{ number_format($latestMetric->load_avg_15, 2) }}
                </p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-400 mb-1">Disk I/O</p>
                <p class="text-sm font-semibold text-gray-900">
                    ↑ {{ number_format($latestMetric->disk_write_bytes / 1024, 0) }} <span class="font-normal text-gray-400">KB/s</span>
                </p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                    ↓ {{ number_format($latestMetric->disk_read_bytes / 1024, 0) }} <span class="font-normal text-gray-400">KB/s</span>
                </p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-400 mb-1">Network</p>
                <p class="text-sm font-semibold text-gray-900">
                    ↑ {{ number_format($latestMetric->net_tx_bytes / 1024, 0) }} <span class="font-normal text-gray-400">KB/s</span>
                </p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                    ↓ {{ number_format($latestMetric->net_rx_bytes / 1024, 0) }} <span class="font-normal text-gray-400">KB/s</span>
                </p>
            </div>

        </div>

        {{-- Disk partitions --}}
        @if($latestPartitions->isNotEmpty())
            <h2 class="text-sm font-semibold text-gray-700 mb-3">Disk Partitions</h2>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="text-left text-xs font-medium text-gray-400 px-4 py-3">Mount</th>
                            <th class="text-right text-xs font-medium text-gray-400 px-4 py-3">Total</th>
                            <th class="text-right text-xs font-medium text-gray-400 px-4 py-3">Used</th>
                            <th class="text-right text-xs font-medium text-gray-400 px-4 py-3">Free</th>
                            <th class="text-right text-xs font-medium text-gray-400 px-4 py-3">Usage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($latestPartitions as $partition)
                            @php $usagePct = $partition->total > 0 ? round($partition->used / $partition->total * 100) : 0; @endphp
                            <tr>
                                <td class="px-4 py-3 font-mono text-gray-700">{{ $partition->mount_point }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($partition->total / 1024 / 1024 / 1024, 1) }} GB</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($partition->used / 1024 / 1024 / 1024, 1) }} GB</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($partition->free / 1024 / 1024 / 1024, 1) }} GB</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full
                                        @if($usagePct >= 90) bg-red-50 text-red-700
                                        @elseif($usagePct >= 75) bg-yellow-50 text-yellow-700
                                        @else bg-gray-100 text-gray-600
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
        <div class="text-center py-16 text-gray-400">
            <p class="text-sm">No metrics received yet for this host.</p>
        </div>
    @endif
</div>

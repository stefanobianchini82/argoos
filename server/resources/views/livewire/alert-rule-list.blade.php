<div>
    {{-- Header --}}
    <x-breadcrumbs :items="[
        ['label' => 'Hosts', 'url' => '/'],
        ['label' => $this->host->label, 'url' => route('hosts.show', $this->host)],
        ['label' => 'Alerts'],
    ]" />

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Alert Rules</h1>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $this->host->label }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if($this->otherHosts->isNotEmpty() && !$showCopyFrom)
                <button wire:click="openCopyFrom"
                        class="inline-flex items-center gap-1.5 text-sm font-medium border border-indigo-300 dark:border-indigo-700 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg px-4 py-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Copia da
                </button>
            @endif
            <a href="{{ route('hosts.alerts.create', $this->host) }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add rule
            </a>
        </div>
    </div>

    {{-- Pannello "Copia da" --}}
    @if($showCopyFrom)
        <div class="mb-6 rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/20 p-4">
            @if(!$selectedSourceHostId)
                {{-- Stato 1: selezione host sorgente --}}
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300 shrink-0">Copia da:</span>
                    <select wire:model.live="selectedSourceHostId"
                            class="flex-1 text-sm rounded-lg border border-indigo-200 dark:border-indigo-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleziona un server...</option>
                        @foreach($this->otherHosts as $h)
                            <option value="{{ $h->id }}">{{ $h->label }}</option>
                        @endforeach
                    </select>
                    <button wire:click="cancelCopy"
                            class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors shrink-0">
                        Annulla
                    </button>
                </div>
            @else
                {{-- Stato 2: conferma copia --}}
                <div class="flex items-center justify-between gap-4">
                    <div class="text-sm">
                        @if($newRulesToCopy > 0)
                            <p class="text-indigo-700 dark:text-indigo-300">
                                Stai per copiare <strong>{{ $newRulesToCopy }} {{ $newRulesToCopy === 1 ? 'alert' : 'alert' }}</strong>
                                da <strong>{{ $this->otherHosts->firstWhere('id', $selectedSourceHostId)?->label }}</strong>.
                                @if($totalSourceRules > $newRulesToCopy)
                                    <span class="text-indigo-500 dark:text-indigo-400">
                                        ({{ $totalSourceRules - $newRulesToCopy }} già {{ ($totalSourceRules - $newRulesToCopy) === 1 ? 'presente' : 'presenti' }}, {{ ($totalSourceRules - $newRulesToCopy) === 1 ? 'verrà saltato' : 'verranno saltati' }}.)
                                    </span>
                                @endif
                            </p>
                        @else
                            <p class="text-gray-500 dark:text-gray-400">
                                Tutti gli alert di
                                <strong>{{ $this->otherHosts->firstWhere('id', $selectedSourceHostId)?->label }}</strong>
                                sono già presenti su questo host.
                            </p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button wire:click="cancelCopy"
                                class="text-sm px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            Annulla
                        </button>
                        @if($newRulesToCopy > 0)
                            <button wire:click="executeCopy"
                                    class="text-sm px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition-colors">
                                Copia
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if($rules->isEmpty())
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 py-16 text-center">
            <p class="text-sm text-gray-400 dark:text-gray-500">No alert rules defined for this host.</p>
            <a href="{{ route('hosts.alerts.create', $this->host) }}"
               class="inline-block mt-4 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                Add your first rule →
            </a>
        </div>
    @else
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800">
                        <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Metric</th>
                        <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Condition</th>
                        <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Duration</th>
                        <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Channel</th>
                        <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Target</th>
                        <th class="text-center text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Active</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($rules as $rule)
                        <tr class="{{ $rule->is_active ? '' : 'opacity-50' }}">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium">{{ $rule->metricLabel() }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $rule->operator }} {{ $rule->threshold }}{{ $rule->metric === 'disk_usage_percent' ? '%' : '' }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $rule->duration_minutes }}m</td>
                            <td class="px-4 py-3">
                                <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full
                                    @if($rule->channel === 'email') bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                    @elseif($rule->channel === 'telegram') bg-sky-50 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400
                                    @else bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400
                                    @endif">
                                    {{ $rule->channel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs font-mono truncate max-w-[160px]">{{ $rule->channel_target }}</td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="toggleActive({{ $rule->id }})"
                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors
                                            {{ $rule->is_active ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600' }}">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                        {{ $rule->is_active ? 'translate-x-4' : 'translate-x-1' }}"></span>
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 justify-end">
                                    <a href="{{ route('hosts.alerts.edit', [$this->host, $rule]) }}"
                                       class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                                        Edit
                                    </a>
                                    <button wire:click="deleteRule({{ $rule->id }})"
                                            wire:confirm="Delete this alert rule?"
                                            class="text-xs font-medium text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div>
    {{-- Header --}}
    <div class="mb-6">
        <a href="/hosts/{{ $this->host->id }}" class="text-sm text-gray-400 hover:text-gray-600 transition-colors">← {{ $this->host->label }}</a>
    </div>

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Alert Rules</h1>
            <p class="text-sm text-gray-400 mt-0.5">{{ $this->host->label }}</p>
        </div>
        <a href="{{ route('hosts.alerts.create', $this->host) }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add rule
        </a>
    </div>

    @if($rules->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
            <p class="text-sm text-gray-400">No alert rules defined for this host.</p>
            <a href="{{ route('hosts.alerts.create', $this->host) }}"
               class="inline-block mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                Add your first rule →
            </a>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left text-xs font-medium text-gray-400 px-4 py-3">Metric</th>
                        <th class="text-left text-xs font-medium text-gray-400 px-4 py-3">Condition</th>
                        <th class="text-left text-xs font-medium text-gray-400 px-4 py-3">Duration</th>
                        <th class="text-left text-xs font-medium text-gray-400 px-4 py-3">Channel</th>
                        <th class="text-left text-xs font-medium text-gray-400 px-4 py-3">Target</th>
                        <th class="text-center text-xs font-medium text-gray-400 px-4 py-3">Active</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($rules as $rule)
                        <tr class="{{ $rule->is_active ? '' : 'opacity-50' }}">
                            <td class="px-4 py-3 text-gray-700 font-medium">{{ $rule->metricLabel() }}</td>
                            <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $rule->operator }} {{ $rule->threshold }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $rule->duration_minutes }}m</td>
                            <td class="px-4 py-3">
                                <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full
                                    @if($rule->channel === 'email') bg-blue-50 text-blue-700
                                    @elseif($rule->channel === 'telegram') bg-sky-50 text-sky-700
                                    @else bg-purple-50 text-purple-700
                                    @endif">
                                    {{ $rule->channel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs font-mono truncate max-w-[160px]">{{ $rule->channel_target }}</td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="toggleActive({{ $rule->id }})"
                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors
                                            {{ $rule->is_active ? 'bg-indigo-600' : 'bg-gray-200' }}">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                        {{ $rule->is_active ? 'translate-x-4' : 'translate-x-1' }}"></span>
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 justify-end">
                                    <a href="{{ route('hosts.alerts.edit', [$this->host, $rule]) }}"
                                       class="text-xs font-medium text-gray-500 hover:text-gray-900 transition-colors">
                                        Edit
                                    </a>
                                    <button wire:click="deleteRule({{ $rule->id }})"
                                            wire:confirm="Delete this alert rule?"
                                            class="text-xs font-medium text-red-500 hover:text-red-700 transition-colors">
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

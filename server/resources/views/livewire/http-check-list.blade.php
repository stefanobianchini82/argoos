<div>
    {{-- Header --}}
    <x-breadcrumbs :items="[
        ['label' => 'Hosts', 'url' => '/'],
        ['label' => $this->host->label, 'url' => route('hosts.show', $this->host)],
        ['label' => 'HTTP Checks'],
    ]" />

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">HTTP Checks</h1>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $this->host->label }}</p>
        </div>
        <a href="{{ route('hosts.checks.create', $this->host) }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add check
        </a>
    </div>

    @if($checks->isEmpty())
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 py-16 text-center">
            <p class="text-sm text-gray-400 dark:text-gray-500">No HTTP checks defined for this host.</p>
            <a href="{{ route('hosts.checks.create', $this->host) }}"
               class="inline-block mt-4 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                Add your first check →
            </a>
        </div>
    @else
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800">
                        <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Label</th>
                        <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">URL</th>
                        <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Method</th>
                        <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Expect</th>
                        <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Status</th>
                        <th class="text-left text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Channel</th>
                        <th class="text-center text-xs font-medium text-gray-400 dark:text-gray-500 px-4 py-3">Active</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($checks as $check)
                        @php $isDown = $check->openEvent !== null; @endphp
                        <tr class="{{ $check->is_active ? '' : 'opacity-50' }}">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium">{{ $check->label }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs font-mono truncate max-w-[200px]">{{ $check->url }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs font-mono">{{ $check->method }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs font-mono">{{ $check->expected_status_code }}</td>
                            <td class="px-4 py-3">
                                @if(! $check->is_active)
                                    <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                        Paused
                                    </span>
                                @elseif($isDown)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        DOWN
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        UP
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full
                                    @if($check->channel === 'email') bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                    @elseif($check->channel === 'telegram') bg-sky-50 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400
                                    @else bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400
                                    @endif">
                                    {{ $check->channel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="toggleActive({{ $check->id }})"
                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors
                                            {{ $check->is_active ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600' }}">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                        {{ $check->is_active ? 'translate-x-4' : 'translate-x-1' }}"></span>
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 justify-end">
                                    <a href="{{ route('hosts.checks.edit', [$this->host, $check]) }}"
                                       class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                                        Edit
                                    </a>
                                    <button wire:click="deleteCheck({{ $check->id }})"
                                            wire:confirm="Delete this HTTP check?"
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

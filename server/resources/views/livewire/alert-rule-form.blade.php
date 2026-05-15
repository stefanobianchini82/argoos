<div>
    {{-- Header --}}
    <x-breadcrumbs :items="[
        ['label' => 'Hosts', 'url' => '/'],
        ['label' => $this->host->label, 'url' => route('hosts.show', $this->host)],
        ['label' => 'Alerts', 'url' => route('hosts.alerts', $this->host)],
        ['label' => $this->alertRule ? 'Edit rule' : 'New rule'],
    ]" />

    <div class="mb-8">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ $this->alertRule ? 'Edit Alert Rule' : 'New Alert Rule' }}
        </h1>
        <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $this->host->label }}</p>
    </div>

    <form wire:submit="save" class="max-w-lg">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">

            {{-- Metric --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Metric</label>
                <select wire:model.live="metric"
                        class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Select a metric…</option>
                    @foreach($metrics as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if($metric === 'disk_usage_percent')
                    <p class="text-xs text-indigo-500 dark:text-indigo-400 mt-1">The threshold is a percentage (0–100). The alert fires if any partition on this host exceeds the threshold for the specified duration.</p>
                @elseif($metric === 'ram_percent')
                    <p class="text-xs text-indigo-500 dark:text-indigo-400 mt-1">The threshold is a percentage (0–100).</p>
                @endif
                @error('metric') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            @if($metric === 'disk_usage_percent')
            {{-- Excluded partitions --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Excluded partitions</label>
                <input type="text" wire:model="excludedPartitions"
                       placeholder="/boot, /boot/efi"
                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Comma-separated mount points to ignore (e.g. /boot, /boot/efi).</p>
            </div>
            @endif

            {{-- Operator + Threshold --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Operator</label>
                    <select wire:model="operator"
                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($operators as $op)
                            <option value="{{ $op }}">{{ $op }}</option>
                        @endforeach
                    </select>
                    @error('operator') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                        Threshold @if(in_array($metric, ['disk_usage_percent', 'ram_percent'])) (%) @endif
                    </label>
                    <input type="number" step="any" wire:model="threshold"
                           placeholder="{{ $metric === 'disk_usage_percent' ? 'e.g. 80' : 'e.g. 80' }}"
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    @error('threshold') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Duration --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Duration (minutes)</label>
                <input type="number" min="1" max="1440" wire:model="durationMinutes"
                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Alert fires when the average value exceeds the threshold for this many minutes.</p>
                @error('durationMinutes') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Channel --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Notification channel</label>
                <select wire:model.live="channel"
                        class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach($channels as $ch)
                        <option value="{{ $ch }}">{{ ucfirst($ch) }}</option>
                    @endforeach
                </select>
                @error('channel') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Channel target --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                        @if($channel === 'email') Email address
                        @elseif($channel === 'telegram') Telegram Chat ID
                        @else Webhook URL
                        @endif
                    </label>
                    @if($settingValue)
                        <button type="button" wire:click="fillFromSettings"
                                class="text-xs text-indigo-500 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                            Use from settings
                        </button>
                    @endif
                </div>
                <input type="text" wire:model="channelTarget"
                       placeholder="{{ $channel === 'email' ? 'you@example.com' : ($channel === 'telegram' ? '123456789' : 'https://…') }}"
                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                @error('channelTarget') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Active toggle --}}
            <div class="flex items-center gap-3">
                <button type="button" wire:click="$toggle('isActive')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors
                            {{ $isActive ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600' }}">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                        {{ $isActive ? 'translate-x-4' : 'translate-x-1' }}"></span>
                </button>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $isActive ? 'Active' : 'Inactive' }}</span>
            </div>

        </div>

        <div class="flex items-center gap-3 mt-4">
            <button type="submit"
                    class="text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 py-2 transition-colors"
                    wire:loading.attr="disabled">
                <span wire:loading.remove>Save rule</span>
                <span wire:loading>Saving…</span>
            </button>
            <a href="{{ route('hosts.alerts', $this->host) }}"
               class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

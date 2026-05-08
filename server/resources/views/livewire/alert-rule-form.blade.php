<div>
    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('hosts.alerts', $this->host) }}" class="text-sm text-gray-400 hover:text-gray-600 transition-colors">← Alert Rules</a>
    </div>

    <div class="mb-8">
        <h1 class="text-lg font-semibold text-gray-900">
            {{ $this->alertRule ? 'Edit Alert Rule' : 'New Alert Rule' }}
        </h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $this->host->label }}</p>
    </div>

    <form wire:submit="save" class="max-w-lg">
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">

            {{-- Metric --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Metric</label>
                <select wire:model="metric"
                        class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Select a metric…</option>
                    @foreach($metrics as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('metric') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Operator + Threshold --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Operator</label>
                    <select wire:model="operator"
                            class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($operators as $op)
                            <option value="{{ $op }}">{{ $op }}</option>
                        @endforeach
                    </select>
                    @error('operator') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Threshold</label>
                    <input type="number" step="any" wire:model="threshold"
                           placeholder="e.g. 80"
                           class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    @error('threshold') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Duration --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Duration (minutes)</label>
                <input type="number" min="1" max="1440" wire:model="durationMinutes"
                       class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <p class="text-xs text-gray-400 mt-1">Alert fires when the average value exceeds the threshold for this many minutes.</p>
                @error('durationMinutes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Channel --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Notification channel</label>
                <select wire:model.live="channel"
                        class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach($channels as $ch)
                        <option value="{{ $ch }}">{{ ucfirst($ch) }}{{ $ch !== 'email' ? ' (coming soon)' : '' }}</option>
                    @endforeach
                </select>
                @error('channel') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Channel target --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    @if($channel === 'email') Email address
                    @elseif($channel === 'telegram') Telegram Chat ID
                    @else Webhook URL
                    @endif
                </label>
                <input type="text" wire:model="channelTarget"
                       placeholder="{{ $channel === 'email' ? 'you@example.com' : ($channel === 'telegram' ? '123456789' : 'https://…') }}"
                       class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                @error('channelTarget') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Active toggle --}}
            <div class="flex items-center gap-3">
                <button type="button" wire:click="$toggle('isActive')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors
                            {{ $isActive ? 'bg-indigo-600' : 'bg-gray-200' }}">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                        {{ $isActive ? 'translate-x-4' : 'translate-x-1' }}"></span>
                </button>
                <span class="text-sm text-gray-600">{{ $isActive ? 'Active' : 'Inactive' }}</span>
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
               class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

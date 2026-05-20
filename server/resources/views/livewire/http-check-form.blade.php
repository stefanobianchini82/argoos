<div>
    {{-- Header --}}
    <x-breadcrumbs :items="[
        ['label' => 'Hosts', 'url' => '/'],
        ['label' => $this->host->label, 'url' => route('hosts.show', $this->host)],
        ['label' => 'HTTP Checks', 'url' => route('hosts.checks', $this->host)],
        ['label' => $this->httpCheck ? 'Edit check' : 'New check'],
    ]" />

    <div class="mb-8">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ $this->httpCheck ? 'Edit HTTP Check' : 'New HTTP Check' }}
        </h1>
        <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $this->host->label }}</p>
    </div>

    <form wire:submit="save" class="max-w-lg">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">

            {{-- Label --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Label</label>
                <input type="text" wire:model="label"
                       placeholder="e.g. API health endpoint"
                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                @error('label') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- URL --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">URL</label>
                <input type="url" wire:model="url"
                       placeholder="https://example.com/health"
                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                @error('url') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Method + Expected status --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Method</label>
                    <select wire:model="method"
                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($methods as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                    @error('method') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Expected status code</label>
                    <input type="number" min="100" max="599" wire:model="expectedStatusCode"
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    @error('expectedStatusCode') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Timeout --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Timeout (seconds)</label>
                <input type="number" min="1" max="60" wire:model="timeoutSeconds"
                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                @error('timeoutSeconds') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Keyword match --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Keyword match <span class="font-normal text-gray-400 dark:text-gray-500">(optional)</span></label>
                <input type="text" wire:model="keywordMatch"
                       placeholder='e.g. "status":"ok"'
                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">If set, the response body must contain this string for the check to be considered up.</p>
                @error('keywordMatch') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
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
                <span wire:loading.remove>Save check</span>
                <span wire:loading>Saving…</span>
            </button>
            <a href="{{ route('hosts.checks', $this->host) }}"
               class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

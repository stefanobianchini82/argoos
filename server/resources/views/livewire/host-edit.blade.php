<div>
    <x-breadcrumbs :items="[
        ['label' => 'Hosts', 'url' => '/'],
        ['label' => $this->host->label, 'url' => route('hosts.show', $this->host)],
        ['label' => 'Edit'],
    ]" />

    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Edit host</h1>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6 max-w-lg">
        <form wire:submit.prevent="save" class="space-y-5">

            <div>
                <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Label <span class="text-red-500">*</span></label>
                <input
                    id="label"
                    type="text"
                    wire:model="label"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                @error('label')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="ip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IP / Hostname</label>
                <input
                    id="ip"
                    type="text"
                    wire:model="ip"
                    placeholder="e.g. 192.168.1.10"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                @error('ip')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <textarea
                    id="description"
                    wire:model="description"
                    rows="3"
                    placeholder="Optional notes about this host"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                ></textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 transition-colors"
                >
                    <span wire:loading.remove wire:target="save">Save changes</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
                <a href="/hosts/{{ $this->host->id }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Cancel</a>
            </div>

        </form>
    </div>

    {{-- API Key regeneration --}}
    <div class="mt-8 max-w-lg">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">API Key</h2>

        @if($regenerated)
            <div class="rounded-xl border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-950 p-4">
                <p class="text-sm font-medium text-green-800 dark:text-green-200 mb-1">New API key generated.</p>
                <p class="text-xs text-green-700 dark:text-green-400 mb-3">Copy it now — it will not be shown again.</p>
                <div class="flex items-center gap-2">
                    <code id="regen-key-value" class="flex-1 block break-all rounded-lg bg-white dark:bg-gray-800 border border-green-300 dark:border-green-700 px-3 py-2 text-sm font-mono text-gray-900 dark:text-gray-100 select-all">{{ $regeneratedKey }}</code>
                    <button
                        type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('regen-key-value').textContent.trim()); this.textContent = 'Copied!';"
                        class="shrink-0 text-xs font-medium bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 border border-green-300 dark:border-green-700 rounded-lg px-3 py-2 transition-colors"
                    >Copy</button>
                </div>
            </div>
        @elseif($confirmingRegenerate)
            <div class="rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950 p-4">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-1">Regenerate API key?</p>
                <p class="text-xs text-amber-700 dark:text-amber-400 mb-4">The current key will be invalidated immediately. The agent will stop sending metrics until it is reconfigured with the new key.</p>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        wire:click="regenerateApiKey"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-medium rounded-lg px-4 py-2 transition-colors"
                    >
                        <span wire:loading.remove wire:target="regenerateApiKey">Yes, regenerate</span>
                        <span wire:loading wire:target="regenerateApiKey">Regenerating…</span>
                    </button>
                    <button
                        type="button"
                        wire:click="cancelRegenerate"
                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        @else
            <button
                type="button"
                wire:click="confirmRegenerate"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                Regenerate API Key
            </button>
        @endif
    </div>
</div>

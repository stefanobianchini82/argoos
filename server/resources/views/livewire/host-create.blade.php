<div>
    <x-breadcrumbs :items="[
        ['label' => 'Hosts', 'url' => '/'],
        ['label' => 'New host'],
    ]" />

    @unless($created)
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">New host</h1>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6 max-w-lg">
            <form wire:submit.prevent="save" class="space-y-5">

                <div>
                    <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Label <span class="text-red-500">*</span></label>
                    <input
                        id="label"
                        type="text"
                        wire:model="label"
                        placeholder="e.g. web-01"
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tags</label>
                    @php $initialTags = array_values(array_filter(array_map('trim', explode(',', $tags)))) @endphp
                    <div
                        x-data="{
                            tags: @js($initialTags),
                            input: '',
                            add() {
                                const t = this.input.trim().toLowerCase().replace(/[^a-z0-9_-]/g, '');
                                if (t && !this.tags.includes(t)) {
                                    this.tags.push(t);
                                    $wire.set('tags', this.tags.join(', '));
                                }
                                this.input = '';
                            },
                            remove(tag) {
                                this.tags = this.tags.filter(t => t !== tag);
                                $wire.set('tags', this.tags.join(', '));
                            }
                        }"
                        class="flex flex-wrap items-center gap-1.5 min-h-[2.5rem] w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent"
                    >
                        <template x-for="tag in tags" :key="tag">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700">
                                <span x-text="tag"></span>
                                <button type="button" @click="remove(tag)" class="text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-200 focus:outline-none leading-none">&times;</button>
                            </span>
                        </template>
                        <input
                            type="text"
                            x-model="input"
                            @keydown.enter.prevent="add()"
                            @keydown.comma.prevent="add()"
                            @blur="if(input.trim()) add()"
                            placeholder="es. prod, staging…"
                            class="flex-1 min-w-[8rem] text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 bg-transparent focus:outline-none"
                        >
                    </div>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Premi Invio o virgola per aggiungere un tag.</p>
                    @error('tags')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-1">
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 transition-colors"
                    >
                        <span wire:loading.remove wire:target="save">Create host</span>
                        <span wire:loading wire:target="save">Creating…</span>
                    </button>
                </div>

            </form>
        </div>
    @endunless

    @if($created)
        <div class="max-w-lg">
            <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 px-5 py-4 mb-6">
                <p class="text-sm font-medium text-green-800 dark:text-green-300">Host created successfully.</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">API Key</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Copy this key now — it will not be shown again. Set it as the <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">API_KEY</code> environment variable on your agent.</p>

                <div class="flex items-center gap-2">
                    <code id="api-key-value" class="flex-1 block bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm font-mono text-gray-800 dark:text-gray-200 break-all select-all">{{ $generatedKey }}</code>
                    <button
                        onclick="navigator.clipboard.writeText(document.getElementById('api-key-value').textContent.trim()); this.textContent = 'Copied!';"
                        class="shrink-0 text-xs font-medium bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg px-3 py-2 transition-colors"
                    >Copy</button>
                </div>
            </div>

            <div class="mt-6">
                <a href="/" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors">← Back to dashboard</a>
            </div>
        </div>
    @endif
</div>

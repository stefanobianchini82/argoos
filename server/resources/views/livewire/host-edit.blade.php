<div>
    <div class="mb-6">
        <a href="/hosts/{{ $this->host->id }}" class="text-sm text-gray-400 hover:text-gray-600 transition-colors">← {{ $this->host->label }}</a>
    </div>

    <h1 class="text-lg font-semibold text-gray-900 mb-6">Edit host</h1>

    <div class="bg-white rounded-xl border border-gray-200 p-6 max-w-lg">
        <form wire:submit.prevent="save" class="space-y-5">

            <div>
                <label for="label" class="block text-sm font-medium text-gray-700 mb-1">Label <span class="text-red-500">*</span></label>
                <input
                    id="label"
                    type="text"
                    wire:model="label"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                @error('label')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="ip" class="block text-sm font-medium text-gray-700 mb-1">IP / Hostname</label>
                <input
                    id="ip"
                    type="text"
                    wire:model="ip"
                    placeholder="e.g. 192.168.1.10"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                @error('ip')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea
                    id="description"
                    wire:model="description"
                    rows="3"
                    placeholder="Optional notes about this host"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                ></textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
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
                <a href="/hosts/{{ $this->host->id }}" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">Cancel</a>
            </div>

        </form>
    </div>
</div>

<div>
    <div class="mb-8">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Settings</h1>
        <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">Global notification and integration settings</p>
    </div>

    <form wire:submit="save" class="max-w-lg space-y-6">

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Notification Channels</h2>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Alert Email</label>
                <input type="email"
                       wire:model="alertEmail"
                       placeholder="you@example.com"
                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Recipient for all email alert notifications. Leave blank to disable email alerts.</p>
                @error('alertEmail') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Telegram Chat ID</label>
                <input type="text"
                       wire:model="telegramChatId"
                       placeholder="e.g. -100123456789"
                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Chat ID for Telegram notifications. Requires <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">TELEGRAM_BOT_TOKEN</code> in your environment. Leave blank to disable Telegram alerts.</p>
                @error('telegramChatId') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Slack Incoming Webhook URL</label>
                <input type="url"
                       wire:model="slackWebhookUrl"
                       placeholder="https://hooks.slack.com/services/…"
                       class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Webhook URL for Slack notifications. Leave blank to disable Slack alerts.</p>
                @error('slackWebhookUrl') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Host Offline Notifications</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Choose which channels fire when a host stops reporting metrics.</p>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">Email</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Send an email when a host goes offline</p>
                </div>
                <button type="button"
                        wire:click="$toggle('hostOfflineEmailEnabled')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $hostOfflineEmailEnabled ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600' }}">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform {{ $hostOfflineEmailEnabled ? 'translate-x-4' : 'translate-x-1' }}"></span>
                </button>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">Telegram</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Send a Telegram message when a host goes offline</p>
                </div>
                <button type="button"
                        wire:click="$toggle('hostOfflineTelegramEnabled')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $hostOfflineTelegramEnabled ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600' }}">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform {{ $hostOfflineTelegramEnabled ? 'translate-x-4' : 'translate-x-1' }}"></span>
                </button>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">Slack</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Send a Slack message when a host goes offline</p>
                </div>
                <button type="button"
                        wire:click="$toggle('hostOfflineSlackEnabled')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $hostOfflineSlackEnabled ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600' }}">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform {{ $hostOfflineSlackEnabled ? 'translate-x-4' : 'translate-x-1' }}"></span>
                </button>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 py-2 transition-colors"
                    wire:loading.attr="disabled">
                <span wire:loading.remove>Save settings</span>
                <span wire:loading>Saving…</span>
            </button>

            @if($saved)
                <span class="text-sm text-green-600 dark:text-green-400 font-medium">Settings saved.</span>
            @endif
        </div>

    </form>
</div>

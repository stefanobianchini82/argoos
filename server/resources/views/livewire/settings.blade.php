<div>
    <div class="mb-8">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Settings</h1>
        <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">Global notification and integration settings</p>
    </div>

    <form wire:submit="save" class="space-y-6">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

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
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Telegram Chat ID</label>
                    @if($telegramBotConfigured)
                        <span class="text-xs font-medium text-green-600 dark:text-green-400">Bot configured ✓</span>
                    @else
                        <span class="text-xs font-medium text-amber-500 dark:text-amber-400">Bot token missing — set <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">TELEGRAM_BOT_TOKEN</code></span>
                    @endif
                </div>
                <div class="flex gap-2">
                    <input type="text"
                           wire:model="telegramChatId"
                           placeholder="e.g. -100123456789"
                           class="flex-1 text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <button type="button"
                            wire:click="testTelegramNotification"
                            wire:loading.attr="disabled"
                            wire:target="testTelegramNotification"
                            @disabled(!$telegramBotConfigured || empty($telegramChatId))
                            class="text-sm font-medium px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <span wire:loading.remove wire:target="testTelegramNotification">Test</span>
                        <span wire:loading wire:target="testTelegramNotification">…</span>
                    </button>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Chat ID for Telegram notifications. Leave blank to disable Telegram alerts.</p>
                @if($telegramTestStatus === 'success')
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $telegramTestMessage }}</p>
                @elseif($telegramTestStatus === 'error')
                    <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $telegramTestMessage }}</p>
                @endif
                @error('telegramChatId') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Slack Incoming Webhook URL</label>
                <div class="flex gap-2">
                    <input type="url"
                           wire:model="slackWebhookUrl"
                           placeholder="https://hooks.slack.com/services/…"
                           class="flex-1 text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <button type="button"
                            wire:click="testSlackNotification"
                            wire:loading.attr="disabled"
                            wire:target="testSlackNotification"
                            @disabled(empty($slackWebhookUrl))
                            class="text-sm font-medium px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <span wire:loading.remove wire:target="testSlackNotification">Test</span>
                        <span wire:loading wire:target="testSlackNotification">…</span>
                    </button>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Webhook URL for Slack notifications. Leave blank to disable Slack alerts.</p>
                @if($slackTestStatus === 'success')
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $slackTestMessage }}</p>
                @elseif($slackTestStatus === 'error')
                    <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $slackTestMessage }}</p>
                @endif
                @error('slackWebhookUrl') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Host Offline Notifications</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Choose which channels fire when a host stops reporting metrics.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Offline threshold (minutes)</label>
                    <input type="number"
                           wire:model="hostOfflineOfflineMinutes"
                           min="1" max="1440"
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Minutes without metrics before a host is considered offline.</p>
                    @error('hostOfflineOfflineMinutes') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Re-notify interval (minutes)</label>
                    <input type="number"
                           wire:model="hostOfflineRenotifyMinutes"
                           min="1" max="1440"
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Minutes between repeated offline notifications for the same host.</p>
                    @error('hostOfflineRenotifyMinutes') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
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

        </div>{{-- /grid --}}

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

    {{-- Import / Export --}}
    <div class="mt-8 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">
        <div>
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Import / Export</h2>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                Export all hosts, alert rules and settings to a JSON backup file. Import a previously exported file to restore or migrate configuration.
                <span class="text-amber-500 dark:text-amber-400 font-medium">The export file contains API keys — keep it secure.</span>
            </p>
        </div>

        {{-- Export --}}
        <div class="flex items-center gap-4">
            <button type="button"
                    wire:click="export"
                    wire:loading.attr="disabled"
                    wire:target="export"
                    class="text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-40">
                <span wire:loading.remove wire:target="export">Export configuration</span>
                <span wire:loading wire:target="export">Exporting…</span>
            </button>
            <p class="text-xs text-gray-400 dark:text-gray-500">Downloads a <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">argoos-config-*.json</code> file.</p>
        </div>

        {{-- Import --}}
        <div class="space-y-3">
            <div class="flex items-start gap-3">
                <div class="flex-1">
                    <input type="file"
                           wire:model="importFile"
                           accept=".json"
                           class="block w-full text-sm text-gray-600 dark:text-gray-400
                                  file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-gray-300 dark:file:border-gray-600
                                  file:text-sm file:font-medium file:bg-white dark:file:bg-gray-800 file:text-gray-700 dark:file:text-gray-300
                                  hover:file:bg-gray-50 dark:hover:file:bg-gray-700 file:transition-colors file:cursor-pointer">
                    @error('importFile') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="button"
                        wire:click="import"
                        wire:loading.attr="disabled"
                        wire:target="import"
                        @disabled(! $importFile)
                        class="text-sm font-medium px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed whitespace-nowrap">
                    <span wire:loading.remove wire:target="import">Import</span>
                    <span wire:loading wire:target="import">Importing…</span>
                </button>
            </div>

            @if($importError)
                <p class="text-sm text-red-600 dark:text-red-400 font-medium">{{ $importError }}</p>
            @endif

            @if($importResult)
                <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300 space-y-0.5">
                    <p class="font-semibold">Import complete.</p>
                    <p>{{ $importResult['new_hosts'] }} new host(s) created, {{ $importResult['updated_hosts'] }} host(s) updated, {{ $importResult['rules'] }} alert rule(s) imported, {{ $importResult['checks'] ?? 0 }} HTTP check(s) imported.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50 dark:bg-gray-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Argoos' }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-gray-900 dark:bg-gray-950 dark:text-gray-100">

    <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center gap-2">
            <a href="/" class="flex items-center">
                <img src="/images/argoos_logo_no_payoff.svg"      alt="Argoos" class="h-9 w-auto dark:hidden">
                <img src="/images/argoos_logo_no_payoff_dark.svg" alt="Argoos" class="h-9 w-auto hidden dark:block">
                <span class="font-semibold text-sm tracking-tight pl-2 dark:text-gray-300">| Self-Hosted Monitoring</span>
            </a>
            <nav class="ml-auto flex items-center gap-4">
                <a href="{{ route('settings') }}"
                   class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                    Settings
                </a>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    @livewireScriptConfig
</body>
</html>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Argoos' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-gray-900">

    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center gap-2">
            <a href="/" class="flex items-center gap-2 text-gray-900 hover:text-gray-700 transition-colors">
                <svg class="w-5 h-5 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="9" opacity=".3"/>
                    <circle cx="4" cy="12" r="1.5"/><circle cx="20" cy="12" r="1.5"/>
                    <circle cx="12" cy="4" r="1.5"/><circle cx="12" cy="20" r="1.5"/>
                    <circle cx="6.3" cy="6.3" r="1.5"/><circle cx="17.7" cy="17.7" r="1.5"/>
                    <circle cx="17.7" cy="6.3" r="1.5"/><circle cx="6.3" cy="17.7" r="1.5"/>
                </svg>
                <span class="font-semibold text-base tracking-tight">Argoos</span>
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    @livewireScriptConfig
</body>
</html>

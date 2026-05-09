<nav class="flex items-center gap-1.5 mb-6 text-sm">
    @foreach ($items as $index => $item)
        @if ($index > 0)
            <span class="text-gray-300 dark:text-gray-600">/</span>
        @endif

        @if (isset($item['url']))
            <a href="{{ $item['url'] }}"
               class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400 transition-colors">
                {{ $item['label'] }}
            </a>
        @else
            <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>

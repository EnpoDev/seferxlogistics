@props([])

<thead {{ $attributes->merge(['class' => 'bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800']) }}>
    {{ $slot }}
</thead>

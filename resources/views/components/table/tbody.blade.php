@props([])

<tbody {{ $attributes->merge(['class' => 'divide-y divide-gray-200 dark:divide-gray-800']) }}>
    {{ $slot }}
</tbody>

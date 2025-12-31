@props([
    'date',
    'format' => 'medium',
    'relative' => false,
    'showTime' => true,
])

@php
    use Carbon\Carbon;

    if (!$date) {
        $formatted = '-';
    } else {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        if ($relative) {
            $formatted = $carbon->diffForHumans();
        } else {
            $formats = [
                'short' => $showTime ? 'd.m.Y H:i' : 'd.m.Y',
                'medium' => $showTime ? 'd M Y, H:i' : 'd M Y',
                'long' => $showTime ? 'd F Y, H:i' : 'd F Y',
                'time' => 'H:i',
                'date' => 'd.m.Y',
            ];

            $formatString = $formats[$format] ?? $formats['medium'];
            $formatted = $carbon->translatedFormat($formatString);
        }
    }
@endphp

<span {{ $attributes->merge(['class' => 'text-gray-600 dark:text-gray-400']) }}>
    {{ $formatted }}
</span>

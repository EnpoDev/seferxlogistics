@props([
    'courier',
    'size' => 'md',
    'showStatus' => true,
    'showName' => true,
    'showPhone' => false,
    'linkTo' => null,
])

@php
    $sizes = [
        'sm' => [
            'avatar' => 'w-8 h-8 text-xs',
            'status' => 'w-2 h-2',
            'name' => 'text-sm',
        ],
        'md' => [
            'avatar' => 'w-10 h-10 text-sm',
            'status' => 'w-2.5 h-2.5',
            'name' => 'text-sm',
        ],
        'lg' => [
            'avatar' => 'w-12 h-12 text-base',
            'status' => 'w-3 h-3',
            'name' => 'text-base',
        ],
        'xl' => [
            'avatar' => 'w-16 h-16 text-lg',
            'status' => 'w-3.5 h-3.5',
            'name' => 'text-lg',
        ],
    ];

    $statusColors = [
        'available' => 'bg-green-500',
        'busy' => 'bg-orange-500',
        'offline' => 'bg-gray-400',
        'on_break' => 'bg-yellow-500',
    ];

    $sizeSet = $sizes[$size] ?? $sizes['md'];
    $statusColor = $statusColors[$courier->status ?? 'offline'] ?? $statusColors['offline'];

    // Kurye adinin bas harfleri
    $initials = collect(explode(' ', $courier->name ?? 'K'))
        ->map(fn($word) => mb_substr($word, 0, 1))
        ->take(2)
        ->join('');
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    @if($linkTo)
        <a href="{{ $linkTo }}" class="relative flex-shrink-0">
    @else
        <div class="relative flex-shrink-0">
    @endif
        @if($courier->photo_path)
            <img
                src="{{ asset('storage/' . $courier->photo_path) }}"
                alt="{{ $courier->name }}"
                class="{{ $sizeSet['avatar'] }} rounded-full object-cover"
            >
        @else
            <div class="{{ $sizeSet['avatar'] }} rounded-full bg-black dark:bg-white flex items-center justify-center">
                <span class="font-medium text-white dark:text-black">{{ $initials }}</span>
            </div>
        @endif

        @if($showStatus)
            <span class="absolute bottom-0 right-0 {{ $sizeSet['status'] }} {{ $statusColor }} rounded-full ring-2 ring-white dark:ring-[#181818]"></span>
        @endif
    @if($linkTo)
        </a>
    @else
        </div>
    @endif

    @if($showName || $showPhone)
        <div class="min-w-0">
            @if($showName)
                @if($linkTo)
                    <a href="{{ $linkTo }}" class="{{ $sizeSet['name'] }} font-medium text-black dark:text-white hover:underline truncate block">
                        {{ $courier->name }}
                    </a>
                @else
                    <p class="{{ $sizeSet['name'] }} font-medium text-black dark:text-white truncate">
                        {{ $courier->name }}
                    </p>
                @endif
            @endif

            @if($showPhone && $courier->phone)
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    @phone($courier->phone)
                </p>
            @endif
        </div>
    @endif
</div>

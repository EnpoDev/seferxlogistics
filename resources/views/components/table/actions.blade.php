@props([
    'item' => null,
    'editRoute' => null,
    'deleteRoute' => null,
    'viewRoute' => null,
    'showEdit' => true,
    'showDelete' => true,
    'showView' => false,
])

<div class="flex items-center justify-end gap-1">
    @isset($prepend)
        {{ $prepend }}
    @endisset

    @if($showView && $viewRoute && $item)
        <a
            href="{{ route($viewRoute, $item) }}"
            class="p-2 text-gray-500 hover:text-black dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
            title="Goruntule"
        >
            <x-ui.icon name="eye" class="w-4 h-4" />
        </a>
    @endif

    @if($showEdit && $editRoute && $item)
        <a
            href="{{ route($editRoute, $item) }}"
            class="p-2 text-gray-500 hover:text-black dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
            title="Duzenle"
        >
            <x-ui.icon name="edit" class="w-4 h-4" />
        </a>
    @endif

    @if($showDelete && $deleteRoute && $item)
        <button
            type="button"
            @click="showConfirmDialog({
                title: 'Silmek istediginize emin misiniz?',
                message: 'Bu islem geri alinamaz.',
                confirmText: 'Sil',
                type: 'danger',
                onConfirm: async () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route($deleteRoute, $item) }}';
                    form.innerHTML = '@csrf @method('DELETE')';
                    document.body.appendChild(form);
                    form.submit();
                }
            })"
            class="p-2 text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
            title="Sil"
        >
            <x-ui.icon name="trash" class="w-4 h-4" />
        </button>
    @endif

    @isset($append)
        {{ $append }}
    @endisset

    {{ $slot }}
</div>

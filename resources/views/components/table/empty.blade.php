@props([
    'colspan' => 1,
    'message' => 'Veri bulunamadi',
    'icon' => 'package',
])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-12">
        <div class="flex flex-col items-center justify-center text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-3">
                <x-ui.icon :name="$icon" class="w-6 h-6 text-gray-400 dark:text-gray-500" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>

            @isset($action)
                <div class="mt-4">
                    {{ $action }}
                </div>
            @endisset
        </div>
    </td>
</tr>

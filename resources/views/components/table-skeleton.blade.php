<!-- Table Skeleton Loader -->
<div class="animate-pulse">
    <table class="w-full">
        <thead class="border-b border-gray-200 dark:border-gray-800">
            <tr>
                @for($i = 0; $i < ($cols ?? 5); $i++)
                <th class="px-6 py-3 text-left">
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                </th>
                @endfor
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            @for($row = 0; $row < ($rows ?? 5); $row++)
            <tr>
                @for($col = 0; $col < ($cols ?? 5); $col++)
                <td class="px-6 py-4">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded {{ $col === 0 ? 'w-32' : 'w-24' }}"></div>
                </td>
                @endfor
            </tr>
            @endfor
        </tbody>
    </table>
</div>


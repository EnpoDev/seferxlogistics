<x-ui.card class="overflow-hidden">
    <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h3 class="font-semibold text-black dark:text-white">Son Aramalar</h3>
        <a href="{{ route('isletmem.aramalar') }}" class="text-sm text-blue-600 hover:underline">
            Tümünü Gör
        </a>
    </div>

    <div id="recent-calls-list" class="divide-y divide-gray-200 dark:divide-gray-800 max-h-96 overflow-y-auto">
        {{-- Will be populated via JavaScript --}}
        <div class="p-8 text-center text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2">Yükleniyor...</p>
        </div>
    </div>
</x-ui.card>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadRecentCalls();

    // Refresh every 30 seconds as fallback
    setInterval(loadRecentCalls, 30000);
});

function loadRecentCalls() {
    fetch('/api/isletmem/recent-calls')
        .then(res => res.json())
        .then(calls => {
            renderCalls(calls);
        })
        .catch(err => {
            console.error('Failed to load recent calls:', err);
        });
}

function renderCalls(calls) {
    const container = document.getElementById('recent-calls-list');

    if (calls.length === 0) {
        container.innerHTML = `
            <div class="p-8 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <p class="mt-2">Henüz arama kaydı yok</p>
            </div>
        `;
        return;
    }

    container.innerHTML = calls.map(call => `
        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        ${call.has_customer
                            ? `<div class="h-10 w-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                </svg>
                            </div>`
                            : `<div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                </svg>
                            </div>`
                        }
                    </div>
                    <div>
                        <p class="font-medium text-black dark:text-white">${call.caller_name}</p>
                        <p class="text-sm text-gray-500">${call.phone}</p>
                    </div>
                </div>
                <div class="text-right">
                    ${call.customer_type ? `<span class="text-xs px-2 py-1 rounded-full ${
                        call.customer_type === 'VIP' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                        call.customer_type === 'Yeni' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400'
                    }">${call.customer_type}</span>` : ''}
                    <p class="text-xs text-gray-400 mt-1">${call.time}</p>
                </div>
            </div>
        </div>
    `).join('');
}

// Add new call to the top of the list when broadcast event is received
function addCallToList(callData) {
    const container = document.getElementById('recent-calls-list');
    const emptyState = container.querySelector('p');

    // Remove empty state if exists
    if (emptyState && emptyState.textContent.includes('Henüz arama kaydı yok')) {
        container.innerHTML = '';
    }

    const callHtml = `
        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors animate-fadeIn">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        ${callData.customer
                            ? `<div class="h-10 w-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                </svg>
                            </div>`
                            : `<div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                </svg>
                            </div>`
                        }
                    </div>
                    <div>
                        <p class="font-medium text-black dark:text-white">${callData.caller_name}</p>
                        <p class="text-sm text-gray-500">${callData.phone}</p>
                    </div>
                </div>
                <div class="text-right">
                    ${callData.customer?.type ? `<span class="text-xs px-2 py-1 rounded-full ${
                        callData.customer.type === 'VIP' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                        callData.customer.type === 'Yeni' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400'
                    }">${callData.customer.type}</span>` : ''}
                    <p class="text-xs text-gray-400 mt-1">${callData.time_ago}</p>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('afterbegin', callHtml);

    // Keep only the most recent 10 calls
    const calls = container.children;
    if (calls.length > 10) {
        container.removeChild(calls[calls.length - 1]);
    }
}

// Listen for WebSocket events (if Echo is available)
if (typeof Echo !== 'undefined' && typeof activeBranchId !== 'undefined') {
    Echo.channel(`branch.${activeBranchId}.calls`)
        .listen('.incoming.call', (e) => {
            addCallToList(e);
            showNotification(e);
        });
}

function showNotification(call) {
    // Browser notification
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('Gelen Arama', {
            body: `${call.caller_name} - ${call.phone}`,
            icon: '/favicon.ico',
            tag: 'incoming-call'
        });
    }

    // Request permission if not granted
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}
</script>
@endpush

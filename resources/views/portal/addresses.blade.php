<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adreslerim - Müşteri Portalı</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center gap-4">
            <a href="{{ route('portal.dashboard') }}" class="p-2 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h1 class="font-semibold text-gray-900">Adreslerim</h1>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6">
        @if(count($addresses) > 0)
        <div class="space-y-4">
            @foreach($addresses as $index => $address)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-medium text-gray-900">{{ $address['title'] ?? 'Adres ' . ($index + 1) }}</h3>
                            @if($address['is_default'] ?? false)
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Varsayılan</span>
                            @endif
                        </div>
                        <p class="text-gray-600">{{ $address['address'] ?? $address }}</p>
                        @if(isset($address['district']))
                            <p class="text-sm text-gray-500">{{ $address['district'] }}, {{ $address['city'] ?? '' }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <p class="text-gray-500 mb-2">Kayıtlı adresiniz bulunmuyor.</p>
            <p class="text-sm text-gray-400">Sipariş verdiğinizde adresleriniz otomatik kaydedilir.</p>
        </div>
        @endif
    </main>
</body>
</html>

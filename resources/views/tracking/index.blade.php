<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Takibi - SeferX Lojistik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-500 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Sipariş Takibi</h1>
            <p class="text-gray-600 mt-2">Siparişinizin durumunu anlık takip edin</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-6">
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('tracking.search') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="query" class="block text-sm font-medium text-gray-700 mb-2">
                        Takip Kodu veya Sipariş Numarası
                    </label>
                    <input
                        type="text"
                        id="query"
                        name="query"
                        placeholder="Örn: ABC123DE veya ORD-000001"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors"
                        required
                    >
                </div>
                <button
                    type="submit"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-4 rounded-xl transition-colors"
                >
                    Siparişi Takip Et
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-gray-100">
                <p class="text-xs text-gray-500 text-center">
                    Takip kodunuzu sipariş onay mesajınızda bulabilirsiniz.
                </p>
            </div>
        </div>

        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                Powered by <span class="font-semibold text-orange-600">SeferX Lojistik</span>
            </p>
        </div>
    </div>
</body>
</html>

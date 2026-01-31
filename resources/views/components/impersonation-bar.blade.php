@if(session('impersonating_from'))
<div class="fixed top-0 left-0 right-0 z-[100] bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 py-2">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="text-sm font-medium">
                    <span class="font-bold">{{ session('impersonated_branch_name') }}</span> olarak görüntülüyorsunuz
                </span>
                <span class="text-xs opacity-75">
                    ({{ session('impersonating_from_name') }} hesabından)
                </span>
            </div>
            <form action="{{ route('bayi.geri-don') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="flex items-center gap-2 px-4 py-1.5 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"/>
                    </svg>
                    Bayi Paneline Geri Dön
                </button>
            </form>
        </div>
    </div>
</div>
{{-- Sayfa içeriğinin üst bar'ın altında görünmesi için padding --}}
<div class="h-12"></div>
@endif

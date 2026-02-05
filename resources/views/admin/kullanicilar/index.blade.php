@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn" x-data="{
    showCreateModal: false,
    showEditModal: false,
    editingUser: null,
    activeTab: '{{ $activeTab }}',
    expandedBayi: null
}">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Kullanicilar</h1>
            <p class="text-gray-600 dark:text-gray-400">Tum sistem kullanicilarini yonetin</p>
        </div>
        <button @click="showCreateModal = true" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Yeni Kullanici</span>
        </button>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <p class="text-red-700 dark:text-red-400">{{ session('error') }}</p>
    </div>
    @endif

    <!-- Search -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4 mb-6">
        <form action="{{ route('admin.kullanicilar.index') }}" method="GET" class="flex flex-wrap gap-4">
            <input type="hidden" name="tab" :value="activeTab">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ $search }}" placeholder="Kullanici ara..."
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                Ara
            </button>
        </form>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-800">
        <nav class="flex space-x-4">
            <button @click="activeTab = 'adminler'"
                :class="activeTab === 'adminler' ? 'border-black dark:border-white text-black dark:text-white' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-3 border-b-2 font-medium transition-colors">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Adminler</span>
                    <span class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs px-2 py-0.5 rounded-full">{{ $adminler->count() }}</span>
                </div>
            </button>
            <button @click="activeTab = 'bayiler'"
                :class="activeTab === 'bayiler' ? 'border-black dark:border-white text-black dark:text-white' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-3 border-b-2 font-medium transition-colors">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Bayiler</span>
                    <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs px-2 py-0.5 rounded-full">{{ $bayiler->count() }}</span>
                </div>
            </button>
        </nav>
    </div>

    <!-- Adminler Table -->
    <div x-show="activeTab === 'adminler'" x-transition class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-black">
            <h3 class="font-semibold text-black dark:text-white">Sistem Yoneticileri</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Super Admin ve Admin yetkisine sahip kullanicilar</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-black">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kullanici</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Roller</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kayit Tarihi</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Islemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($adminler as $kullanici)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                    <span class="text-red-600 dark:text-red-400 font-medium">{{ strtoupper(substr($kullanici->name, 0, 2)) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-black dark:text-white">{{ $kullanici->name }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $kullanici->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($kullanici->roles ?? [] as $role)
                                @php
                                    $roleColors = [
                                        'super_admin' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'admin' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded text-xs {{ $roleColors[$role] ?? 'bg-gray-100 text-gray-800' }}">{{ ucfirst(str_replace('_', ' ', $role)) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $kullanici->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <button @click="editingUser = {{ $kullanici->toJson() }}; showEditModal = true" class="p-2 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                @if($kullanici->id !== auth()->id())
                                <form action="{{ route('admin.kullanicilar.destroy', $kullanici) }}" method="POST" class="inline" onsubmit="return confirm('Bu kullaniciyi silmek istediginize emin misiniz?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">Admin bulunamadi</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bayiler Table -->
    <div x-show="activeTab === 'bayiler'" x-transition class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-black">
            <h3 class="font-semibold text-black dark:text-white">Bayiler ve Isletmeleri</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Bayi satirina tiklayarak isletmelerini gorebilirsiniz</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-black">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-8"></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bayi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Isletme Sayisi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kayit Tarihi</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Islemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($bayiler as $bayi)
                    <!-- Bayi Row -->
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer" @click="expandedBayi = expandedBayi === {{ $bayi->id }} ? null : {{ $bayi->id }}">
                        <td class="px-6 py-4">
                            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="expandedBayi === {{ $bayi->id }} ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 dark:text-blue-400 font-medium">{{ strtoupper(substr($bayi->name, 0, 2)) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-black dark:text-white">{{ $bayi->name }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $bayi->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $bayi->isletmeler->count() > 0 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                                {{ $bayi->isletmeler->count() }} Isletme
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $bayi->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-right" @click.stop>
                            <div class="flex items-center justify-end space-x-2">
                                <button @click="editingUser = {{ $bayi->toJson() }}; showEditModal = true" class="p-2 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <form action="{{ route('admin.kullanicilar.destroy', $bayi) }}" method="POST" class="inline" onsubmit="return confirm('Bu bayiyi silmek istediginize emin misiniz?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <!-- Isletmeler (Expandable) -->
                    <tr x-show="expandedBayi === {{ $bayi->id }}" x-collapse>
                        <td colspan="5" class="px-0 py-0">
                            <div class="bg-gray-50 dark:bg-black/50 px-6 py-4">
                                @if($bayi->isletmeler->count() > 0)
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-3">{{ $bayi->name }} - Isletmeleri</p>
                                <div class="space-y-2">
                                    @foreach($bayi->isletmeler as $isletme)
                                    <div class="flex items-center justify-between bg-white dark:bg-[#1a1a1a] rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                                <span class="text-green-600 dark:text-green-400 font-medium text-sm">{{ strtoupper(substr($isletme->name, 0, 2)) }}</span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-black dark:text-white text-sm">{{ $isletme->name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $isletme->email }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Isletme</span>
                                            <button @click="editingUser = {{ $isletme->toJson() }}; showEditModal = true" class="p-1.5 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white rounded hover:bg-gray-100 dark:hover:bg-gray-800">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">Bu bayiye ait isletme bulunmuyor.</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">Bayi bulunamadi</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Modal -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
            <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Kullanici</h3>
                <form action="{{ route('admin.kullanicilar.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ad Soyad</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-posta</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sifre</label>
                        <input type="password" name="password" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sifre Tekrar</label>
                        <input type="password" name="password_confirmation" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roller</label>
                        <div class="space-y-2">
                            <p class="text-xs text-gray-500 mb-2">Yonetim</p>
                            @foreach(['super_admin' => 'Super Admin', 'admin' => 'Admin'] as $value => $label)
                            <label class="flex items-center">
                                <input type="checkbox" name="roles[]" value="{{ $value }}" class="rounded border-gray-300 dark:border-gray-600 text-black dark:text-white">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                            @endforeach
                            <p class="text-xs text-gray-500 mb-2 mt-3">Is Ortaklari</p>
                            @foreach(['bayi' => 'Bayi', 'isletme' => 'Isletme', 'kurye' => 'Kurye'] as $value => $label)
                            <label class="flex items-center">
                                <input type="checkbox" name="roles[]" value="{{ $value }}" class="rounded border-gray-300 dark:border-gray-600 text-black dark:text-white">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showCreateModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">Iptal</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200">Olustur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showEditModal = false"></div>
            <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Kullanici Duzenle</h3>
                <form :action="'/admin/kullanicilar/' + (editingUser?.id || '')" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ad Soyad</label>
                        <input type="text" name="name" x-model="editingUser.name" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-posta</label>
                        <input type="email" name="email" x-model="editingUser.email" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Yeni Sifre</label>
                        <input type="password" name="password" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        <p class="text-xs text-gray-500 mt-1">Bos birakirsaniz degismez</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sifre Tekrar</label>
                        <input type="password" name="password_confirmation" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roller</label>
                        <div class="space-y-2">
                            <p class="text-xs text-gray-500 mb-2">Yonetim</p>
                            @foreach(['super_admin' => 'Super Admin', 'admin' => 'Admin'] as $value => $label)
                            <label class="flex items-center">
                                <input type="checkbox" name="roles[]" value="{{ $value }}" :checked="editingUser?.roles?.includes('{{ $value }}')" class="rounded border-gray-300 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                            @endforeach
                            <p class="text-xs text-gray-500 mb-2 mt-3">Is Ortaklari</p>
                            @foreach(['bayi' => 'Bayi', 'isletme' => 'Isletme', 'kurye' => 'Kurye'] as $value => $label)
                            <label class="flex items-center">
                                <input type="checkbox" name="roles[]" value="{{ $value }}" :checked="editingUser?.roles?.includes('{{ $value }}')" class="rounded border-gray-300 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showEditModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">Iptal</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

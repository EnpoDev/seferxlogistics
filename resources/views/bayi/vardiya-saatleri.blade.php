<x-bayi-layout>
    <x-slot name="title">Vardiya Saatleri - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Haftalık Mesai Şablonu</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Kurye vardiya saatlerini düzenleyin</p>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <button onclick="openModal('defaultShiftModal')" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors font-medium text-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Varsayılan Düzenle
                </button>
                <button onclick="openModal('bulkUpdateModal')" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium text-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Toplu Güncelle
                </button>
                
                <form action="{{ route('bayi.vardiya-saatleri') }}" method="GET" class="relative w-full sm:w-auto" id="searchForm">
                    <div class="absolute left-0 flex items-center pointer-events-none" style="padding-left: 10px; top: 34%; transform: translateY(-50%);">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                        placeholder="Kurye ara..." 
                        class="pr-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white focus:border-transparent w-full sm:w-64 transition-all"
                        style="padding-left: 35px;"
                    >
                </form>
            </div>
        </div>

        <!-- Vardiya Tablosu -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <form id="bulkForm" action="{{ route('bayi.vardiya-saatleri.toplu-guncelle') }}" method="POST">
                        @csrf
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-800">
                                    <th class="py-3 px-4 w-10">
                                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-black focus:ring-black">
                                    </th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Kurye</th>
                                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Pzt</th>
                                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Sal</th>
                                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Çar</th>
                                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Per</th>
                                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Cum</th>
                                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Cmt</th>
                                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Paz</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($couriers as $courier)
                                <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <input type="checkbox" name="courier_ids[]" value="{{ $courier->id }}" class="courier-checkbox rounded border-gray-300 text-black focus:ring-black">
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold overflow-hidden">
                                                @if($courier->photo_path)
                                                    <img src="{{ Storage::url($courier->photo_path) }}" alt="{{ $courier->name }}" class="w-full h-full object-cover">
                                                @else
                                                    {{ substr($courier->name, 0, 2) }}
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-black dark:text-white">{{ $courier->name }}</p>
                                                <p class="text-xs text-gray-500">{{ $courier->vehicle_plate ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    @for($i = 0; $i < 7; $i++)
                                    <td class="py-3 px-2 text-center">
                                        <div class="relative group">
                                            @php
                                                $shift = $courier->shifts[$i] ?? '';
                                                $parts = explode('-', $shift);
                                                $start = trim($parts[0] ?? '');
                                                $end = trim($parts[1] ?? '');
                                            @endphp
                                            <div class="flex items-center gap-1 justify-center">
                                                <input type="text" 
                                                    value="{{ $start }}" 
                                                    placeholder="09:00"
                                                    data-day="{{ $i }}"
                                                    data-type="start"
                                                    data-courier-id="{{ $courier->id }}"
                                                    class="shift-time-picker w-16 text-xs text-center bg-gray-50 dark:bg-gray-900 border border-transparent hover:border-gray-200 dark:hover:border-gray-700 focus:border-black dark:focus:border-white rounded focus:ring-0 transition-all placeholder-gray-400">
                                                <span class="text-gray-400">-</span>
                                                <input type="text" 
                                                    value="{{ $end }}" 
                                                    placeholder="18:00"
                                                    data-day="{{ $i }}"
                                                    data-type="end"
                                                    data-courier-id="{{ $courier->id }}"
                                                    class="shift-time-picker w-16 text-xs text-center bg-gray-50 dark:bg-gray-900 border border-transparent hover:border-gray-200 dark:hover:border-gray-700 focus:border-black dark:focus:border-white rounded focus:ring-0 transition-all placeholder-gray-400">
                                            </div>
                                            <!-- Hidden input to store full value for easier update logic if needed, but we update via JS -->
                                            
                                            <div id="loading-{{ $courier->id }}-{{ $i }}" class="hidden absolute right-0 top-1/2 -translate-y-1/2 translate-x-full pl-1">
                                                <div class="w-3 h-3 border-2 border-gray-300 border-t-black dark:border-t-white rounded-full animate-spin"></div>
                                            </div>
                                            <div id="success-{{ $courier->id }}-{{ $i }}" class="hidden absolute right-0 top-1/2 -translate-y-1/2 translate-x-full pl-1 text-green-500">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </td>
                                    @endfor
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-12">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-16 h-16 text-gray-300 dark:text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <p class="text-lg font-medium text-gray-600 dark:text-gray-400">Kurye bulunamadı</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </form>
                </div>
                
                <div class="mt-4">
                    {{ $couriers->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Varsayılan Düzenle Modal -->
    <div id="defaultShiftModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#181818] rounded-2xl w-full max-w-2xl overflow-hidden shadow-xl transform transition-all">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                <h3 class="text-lg font-bold text-black dark:text-white">Varsayılan Vardiya Ayarları</h3>
                <button onclick="closeModal('defaultShiftModal')" class="text-gray-500 hover:text-black dark:hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form action="{{ route('bayi.vardiya-saatleri.varsayilan') }}" method="POST" class="p-6 space-y-6">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @php
                        $days = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
                        $defaults = $businessInfo->default_shifts ?? [];
                    @endphp
                    @foreach($days as $index => $day)
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $day }}</label>
                        @php
                            $shift = $defaults[$index] ?? '';
                            $parts = explode('-', $shift);
                            $start = trim($parts[0] ?? '');
                            $end = trim($parts[1] ?? '');
                        @endphp
                        <div class="flex items-center gap-2">
                            <input type="text" class="default-time-picker w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-black dark:focus:ring-white focus:border-transparent placeholder-gray-400" 
                                placeholder="Başlangıç" value="{{ $start }}" data-index="{{ $index }}" data-type="start">
                            <span class="text-gray-400">-</span>
                            <input type="text" class="default-time-picker w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-black dark:focus:ring-white focus:border-transparent placeholder-gray-400" 
                                placeholder="Bitiş" value="{{ $end }}" data-index="{{ $index }}" data-type="end">
                        </div>
                        <input type="hidden" name="default_shifts[{{ $index }}]" id="default_shift_{{ $index }}" value="{{ $shift }}">
                    </div>
                    @endforeach
                </div>

                <div class="flex items-center space-x-3 pt-4 border-t border-gray-200 dark:border-gray-800">
                    <input type="checkbox" name="auto_assign_shifts" id="auto_assign_shifts" {{ ($businessInfo->auto_assign_shifts ?? false) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-black focus:ring-black h-5 w-5">
                    <label for="auto_assign_shifts" class="text-sm font-medium text-black dark:text-white">
                        Yeni oluşturulan kuryelere bu vardiya planını otomatik ata
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeModal('defaultShiftModal')" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors font-medium">İptal</button>
                    <button type="submit" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toplu Güncelle Modal -->
    <div id="bulkUpdateModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#181818] rounded-2xl w-full max-w-2xl overflow-hidden shadow-xl transform transition-all">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                <h3 class="text-lg font-bold text-black dark:text-white">Toplu Vardiya Güncelleme</h3>
                <button onclick="closeModal('bulkUpdateModal')" class="text-gray-500 hover:text-black dark:hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6 space-y-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 p-3 rounded-lg border border-blue-100 dark:border-blue-900/30">
                    <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Dikkat: Sadece doldurduğunuz günler güncellenecektir. Boş bıraktığınız günler kuryenin mevcut ayarında kalacaktır.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($days as $index => $day)
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $day }}</label>
                        <div class="flex items-center gap-2">
                            <input type="text" class="bulk-time-picker w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-black dark:focus:ring-white focus:border-transparent placeholder-gray-400" 
                                placeholder="Başlangıç" data-index="{{ $index }}" data-type="start">
                            <span class="text-gray-400">-</span>
                            <input type="text" class="bulk-time-picker w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-black dark:focus:ring-white focus:border-transparent placeholder-gray-400" 
                                placeholder="Bitiş" data-index="{{ $index }}" data-type="end">
                        </div>
                        <input type="hidden" form="bulkForm" name="shifts[{{ $index }}]" id="bulk_shift_{{ $index }}">
                    </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
                    <button type="button" onclick="closeModal('bulkUpdateModal')" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors font-medium">İptal</button>
                    <button type="submit" form="bulkForm" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">Güncelle</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            // Debounce function to limit request frequency
            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            // Handle input changes
            searchInput.addEventListener('input', debounce(function(e) {
                this.form.submit();
            }, 500));
            
            // Maintain focus
            if (searchInput.value) {
                const val = searchInput.value;
                searchInput.focus();
                searchInput.value = '';
                searchInput.value = val;
            }
        }

        // Initialize Flatpickr
        document.addEventListener('DOMContentLoaded', function() {
            initFlatpickr();
            initBulkFlatpickr();
            initDefaultFlatpickr();
        });

        function initFlatpickr() {
            flatpickr(".shift-time-picker", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                onChange: function(selectedDates, dateStr, instance) {
                    const input = instance.element;
                    const container = input.parentElement;
                    const inputs = container.querySelectorAll('input');
                    const startInput = inputs[0];
                    const endInput = inputs[1];
                    
                    const start = startInput.value;
                    const end = endInput.value;
                    
                    if (start && end) {
                        const courierId = input.dataset.courierId;
                        const day = input.dataset.day;
                        const fullValue = `${start} - ${end}`;
                        updateShiftValue(courierId, day, fullValue, input);
                    }
                }
            });
        }

        function initBulkFlatpickr() {
            flatpickr(".bulk-time-picker", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                onChange: function(selectedDates, dateStr, instance) {
                    const input = instance.element;
                    const index = input.dataset.index;
                    const container = input.parentElement;
                    const inputs = container.querySelectorAll('input');
                    const start = inputs[0].value;
                    const end = inputs[1].value;
                    
                    const hiddenInput = document.getElementById(`bulk_shift_${index}`);
                    
                    if (start && end) {
                        hiddenInput.value = `${start} - ${end}`;
                    } else {
                        hiddenInput.value = '';
                    }
                }
            });
        }

        function initDefaultFlatpickr() {
            flatpickr(".default-time-picker", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                onChange: function(selectedDates, dateStr, instance) {
                    const input = instance.element;
                    const index = input.dataset.index;
                    const container = input.parentElement;
                    const inputs = container.querySelectorAll('input');
                    const start = inputs[0].value;
                    const end = inputs[1].value;
                    
                    const hiddenInput = document.getElementById(`default_shift_${index}`);
                    
                    if (start && end) {
                        hiddenInput.value = `${start} - ${end}`;
                    } else {
                        hiddenInput.value = '';
                    }
                }
            });
        }

        function updateShiftValue(courierId, day, value, inputElement) {
            const loading = document.getElementById(`loading-${courierId}-${day}`);
            const success = document.getElementById(`success-${courierId}-${day}`);
            
            if(loading) loading.classList.remove('hidden');
            if(success) success.classList.add('hidden');
            
            // Disable inputs in the row/cell
            // const inputs = inputElement.parentElement.querySelectorAll('input');
            // inputs.forEach(i => i.disabled = true);

            fetch(`/bayi/vardiya-saatleri/${courierId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    day: day,
                    hours: value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if(success) {
                        success.classList.remove('hidden');
                        setTimeout(() => {
                            success.classList.add('hidden');
                        }, 2000);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // alert('Bir hata oluştu.');
            })
            .finally(() => {
                if(loading) loading.classList.add('hidden');
                // inputs.forEach(i => i.disabled = false);
            });
        }

        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('fixed')) {
                event.target.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        // Checkbox logic
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.courier-checkbox');

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Form Submit Validation for Bulk Update
        document.getElementById('bulkForm').addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.courier-checkbox:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Lütfen en az bir kurye seçiniz.');
            }
        });
    </script>
</x-bayi-layout>

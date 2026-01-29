@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Vardiya Saatleri"
        subtitle="Kuryelerinizin haftalık çalışma programlarını yönetin"
    >
        <x-slot name="icon">
            <x-ui.icon name="clock" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <a href="{{ route('bayi.vardiya.analytics') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Analitik
            </a>
            <x-ui.button variant="secondary" onclick="showDefaultModal()" icon="settings">
                Varsayilan Duzenle
            </x-ui.button>
            <x-ui.button onclick="showBulkUpdateModal()" icon="edit">
                Toplu Guncelle
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Arama --}}
    <div class="mb-6">
        <form method="GET" action="{{ route('bayi.vardiya-saatleri') }}" class="max-w-md">
            <x-form.search-input
                name="search"
                :value="request('search')"
                placeholder="Kurye ara..."
                :autoSubmit="true"
            />
        </form>
    </div>

    {{-- Tablo --}}
    <x-ui.card class="overflow-hidden">
        <div class="overflow-x-auto">
            <x-table.table>
                <x-table.thead>
                    <x-table.tr :hoverable="false">
                        <x-table.th class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800">
                            <x-form.checkbox id="selectAll" />
                        </x-table.th>
                        <x-table.th class="sticky left-12 z-10 bg-gray-50 dark:bg-gray-800 whitespace-nowrap">Kurye</x-table.th>
                        <x-table.th class="whitespace-nowrap">Pazartesi</x-table.th>
                        <x-table.th class="whitespace-nowrap">Sali</x-table.th>
                        <x-table.th class="whitespace-nowrap">Carsamba</x-table.th>
                        <x-table.th class="whitespace-nowrap">Persembe</x-table.th>
                        <x-table.th class="whitespace-nowrap">Cuma</x-table.th>
                        <x-table.th class="whitespace-nowrap">Cumartesi</x-table.th>
                        <x-table.th class="whitespace-nowrap">Pazar</x-table.th>
                    </x-table.tr>
                </x-table.thead>

                <x-table.tbody>
                    @forelse ($couriers as $courier)
                    <x-table.tr>
                        {{-- Checkbox --}}
                        <x-table.td class="bg-white dark:bg-gray-900">
                            <x-form.checkbox class="courier-checkbox" value="{{ $courier->id }}" />
                        </x-table.td>

                        {{-- Kurye Bilgisi --}}
                        <x-table.td class="bg-white dark:bg-gray-900">
                            <div class="flex flex-col items-start justify-center gap-2">
                                <x-data.courier-avatar :courier="$courier" size="sm" :showStatus="false" :showPhone="false" />
                                <div class="flex items-center gap-2">
                                    <x-data.status-badge :status="$courier->status" entity="courier" size="xs" />
                                    @if($courier->hasTemplateShift())
                                        <x-ui.badge type="info" size="sm">Şablonlu</x-ui.badge>
                                    @endif
                                    <button onclick="applyTemplate({{ $courier->id }})"
                                            class="p-1 rounded-md text-xs border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700"
                                            title="Şablon Uygula">
                                        <x-ui.icon name="magic" class="w-4 h-4" />
                                    </button>
                                    <button onclick="deleteCourier({{ $courier->id }})"
                                            class="p-1 rounded-md text-xs text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950"
                                            title="Sil">
                                        <x-ui.icon name="trash" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </x-table.td>

                        {{-- Gunler --}}
                        @for ($day = 0; $day < 7; $day++)
                            @php
                                $shift = isset($courier->shifts[$day]) ? $courier->shifts[$day] : null;
                                $break = isset($courier->break_durations[$day]) ? $courier->break_durations[$day] : null;
                            @endphp
                            <x-table.td class="border-l dark:border-gray-700">
                                @if($shift)
                                <div onclick="editShift({{ $courier->id }}, {{ $day }}, '{{ $shift }}', {{ json_encode($break) }})"
                                     class="rounded-lg ring-1 ring-gray-200 dark:ring-gray-800 shadow dark:bg-gray-900 bg-white group cursor-pointer hover:shadow-lg hover:scale-[1.02] transition-all duration-300 overflow-hidden p-4">
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-2">
                                            <div class="p-1.5 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                                <x-ui.icon name="clock" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <span class="font-bold text-base text-gray-900 dark:text-white">{{ $shift }}</span>
                                        </div>
                                        @if($break)
                                        <div class="flex items-center gap-2 px-3 py-2 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                            <x-ui.icon name="pause" class="w-4 h-4 text-orange-500 dark:text-orange-400" />
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $break['duration'] }}dk</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">/ {{ $break['parts'] }} parça</span>
                                        </div>
                                        @endif
                                        <div class="flex items-center gap-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                                            <button onclick="event.stopPropagation(); editShift({{ $courier->id }}, {{ $day }}, '{{ $shift }}', {{ json_encode($break) }})"
                                                    class="flex items-center justify-center w-7 h-7 rounded-lg transition-all bg-gray-100 dark:bg-gray-800 hover:bg-blue-100 dark:hover:bg-blue-900/30">
                                                <x-ui.icon name="clock" class="w-4 h-4 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400" />
                                            </button>
                                            <button onclick="event.stopPropagation(); deleteShift({{ $courier->id }}, {{ $day }})"
                                                    class="flex items-center justify-center w-7 h-7 rounded-lg transition-all bg-gray-100 dark:bg-gray-800 hover:bg-red-100 dark:hover:bg-red-900/30">
                                                <x-ui.icon name="minus" class="w-4 h-4 text-gray-400 hover:text-red-600 dark:hover:text-red-400" />
                                            </button>
                                            <button onclick="event.stopPropagation(); copyShift({{ $courier->id }}, {{ $day }})"
                                                    class="flex items-center justify-center w-7 h-7 rounded-lg transition-all bg-gray-100 dark:bg-gray-800 hover:bg-blue-100 dark:hover:bg-blue-900/30">
                                                <x-ui.icon name="refresh" class="w-4 h-4 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div onclick="editShift({{ $courier->id }}, {{ $day }}, null, null)"
                                     class="h-full min-h-[100px] flex items-center justify-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:border-black dark:hover:border-white hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-all">
                                    <x-ui.icon name="plus" class="w-8 h-8 text-gray-400" />
                                </div>
                                @endif
                            </x-table.td>
                        @endfor
                    </x-table.tr>
                    @empty
                    <x-table.empty colspan="9" icon="users" message="Kurye bulunamadı" />
                    @endforelse
                </x-table.tbody>
            </x-table.table>
        </div>
    </x-ui.card>

    {{-- Pagination --}}
    @if($couriers->hasPages())
    <div class="mt-4">
        {{ $couriers->links() }}
    </div>
    @endif
</div>

{{-- Vardiya Düzenle Modal --}}
<x-ui.modal name="editShiftModal" title="Vardiya Düzenle" size="md">
    <form id="editShiftForm" class="space-y-4">
        <input type="hidden" id="editCourierId">
        <input type="hidden" id="editDay">

        <x-form.input type="time" id="editStartTime" label="Başlangıç Saati" />
        <x-form.input type="time" id="editEndTime" label="Bitiş Saati" />
        <x-form.input type="number" id="editBreakDuration" label="Mola Süresi (dakika)" placeholder="60" min="0" />
        <x-form.input type="number" id="editBreakParts" label="Mola Parça Sayısı" placeholder="2" min="0" />

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'editShiftModal')" class="flex-1">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" class="flex-1">
                Kaydet
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>

{{-- Vardiya Kopyala Modal --}}
<x-ui.modal name="copyShiftModal" title="Vardiyayı Kopyala" size="md">
    <form id="copyShiftForm" class="space-y-4">
        <input type="hidden" id="copyCourierId">
        <input type="hidden" id="copySourceDay">

        <p class="text-sm text-gray-600 dark:text-gray-400">Bu vardiyayı hangi günlere kopyalamak istiyorsunuz?</p>

        <div class="space-y-2">
            @php $days = ['Pazartesi', 'Sali', 'Carsamba', 'Persembe', 'Cuma', 'Cumartesi', 'Pazar']; @endphp
            @foreach($days as $index => $dayName)
            <x-form.checkbox name="target_days[]" value="{{ $index }}" label="{{ $dayName }}" />
            @endforeach
        </div>

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'copyShiftModal')" class="flex-1">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" class="flex-1">
                Kopyala
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>

{{-- Varsayılan Ayarlar Modal --}}
<x-ui.modal name="defaultModal" title="Varsayılan Vardiya Ayarları" size="2xl">
    <form id="defaultForm" class="space-y-6">
        @php $days = ['Pazartesi', 'Sali', 'Carsamba', 'Persembe', 'Cuma', 'Cumartesi', 'Pazar']; @endphp
        @foreach($days as $index => $dayName)
        <div class="flex items-center gap-4">
            <label class="w-24 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $dayName }}</label>
            <x-form.input type="time" name="default_shifts[{{ $index }}][start]"
                          :value="isset($businessInfo->default_shifts[$index]) ? explode(' - ', $businessInfo->default_shifts[$index])[0] ?? '' : ''"
                          class="flex-1" />
            <span class="text-gray-500">-</span>
            <x-form.input type="time" name="default_shifts[{{ $index }}][end]"
                          :value="isset($businessInfo->default_shifts[$index]) ? explode(' - ', $businessInfo->default_shifts[$index])[1] ?? '' : ''"
                          class="flex-1" />
        </div>
        @endforeach

        <x-layout.section title="Varsayılan Mola Ayarları" border>
            <x-layout.grid cols="2" gap="4">
                <x-form.input type="number" name="default_break_duration" label="Mola Süresi (dk)"
                              :value="$businessInfo->default_break_duration ?? 60" min="0" />
                <x-form.input type="number" name="default_break_parts" label="Parça Sayısı"
                              :value="$businessInfo->default_break_parts ?? 2" min="0" />
            </x-layout.grid>
        </x-layout.section>

        <x-form.checkbox name="auto_assign_shifts" label="Yeni kuryeler için otomatik uygula"
                         :checked="$businessInfo->auto_assign_shifts" />

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'defaultModal')" class="flex-1">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" class="flex-1">
                Kaydet
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>

{{-- Toplu Güncelle Modal --}}
<x-ui.modal name="bulkUpdateModal" title="Toplu Vardiya Güncelle" size="2xl">
    <form id="bulkUpdateForm" class="space-y-6">
        <x-feedback.alert type="info">
            Sadece doldurulan günler güncellenecektir. Boş bırakılan günler mevcut halleriyle kalacaktır.
        </x-feedback.alert>

        @php $days = ['Pazartesi', 'Sali', 'Carsamba', 'Persembe', 'Cuma', 'Cumartesi', 'Pazar']; @endphp
        @foreach($days as $index => $dayName)
        <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $dayName }}</label>
            <div class="flex items-center gap-4">
                <x-form.input type="time" name="bulk_shifts[{{ $index }}][start]" class="flex-1" />
                <span class="text-gray-500">-</span>
                <x-form.input type="time" name="bulk_shifts[{{ $index }}][end]" class="flex-1" />
            </div>
            <div class="flex items-center gap-4">
                <x-form.input type="number" name="bulk_breaks[{{ $index }}][duration]" placeholder="Mola (dk)" min="0" class="flex-1" />
                <x-form.input type="number" name="bulk_breaks[{{ $index }}][parts]" placeholder="Parça" min="0" class="flex-1" />
            </div>
        </div>
        @endforeach

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'bulkUpdateModal')" class="flex-1">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" class="flex-1">
                Güncelle
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>

@push('scripts')
<script>
    // Select All Checkbox
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.courier-checkbox').forEach(cb => cb.checked = this.checked);
    });

    // Modal Functions
    function showDefaultModal() {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'defaultModal' }));
    }

    function showBulkUpdateModal() {
        const selectedCouriers = Array.from(document.querySelectorAll('.courier-checkbox:checked')).map(cb => cb.value);
        if (selectedCouriers.length === 0) {
            showToast('Lütfen en az bir kurye seçin', 'error');
            return;
        }
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'bulkUpdateModal' }));
    }

    // Edit Shift
    function editShift(courierId, day, shift, breakData) {
        document.getElementById('editCourierId').value = courierId;
        document.getElementById('editDay').value = day;

        if (shift) {
            const [start, end] = shift.split(' - ');
            document.getElementById('editStartTime').value = start || '';
            document.getElementById('editEndTime').value = end || '';
        } else {
            document.getElementById('editStartTime').value = '';
            document.getElementById('editEndTime').value = '';
        }

        if (breakData) {
            document.getElementById('editBreakDuration').value = breakData.duration || '';
            document.getElementById('editBreakParts').value = breakData.parts || '';
        } else {
            document.getElementById('editBreakDuration').value = '';
            document.getElementById('editBreakParts').value = '';
        }

        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'editShiftModal' }));
    }

    // Delete Shift
    async function deleteShift(courierId, day) {
        if (!confirm('Bu vardiyayı silmek istediğinizden emin misiniz?')) return;

        try {
            const response = await fetch(`/bayi/vardiya-saatleri/${courierId}/sil`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ day })
            });

            if (response.ok) {
                location.reload();
            } else {
                showToast('Bir hata oluştu', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Bir hata oluştu', 'error');
        }
    }

    // Copy Shift
    function copyShift(courierId, sourceDay) {
        document.getElementById('copyCourierId').value = courierId;
        document.getElementById('copySourceDay').value = sourceDay;

        document.querySelectorAll('#copyShiftForm input[type="checkbox"]').forEach((cb, index) => {
            cb.checked = false;
            cb.disabled = index == sourceDay;
        });

        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'copyShiftModal' }));
    }

    // Apply Template
    async function applyTemplate(courierId) {
        if (!confirm('Varsayılan şablonu bu kuryeye uygulamak istediğinizden emin misiniz?')) return;

        try {
            const response = await fetch(`/bayi/vardiya-saatleri/${courierId}/sablon-uygula`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                location.reload();
            } else {
                const data = await response.json();
                showToast(data.message || 'Bir hata oluştu', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Bir hata oluştu', 'error');
        }
    }

    // Form Submissions
    document.getElementById('editShiftForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();

        const courierId = document.getElementById('editCourierId').value;
        const day = document.getElementById('editDay').value;
        const start = document.getElementById('editStartTime').value;
        const end = document.getElementById('editEndTime').value;
        const breakDuration = document.getElementById('editBreakDuration').value;
        const breakParts = document.getElementById('editBreakParts').value;

        const hours = start && end ? `${start} - ${end}` : null;

        try {
            const response = await fetch(`/bayi/vardiya-saatleri/${courierId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    day,
                    hours,
                    break_duration: breakDuration || null,
                    break_parts: breakParts || null
                })
            });

            if (response.ok) {
                location.reload();
            } else {
                showToast('Bir hata oluştu', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Bir hata oluştu', 'error');
        }
    });

    document.getElementById('copyShiftForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();

        const courierId = document.getElementById('copyCourierId').value;
        const sourceDay = document.getElementById('copySourceDay').value;
        const targetDays = Array.from(document.querySelectorAll('#copyShiftForm input[type="checkbox"]:checked')).map(cb => cb.value);

        if (targetDays.length === 0) {
            showToast('Lütfen en az bir gün seçin', 'error');
            return;
        }

        try {
            const response = await fetch(`/bayi/vardiya-saatleri/${courierId}/kopyala`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    source_day: sourceDay,
                    target_days: targetDays
                })
            });

            if (response.ok) {
                location.reload();
            } else {
                showToast('Bir hata oluştu', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Bir hata oluştu', 'error');
        }
    });

    document.getElementById('defaultForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const defaultShifts = {};

        for (let i = 0; i < 7; i++) {
            const start = formData.get(`default_shifts[${i}][start]`);
            const end = formData.get(`default_shifts[${i}][end]`);
            if (start && end) {
                defaultShifts[i] = `${start} - ${end}`;
            }
        }

        try {
            const response = await fetch('/bayi/vardiya-saatleri/varsayilan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    default_shifts: defaultShifts,
                    default_break_duration: formData.get('default_break_duration'),
                    default_break_parts: formData.get('default_break_parts'),
                    auto_assign_shifts: formData.get('auto_assign_shifts') === 'on'
                })
            });

            if (response.ok) {
                location.reload();
            } else {
                showToast('Bir hata oluştu', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Bir hata oluştu', 'error');
        }
    });

    document.getElementById('bulkUpdateForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();

        const selectedCouriers = Array.from(document.querySelectorAll('.courier-checkbox:checked')).map(cb => cb.value);
        if (selectedCouriers.length === 0) {
            showToast('Lütfen en az bir kurye seçin', 'error');
            return;
        }

        const formData = new FormData(this);
        const shifts = {};
        const breaks = {};

        for (let i = 0; i < 7; i++) {
            const start = formData.get(`bulk_shifts[${i}][start]`);
            const end = formData.get(`bulk_shifts[${i}][end]`);
            if (start && end) {
                shifts[i] = `${start} - ${end}`;
            }

            const duration = formData.get(`bulk_breaks[${i}][duration]`);
            const parts = formData.get(`bulk_breaks[${i}][parts]`);
            if (duration && parts) {
                breaks[i] = { duration: parseInt(duration), parts: parseInt(parts) };
            }
        }

        try {
            const response = await fetch('/bayi/vardiya-saatleri/toplu-guncelle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    shifts,
                    break_durations: breaks,
                    courier_ids: selectedCouriers
                })
            });

            if (response.ok) {
                location.reload();
            } else {
                showToast('Bir hata oluştu', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Bir hata oluştu', 'error');
        }
    });
</script>
@endpush
@endsection

@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Nakit Ödemeler"
        subtitle="Kurye nakit işlemleri ve bakiye yönetimi"
    >
        <x-slot name="icon">
            <x-ui.icon name="money" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <div class="bg-green-600 dark:bg-green-700 px-6 py-4 rounded-xl shadow-lg">
                <p class="text-sm text-white/90 mb-1">Toplam Nakit</p>
                <p class="text-3xl font-bold text-white">
                    <x-data.money :amount="$totalCash" />
                </p>
            </div>
        </x-slot>
    </x-layout.page-header>

    {{-- Yeni İşlem Formu --}}
    <x-ui.card class="mb-6">
        <x-layout.section title="Yeni İşlem Oluştur" icon="plus">
            <form id="cashTransactionForm" class="space-y-6">
                @csrf
                <x-layout.grid cols="1" mdCols="2" gap="6">
                    {{-- Kurye Seç --}}
                    <div>
                        <x-form.select
                            name="courier_id"
                            id="courier_id"
                            label="Kurye Seç"
                            required
                            placeholder="Kurye ara (İsim, Telefon, TC, Plaka)..."
                        >
                            @foreach($allCouriers as $courier)
                                <option value="{{ $courier->id }}"
                                        data-name="{{ $courier->name }}"
                                        data-phone="{{ $courier->phone }}"
                                        data-tc="{{ $courier->tc_no }}"
                                        data-plate="{{ $courier->vehicle_plate }}"
                                        data-balance="{{ $courier->cash_balance }}">
                                    {{ $courier->name }} - {{ $courier->phone }} @if($courier->tc_no) - TC: {{ $courier->tc_no }} @endif @if($courier->vehicle_plate) - {{ $courier->vehicle_plate }} @endif
                                </option>
                            @endforeach
                        </x-form.select>
                        <div id="courierBalance" class="mt-2 text-sm text-gray-600 dark:text-gray-400 hidden">
                            Mevcut Bakiye: <span class="font-bold" id="balanceAmount"></span>
                        </div>
                    </div>

                    {{-- Tutar --}}
                    <x-form.input
                        type="number"
                        name="amount"
                        id="amount"
                        label="Tutar"
                        step="0.01"
                        min="0.01"
                        required
                        placeholder="0.00"
                        suffix="TL"
                    />
                </x-layout.grid>

                {{-- İşlem Tipi --}}
                <x-form.form-group label="İşlem Tipi">
                    <x-layout.grid cols="1" mdCols="2" gap="4">
                        <label class="relative cursor-pointer group payment-type-option">
                            <input type="radio" name="type" value="payment_received" required class="sr-only payment-type-radio">
                            <div class="payment-type-card p-4 border-2 border-gray-200 dark:border-gray-800 bg-white dark:bg-[#181818] rounded-xl transition-all duration-200 hover:border-green-300 dark:hover:border-green-700">
                                <div class="checkmark-badge absolute -top-2 -right-2 w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center opacity-0 transition-opacity shadow-lg">
                                    <x-ui.icon name="check" class="w-5 h-5" />
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="icon-circle w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center transition-transform">
                                        <x-ui.icon name="money" class="w-6 h-6 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div>
                                        <p class="type-title font-bold text-black dark:text-white">Ödeme Al</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Kuryeden nakit al</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer group payment-type-option">
                            <input type="radio" name="type" value="advance_given" required class="sr-only payment-type-radio">
                            <div class="payment-type-card p-4 border-2 border-gray-200 dark:border-gray-800 bg-white dark:bg-[#181818] rounded-xl transition-all duration-200 hover:border-blue-300 dark:hover:border-blue-700">
                                <div class="checkmark-badge absolute -top-2 -right-2 w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center opacity-0 transition-opacity shadow-lg">
                                    <x-ui.icon name="check" class="w-5 h-5" />
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="icon-circle w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center transition-transform">
                                        <x-ui.icon name="wallet" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <p class="type-title font-bold text-black dark:text-white">Avans Ver</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Kuryeye nakit ver</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </x-layout.grid>
                </x-form.form-group>

                {{-- Notlar --}}
                <x-form.textarea
                    name="notes"
                    label="Not (Opsiyonel)"
                    :rows="3"
                    placeholder="İşlem hakkında not ekleyebilirsiniz..."
                />

                {{-- Submit --}}
                <div class="flex justify-end">
                    <x-ui.button type="submit" id="submitBtn" icon="check">
                        İşlem Oluştur
                    </x-ui.button>
                </div>
            </form>
        </x-layout.section>
    </x-ui.card>

    {{-- Son Bakiyeler --}}
    <x-ui.card class="mb-6">
        <x-layout.section title="Son Bakiyeler" icon="users">
            <x-slot name="actions">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $couriersWithTransactions->count() }} Kurye</span>
            </x-slot>

            <x-table.table>
                <x-table.thead>
                    <x-table.tr :hoverable="false">
                        <x-table.th>Kurye</x-table.th>
                        <x-table.th align="right">Nakit Bakiye</x-table.th>
                    </x-table.tr>
                </x-table.thead>
                <x-table.tbody>
                    @forelse($couriersWithTransactions as $courier)
                        <x-table.tr>
                            <x-table.td>
                                <x-data.courier-avatar :courier="$courier" size="sm" :showStatus="false" />
                            </x-table.td>
                            <x-table.td align="right">
                                <span class="text-lg font-bold {{ $courier->cash_balance > 0 ? 'text-green-600 dark:text-green-400' : ($courier->cash_balance < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400') }}">
                                    <x-data.money :amount="$courier->cash_balance" />
                                </span>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.empty colspan="2" icon="users" message="Henüz nakit işlemi yapılmamış" />
                    @endforelse
                </x-table.tbody>
            </x-table.table>
        </x-layout.section>
    </x-ui.card>

    {{-- Son İşlemler --}}
    <x-ui.card>
        <x-layout.section title="Son İşlemler" icon="list">
            <x-slot name="actions">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $recentTransactions->count() }} İşlem</span>
            </x-slot>

            <x-table.table>
                <x-table.thead>
                    <x-table.tr :hoverable="false">
                        <x-table.th>Kurye</x-table.th>
                        <x-table.th>İşletme</x-table.th>
                        <x-table.th align="right">Tutar</x-table.th>
                        <x-table.th>İşlem Tipi</x-table.th>
                        <x-table.th>Durum</x-table.th>
                        <x-table.th>Tarih</x-table.th>
                        <x-table.th align="right">İşlem</x-table.th>
                    </x-table.tr>
                </x-table.thead>
                <x-table.tbody>
                    @forelse($recentTransactions as $transaction)
                        <x-table.tr data-transaction-id="{{ $transaction->id }}">
                            <x-table.td>
                                <x-data.courier-avatar :courier="$transaction->courier" size="sm" :showStatus="false" />
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $transaction->branch->name ?? 'Merkez' }}
                                </span>
                            </x-table.td>
                            <x-table.td align="right">
                                <span class="text-base font-bold {{ $transaction->type === 'payment_received' ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400' }}">
                                    {{ $transaction->type === 'payment_received' ? '-' : '+' }}<x-data.money :amount="$transaction->amount" />
                                </span>
                            </x-table.td>
                            <x-table.td>
                                <x-ui.badge :type="$transaction->type === 'payment_received' ? 'success' : 'info'" size="sm">
                                    {{ $transaction->getTypeLabel() }}
                                </x-ui.badge>
                            </x-table.td>
                            <x-table.td>
                                @php
                                    $statusTypes = [
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'cancelled' => 'danger',
                                    ];
                                @endphp
                                <x-ui.badge :type="$statusTypes[$transaction->status] ?? 'default'" size="sm">
                                    {{ $transaction->getStatusLabel() }}
                                </x-ui.badge>
                            </x-table.td>
                            <x-table.td>
                                <x-data.date-time :date="$transaction->created_at" />
                            </x-table.td>
                            <x-table.td align="right">
                                @if($transaction->status === 'completed')
                                    <x-ui.button type="button" variant="ghost" size="sm" onclick="cancelTransaction({{ $transaction->id }})">
                                        İptal Et
                                    </x-ui.button>
                                @endif
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.empty colspan="7" icon="list" message="İşlem bulunamadı" />
                    @endforelse
                </x-table.tbody>
            </x-table.table>
        </x-layout.section>
    </x-ui.card>
</div>

{{-- Confirm Modal --}}
<x-ui.confirm-modal name="cancelTransactionModal" title="İşlem İptal" type="danger" />

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .payment-type-option input[type="radio"]:checked ~ .payment-type-card {
        transform: scale(1.02);
    }
    .payment-type-option input[type="radio"]:checked ~ .payment-type-card .checkmark-badge {
        opacity: 1 !important;
    }
    .payment-type-option input[type="radio"]:checked ~ .payment-type-card .icon-circle {
        transform: scale(1.1);
    }
    .payment-type-option input[type="radio"][value="payment_received"]:checked ~ .payment-type-card {
        border-color: rgb(34, 197, 94) !important;
        background-color: rgb(240, 253, 244) !important;
    }
    .dark .payment-type-option input[type="radio"][value="payment_received"]:checked ~ .payment-type-card {
        background-color: rgba(34, 197, 94, 0.15) !important;
        border-color: rgb(74, 222, 128) !important;
    }
    .payment-type-option input[type="radio"][value="advance_given"]:checked ~ .payment-type-card {
        border-color: rgb(59, 130, 246) !important;
        background-color: rgb(239, 246, 255) !important;
    }
    .dark .payment-type-option input[type="radio"][value="advance_given"]:checked ~ .payment-type-card {
        background-color: rgba(59, 130, 246, 0.15) !important;
        border-color: rgb(96, 165, 250) !important;
    }
    .select2-container--default .select2-selection--single {
        background-color: rgb(249, 250, 251);
        border: 1px solid rgb(229, 231, 235);
        border-radius: 0.5rem;
        height: 50px;
        padding: 0.75rem 1rem;
    }
    .dark .select2-container--default .select2-selection--single {
        background-color: rgb(17, 24, 39) !important;
        border-color: rgb(31, 41, 55) !important;
    }
    .dark .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: white !important;
    }
    .select2-container { width: 100% !important; }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#courier_id').select2({
            placeholder: 'Kurye ara (İsim, Telefon, TC, Plaka)...',
            allowClear: true,
            width: '100%'
        });

        $('#courier_id').on('change', function() {
            const selectedOption = $(this).find(':selected');
            const balance = selectedOption.data('balance');
            const balanceDiv = $('#courierBalance');
            const balanceAmount = $('#balanceAmount');

            if (balance !== undefined && balance !== null) {
                balanceAmount.text('₺' + parseFloat(balance).toFixed(2));
                balanceDiv.removeClass('hidden');
            } else {
                balanceDiv.addClass('hidden');
            }
        });
    });

    $('#cashTransactionForm').on('submit', async function(e) {
        e.preventDefault();

        const submitBtn = $('#submitBtn');
        submitBtn.prop('disabled', true);

        const formData = new FormData(this);

        try {
            const response = await fetch('{{ route('bayi.nakit-odemeler.store') }}', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Bir hata oluştu.', 'error');
            }
        } catch (error) {
            showToast('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
        } finally {
            submitBtn.prop('disabled', false);
        }
    });

    function cancelTransaction(transactionId) {
        window.dispatchEvent(new CustomEvent('open-confirm', {
            detail: {
                title: 'İşlem İptal',
                message: 'Bu işlemi iptal etmek istediğinizden emin misiniz? Bu işlem geri alınamaz.',
                confirmText: 'İptal Et',
                cancelText: 'Vazgeç',
                onConfirm: async () => {
                    try {
                        const response = await fetch(`/bayi/nakit-odemeler/${transactionId}/cancel`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (error) {
                        showToast('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                    }
                }
            }
        }));
    }
</script>
@endpush
@endsection

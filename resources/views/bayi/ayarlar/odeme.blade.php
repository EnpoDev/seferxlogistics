<x-bayi-layout>
    <x-slot name="title">Ödeme Yöntemleri - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Ödeme Yöntemleri</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">İşletmenizde kabul edilen ödeme yöntemlerini yapılandırın</p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-red-700 dark:text-red-400">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Ayarlar Formu -->
        <form action="{{ route('bayi.ayarlar.odeme-yontemleri.update') }}" method="POST" x-data="{ mealCardsEnabled: {{ $branch->payment_meal_cards_enabled ? 'true' : 'false' }} }">
            @csrf

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="p-6 space-y-6">

                    <!-- Nakit Ödeme -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="payment_cash_enabled"
                            label="Nakit Ödeme"
                            description="Teslimat sırasında nakit ödeme kabul et"
                            :checked="$branch->payment_cash_enabled ?? true"
                        />
                    </div>

                    <!-- Kart Ödeme (POS) -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="payment_card_enabled"
                            label="Kart Ödeme (POS)"
                            description="Teslimat sırasında kredi/banka kartı ile ödeme kabul et"
                            :checked="$branch->payment_card_enabled ?? true"
                        />
                    </div>

                    <!-- Online Ödeme -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="payment_online_enabled"
                            label="Online Ödeme"
                            description="Sipariş sırasında online ödeme kabul et"
                            :checked="$branch->payment_online_enabled ?? false"
                        />
                    </div>

                    <!-- Havale/EFT -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="payment_bank_transfer_enabled"
                            label="Havale/EFT"
                            description="Banka havalesi veya EFT ile ödeme kabul et"
                            :checked="$branch->payment_bank_transfer_enabled ?? false"
                        />
                    </div>

                    <!-- Yemek Kartları -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg space-y-4">
                        <x-form.toggle
                            name="payment_meal_cards_enabled"
                            label="Yemek Kartları"
                            description="Teslimat sırasında yemek kartı ile ödeme kabul et"
                            :checked="$branch->payment_meal_cards_enabled ?? false"
                            x-model="mealCardsEnabled"
                        />

                        <!-- Meal Card Options (shown when meal cards enabled) -->
                        <div x-show="mealCardsEnabled" x-transition class="ml-6 pl-6 border-l-2 border-gray-300 dark:border-gray-700 space-y-3">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Kabul Edilen Yemek Kartları:</p>

                            @php
                                $mealCardOptions = [
                                    'sodexo' => 'Sodexo',
                                    'multinet' => 'Multinet',
                                    'ticket' => 'Ticket',
                                    'metropol' => 'Metropol',
                                    'setcard' => 'Setcard',
                                    'edenred' => 'Edenred',
                                    'pluxee' => 'Pluxee',
                                    'tokenflex' => 'TokenFlex',
                                ];
                                $enabledCards = $branch->enabled_meal_cards ?? [];
                            @endphp

                            <div class="grid grid-cols-2 gap-3">
                                @foreach($mealCardOptions as $value => $label)
                                    <label class="flex items-center space-x-2 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:border-gray-400 dark:hover:border-gray-600 transition-colors">
                                        <input type="checkbox"
                                               name="enabled_meal_cards[]"
                                               value="{{ $value }}"
                                               {{ in_array($value, $enabledCards) ? 'checked' : '' }}
                                               class="w-4 h-4 text-black dark:text-white border-gray-300 dark:border-gray-600 rounded focus:ring-black dark:focus:ring-white">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">
                        Kaydet
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-bayi-layout>

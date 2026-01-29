<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Müşteri Portalı - SeferX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4" x-data="loginApp()">
        <div class="max-w-md w-full">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-black rounded-2xl mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Müşteri Portalı</h1>
                <p class="text-gray-600 mt-2">Siparişlerinizi takip edin</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <!-- Step 1: Phone -->
                <div x-show="step === 1" x-transition>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Telefon Numaranız</h2>
                    <p class="text-sm text-gray-600 mb-6">Sipariş verirken kullandığınız telefon numarasını girin.</p>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefon</label>
                        <input type="tel" x-model="phone" @keyup.enter="sendOtp()"
                               placeholder="05XX XXX XX XX"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-black focus:border-black transition-all">
                    </div>

                    <button @click="sendOtp()" :disabled="isLoading || !phone"
                            class="w-full py-3 bg-black text-white rounded-xl font-medium hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isLoading">Devam Et</span>
                        <span x-show="isLoading">Gönderiliyor...</span>
                    </button>
                </div>

                <!-- Step 2: OTP -->
                <div x-show="step === 2" x-transition>
                    <button @click="step = 1" class="flex items-center text-gray-600 hover:text-black mb-4">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Geri
                    </button>

                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Doğrulama Kodu</h2>
                    <p class="text-sm text-gray-600 mb-6">
                        <span x-text="phone"></span> numarasına gönderilen 6 haneli kodu girin.
                    </p>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kod</label>
                        <input type="text" x-model="otp" maxlength="6" @keyup.enter="verifyOtp()"
                               placeholder="XXXXXX"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-black focus:border-black transition-all text-center text-2xl tracking-widest">
                    </div>

                    <button @click="verifyOtp()" :disabled="isLoading || otp.length !== 6"
                            class="w-full py-3 bg-black text-white rounded-xl font-medium hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isLoading">Giriş Yap</span>
                        <span x-show="isLoading">Doğrulanıyor...</span>
                    </button>

                    <button @click="sendOtp()" class="w-full mt-4 text-gray-600 hover:text-black text-sm">
                        Kodu tekrar gönder
                    </button>
                </div>

                <!-- Error Message -->
                <div x-show="error" x-transition class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-red-700 text-sm" x-text="error"></p>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-gray-500 text-sm mt-8">
                Siparişlerinizle ilgili sorularınız için: <a href="tel:+902121234567" class="text-black font-medium">0212 123 45 67</a>
            </p>
        </div>
    </div>

    <script>
        function loginApp() {
            return {
                step: 1,
                phone: '',
                otp: '',
                isLoading: false,
                error: null,

                async sendOtp() {
                    if (!this.phone) return;

                    this.isLoading = true;
                    this.error = null;

                    try {
                        const response = await fetch('/portal/send-otp', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ phone: this.phone })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.step = 2;
                            // Debug modda OTP'yi göster
                            if (data.debug_otp) {
                                this.otp = data.debug_otp;
                            }
                        } else {
                            this.error = data.message;
                        }
                    } catch (error) {
                        this.error = 'Bir hata oluştu. Lütfen tekrar deneyin.';
                    } finally {
                        this.isLoading = false;
                    }
                },

                async verifyOtp() {
                    if (this.otp.length !== 6) return;

                    this.isLoading = true;
                    this.error = null;

                    try {
                        const response = await fetch('/portal/verify-otp', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ phone: this.phone, otp: this.otp })
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Token'i cookie'ye kaydet
                            document.cookie = `portal_token=${data.token}; path=/; max-age=${60*60*24}`;
                            // Dashboard'a yönlendir
                            window.location.href = '/portal/dashboard';
                        } else {
                            this.error = data.message;
                        }
                    } catch (error) {
                        this.error = 'Bir hata oluştu. Lütfen tekrar deneyin.';
                    } finally {
                        this.isLoading = false;
                    }
                }
            };
        }
    </script>
</body>
</html>

@extends('layouts.kurye')

@section('content')
<div class="slide-up min-h-screen bg-black" x-data="deliverApp()">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-b from-black/80 to-transparent px-4 py-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('kurye.order.detail', $order) }}" class="w-10 h-10 bg-white/20 backdrop-blur rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="text-center">
                <p class="text-white font-semibold">#{{ $order->order_number }}</p>
                <p class="text-white/70 text-xs">Teslimat Kanıtı</p>
            </div>
            <div class="w-10 h-10"></div>
        </div>
    </div>

    <!-- Camera Preview / Photo Preview -->
    <div class="relative h-screen">
        <!-- Video Stream (Camera) -->
        <video x-show="!photo" x-ref="video" autoplay playsinline muted class="w-full h-full object-cover"></video>

        <!-- Captured Photo Preview -->
        <div x-show="photo" class="w-full h-full">
            <img :src="photo" class="w-full h-full object-cover">
        </div>

        <!-- Camera Overlay -->
        <div class="absolute inset-0 pointer-events-none">
            <!-- Corner guides -->
            <div class="absolute top-24 left-8 w-16 h-16 border-l-4 border-t-4 border-white/50 rounded-tl-lg"></div>
            <div class="absolute top-24 right-8 w-16 h-16 border-r-4 border-t-4 border-white/50 rounded-tr-lg"></div>
            <div class="absolute bottom-48 left-8 w-16 h-16 border-l-4 border-b-4 border-white/50 rounded-bl-lg"></div>
            <div class="absolute bottom-48 right-8 w-16 h-16 border-r-4 border-b-4 border-white/50 rounded-br-lg"></div>
        </div>

        <!-- Instructions -->
        <div x-show="!photo" class="absolute top-20 left-0 right-0 text-center">
            <p class="text-white/80 text-sm bg-black/30 px-4 py-2 rounded-full inline-block backdrop-blur-sm">
                Teslim edilen paketi veya kapıyı fotoğraflayın
            </p>
        </div>

        <!-- Bottom Controls -->
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 via-black/60 to-transparent p-6 pt-20">
            <!-- Photo not taken yet -->
            <div x-show="!photo" class="flex items-center justify-center space-x-8">
                <!-- Gallery Button -->
                <label class="w-14 h-14 bg-white/20 backdrop-blur rounded-full flex items-center justify-center cursor-pointer">
                    <input type="file" accept="image/*" @change="selectFromGallery" class="hidden">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </label>

                <!-- Capture Button -->
                <button @click="capturePhoto" class="w-20 h-20 rounded-full bg-white flex items-center justify-center shadow-2xl">
                    <div class="w-16 h-16 rounded-full border-4 border-black/20"></div>
                </button>

                <!-- Switch Camera -->
                <button @click="switchCamera" class="w-14 h-14 bg-white/20 backdrop-blur rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>

            <!-- Photo taken - confirm or retake -->
            <div x-show="photo" class="space-y-4">
                <!-- Note input -->
                <div class="relative">
                    <input type="text" x-model="note" placeholder="Not ekle (isteğe bağlı)"
                           class="w-full px-4 py-3 bg-white/10 backdrop-blur border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Retake -->
                    <button @click="retakePhoto" class="flex-1 py-4 bg-white/20 backdrop-blur text-white rounded-xl font-semibold">
                        Tekrar Çek
                    </button>

                    <!-- Confirm -->
                    <button @click="uploadPhoto" x-bind:disabled="uploading"
                            class="flex-1 py-4 bg-green-500 text-white rounded-xl font-semibold disabled:opacity-50">
                        <span x-show="!uploading">Teslimi Onayla</span>
                        <span x-show="uploading" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Yükleniyor...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="mt-6 bg-white/10 backdrop-blur rounded-xl p-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-medium text-sm">{{ $order->customer_name }}</p>
                        <p class="text-white/60 text-xs truncate">{{ $order->customer_address }}</p>
                    </div>
                    <a href="tel:{{ $order->customer_phone }}" class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden canvas for photo capture -->
    <canvas x-ref="canvas" class="hidden"></canvas>

    <!-- Success Modal -->
    <div x-show="showSuccess" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-8 text-center max-w-sm w-full">
            <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Teslim Edildi!</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Teslimat kanıtı başarıyla kaydedildi.</p>
            <a href="{{ route('kurye.dashboard') }}" class="block w-full py-3 bg-green-500 text-white rounded-xl font-semibold">
                Ana Sayfaya Dön
            </a>
        </div>
    </div>

    <!-- Error Toast -->
    <div x-show="error" x-transition
         class="fixed top-20 left-4 right-4 bg-red-500 text-white px-4 py-3 rounded-xl shadow-lg z-50">
        <p class="text-sm" x-text="error"></p>
    </div>
</div>

@push('scripts')
<script>
function deliverApp() {
    return {
        photo: null,
        photoBlob: null,
        note: '',
        uploading: false,
        showSuccess: false,
        error: null,
        stream: null,
        facingMode: 'environment', // Back camera by default
        currentLocation: null,

        async init() {
            // Get current location
            this.getCurrentLocation();

            // Start camera
            await this.startCamera();
        },

        getCurrentLocation() {
            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.currentLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                    },
                    (error) => {
                        console.log('Location error:', error);
                    },
                    { enableHighAccuracy: true }
                );
            }
        },

        async startCamera() {
            try {
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                }

                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: this.facingMode,
                        width: { ideal: 1920 },
                        height: { ideal: 1080 }
                    },
                    audio: false
                });

                this.$refs.video.srcObject = this.stream;
            } catch (error) {
                console.error('Camera error:', error);
                this.error = 'Kamera erişimi sağlanamadı. Lütfen kamera izinlerini kontrol edin.';
                setTimeout(() => this.error = null, 5000);
            }
        },

        async switchCamera() {
            this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
            await this.startCamera();
        },

        capturePhoto() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);

            this.photo = canvas.toDataURL('image/jpeg', 0.85);

            // Convert to blob for upload
            canvas.toBlob((blob) => {
                this.photoBlob = blob;
            }, 'image/jpeg', 0.85);

            // Stop the camera
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
        },

        selectFromGallery(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                this.photo = e.target.result;
                this.photoBlob = file;

                // Stop the camera
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                }
            };
            reader.readAsDataURL(file);
        },

        async retakePhoto() {
            this.photo = null;
            this.photoBlob = null;
            await this.startCamera();
        },

        async uploadPhoto() {
            if (!this.photoBlob) return;

            this.uploading = true;
            this.error = null;

            const formData = new FormData();
            formData.append('photo', this.photoBlob, 'pod.jpg');

            if (this.note) {
                formData.append('note', this.note);
            }

            if (this.currentLocation) {
                formData.append('lat', this.currentLocation.lat);
                formData.append('lng', this.currentLocation.lng);
            }

            try {
                const response = await fetch('{{ route("kurye.order.pod.upload", $order) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess = true;
                } else {
                    this.error = data.message || 'Bir hata oluştu';
                    setTimeout(() => this.error = null, 5000);
                }
            } catch (error) {
                console.error('Upload error:', error);
                this.error = 'Bağlantı hatası. Lütfen tekrar deneyin.';
                setTimeout(() => this.error = null, 5000);
            } finally {
                this.uploading = false;
            }
        },

        destroy() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
        }
    }
}
</script>
@endpush

@push('styles')
<style>
    /* Hide scrollbar for this page */
    body {
        overflow: hidden;
    }
</style>
@endpush
@endsection

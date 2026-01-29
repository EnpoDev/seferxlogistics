<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerPortalController extends Controller
{
    private TrackingService $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Portal giris sayfasi
     */
    public function index()
    {
        return view('portal.index');
    }

    /**
     * Telefon numarasi ile giris / OTP gonder
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:10|max:15',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);

        // Telefon numarasını normalize et
        $normalizedPhone = $this->normalizePhoneNumber($phone);

        if (!$normalizedPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Gecersiz telefon numarasi formati.',
            ], 400);
        }

        // Musteri var mi kontrol et - TAM EŞLEŞME ile ara (LIKE pattern KALDIRILDI)
        $customer = Customer::where(function ($query) use ($normalizedPhone) {
            $query->where('phone', $normalizedPhone)
                ->orWhere('phone', '0' . $normalizedPhone)
                ->orWhere('phone', '+90' . $normalizedPhone)
                ->orWhere('phone', '90' . $normalizedPhone);
        })->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Bu telefon numarasina ait musteri bulunamadi.',
            ], 404);
        }

        // OTP olustur (6 haneli)
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // OTP'yi cache'e kaydet (5 dakika gecerli)
        Cache::put("portal_otp_{$normalizedPhone}", $otp, now()->addMinutes(5));

        // SMS gonder (production'da CustomerNotificationService kullanilir)
        // CustomerNotificationService::sendSMS($phone, "Dogrulama kodunuz: {$otp}");

        return response()->json([
            'success' => true,
            'message' => 'Dogrulama kodu gonderildi.',
            // OTP debug modda bile artık gösterilmiyor - güvenlik açığı
        ]);
    }

    /**
     * OTP dogrula ve giris yap
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        $normalizedPhone = $this->normalizePhoneNumber($phone);

        if (!$normalizedPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Gecersiz telefon numarasi formati.',
            ], 400);
        }

        $cachedOtp = Cache::get("portal_otp_{$normalizedPhone}");

        if (!$cachedOtp || !hash_equals($cachedOtp, $validated['otp'])) {
            return response()->json([
                'success' => false,
                'message' => 'Gecersiz veya suresi dolmus dogrulama kodu.',
            ], 400);
        }

        // OTP'yi sil
        Cache::forget("portal_otp_{$normalizedPhone}");

        // Musteriyi bul - TAM EŞLEŞME ile ara
        $customer = Customer::where(function ($query) use ($normalizedPhone) {
            $query->where('phone', $normalizedPhone)
                ->orWhere('phone', '0' . $normalizedPhone)
                ->orWhere('phone', '+90' . $normalizedPhone)
                ->orWhere('phone', '90' . $normalizedPhone);
        })->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Musteri bulunamadi.',
            ], 404);
        }

        // Portal token olustur
        $token = Str::random(64);
        Cache::put("portal_token_{$token}", $customer->id, now()->addHours(24));

        return response()->json([
            'success' => true,
            'token' => $token,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
        ]);
    }

    /**
     * Musteri dashboard
     */
    public function dashboard(Request $request)
    {
        $customer = $this->getCustomerFromToken($request);

        if (!$customer) {
            return redirect()->route('portal.index')->with('error', 'Oturum suresi doldu.');
        }

        // Aktif siparisler
        $activeOrders = Order::where('customer_id', $customer->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Son siparisler
        $recentOrders = Order::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Istatistikler
        $stats = [
            'total_orders' => Order::where('customer_id', $customer->id)->count(),
            'total_spent' => Order::where('customer_id', $customer->id)
                ->where('status', 'delivered')
                ->sum('total'),
            'this_month' => Order::where('customer_id', $customer->id)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];

        return view('portal.dashboard', compact('customer', 'activeOrders', 'recentOrders', 'stats'));
    }

    /**
     * Siparis gecmisi
     */
    public function orders(Request $request)
    {
        $customer = $this->getCustomerFromToken($request);

        if (!$customer) {
            return redirect()->route('portal.index');
        }

        $orders = Order::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('portal.orders', compact('customer', 'orders'));
    }

    /**
     * Siparis detayi
     */
    public function orderDetail(Request $request, Order $order)
    {
        $customer = $this->getCustomerFromToken($request);

        if (!$customer || $order->customer_id !== $customer->id) {
            return redirect()->route('portal.index');
        }

        $trackingData = null;
        if (!in_array($order->status, ['delivered', 'cancelled'])) {
            $trackingData = $this->trackingService->getTrackingData($order);
        }

        return view('portal.order-detail', compact('customer', 'order', 'trackingData'));
    }

    /**
     * Siparis takip API
     */
    public function trackOrder(Request $request, Order $order): JsonResponse
    {
        $customer = $this->getCustomerFromToken($request);

        if (!$customer || $order->customer_id !== $customer->id) {
            return response()->json(['success' => false], 403);
        }

        $trackingData = $this->trackingService->getTrackingData($order);

        return response()->json([
            'success' => true,
            'data' => $trackingData,
        ]);
    }

    /**
     * Adreslerim
     */
    public function addresses(Request $request)
    {
        $customer = $this->getCustomerFromToken($request);

        if (!$customer) {
            return redirect()->route('portal.index');
        }

        $addresses = $customer->addresses ?? [];

        return view('portal.addresses', compact('customer', 'addresses'));
    }

    /**
     * Cikis yap
     */
    public function logout(Request $request)
    {
        $token = $request->cookie('portal_token') ?? $request->header('X-Portal-Token');

        if ($token) {
            Cache::forget("portal_token_{$token}");
        }

        return redirect()->route('portal.index')
            ->withCookie(cookie()->forget('portal_token'));
    }

    /**
     * Token'dan musteriyi al
     */
    private function getCustomerFromToken(Request $request): ?Customer
    {
        $token = $request->cookie('portal_token') ?? $request->header('X-Portal-Token') ?? $request->get('token');

        if (!$token) {
            return null;
        }

        $customerId = Cache::get("portal_token_{$token}");

        if (!$customerId) {
            return null;
        }

        return Customer::find($customerId);
    }

    /**
     * Telefon numarasını standart 10 haneli formata çevir
     *
     * @param string $phone
     * @return string|null
     */
    private function normalizePhoneNumber(string $phone): ?string
    {
        // Sadece rakamları al
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // Türkiye ülke kodu kaldır
        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        // Başındaki 0'ı kaldır
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }

        // 10 haneli olmalı (5XX XXX XX XX)
        if (strlen($digits) !== 10) {
            return null;
        }

        // 5 ile başlamalı (mobil numara)
        if (!str_starts_with($digits, '5')) {
            return null;
        }

        return $digits;
    }
}

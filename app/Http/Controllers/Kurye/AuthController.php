<?php

namespace App\Http\Controllers\Kurye;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('courier')->check()) {
            return redirect()->route('kurye.dashboard');
        }
        
        return view('kurye.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Normalize phone number - sadece rakamları al
        $phone = preg_replace('/[^0-9]/', '', $request->phone);

        // Telefon numarasını standart formata çevir (10 haneli)
        $normalizedPhone = $this->normalizePhoneNumber($phone);

        if (!$normalizedPhone) {
            return back()->withErrors([
                'phone' => 'Geçersiz telefon numarası formatı.',
            ])->withInput();
        }

        // Sadece tam eşleşme ile kurye ara - LIKE pattern KALDIRILDI (güvenlik açığı)
        $courier = Courier::where(function ($query) use ($normalizedPhone) {
            $query->where('phone', $normalizedPhone)
                ->orWhere('phone', '0' . $normalizedPhone)
                ->orWhere('phone', '+90' . $normalizedPhone)
                ->orWhere('phone', '90' . $normalizedPhone);
        })->first();

        if (!$courier) {
            return back()->withErrors([
                'phone' => 'Bu telefon numarasıyla kayıtlı kurye bulunamadı.',
            ])->withInput();
        }

        if (!$courier->is_app_enabled) {
            return back()->withErrors([
                'phone' => 'Uygulama erişiminiz devre dışı bırakılmış. Lütfen yöneticinizle iletişime geçin.',
            ])->withInput();
        }

        if (empty($courier->password)) {
            return back()->withErrors([
                'phone' => 'Hesabınız için henüz şifre oluşturulmamış. Lütfen yöneticinizle iletişime geçin.',
            ])->withInput();
        }

        if (!Hash::check($request->password, $courier->password)) {
            return back()->withErrors([
                'password' => 'Şifre hatalı.',
            ])->withInput();
        }

        Auth::guard('courier')->login($courier, $request->boolean('remember'));

        $courier->update([
            'last_login_at' => now(),
            'status' => Courier::STATUS_AVAILABLE,
        ]);

        return redirect()->intended(route('kurye.dashboard'));
    }

    public function logout(Request $request)
    {
        $courier = Auth::guard('courier')->user();

        if ($courier) {
            $courier->update(['status' => Courier::STATUS_OFFLINE]);
        }

        Auth::guard('courier')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('kurye.login');
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


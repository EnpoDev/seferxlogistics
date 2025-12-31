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

        // Normalize phone number
        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        
        $courier = Courier::where('phone', $phone)
            ->orWhere('phone', '0' . $phone)
            ->orWhere('phone', '+90' . $phone)
            ->orWhere('phone', 'like', '%' . substr($phone, -10))
            ->first();

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
}


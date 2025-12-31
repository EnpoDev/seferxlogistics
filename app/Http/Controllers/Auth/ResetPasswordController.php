<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class ResetPasswordController extends Controller
{
    /**
     * Show the password reset form.
     */
    public function showResetForm(Request $request, string $token)
    {
        $email = $request->query('email');
        
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Reset the password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $email = $request->input('email');
        $token = $request->input('token');
        $password = $request->input('password');

        // Find the token record
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record) {
            return back()->withErrors([
                'email' => 'Bu e-posta adresi için şifre sıfırlama talebi bulunamadı.',
            ])->withInput();
        }

        // Verify token
        if (!Hash::check($token, $record->token)) {
            return back()->withErrors([
                'email' => 'Şifre sıfırlama bağlantısı geçersiz.',
            ])->withInput();
        }

        // Check token expiration (60 minutes)
        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            
            return back()->withErrors([
                'email' => 'Şifre sıfırlama bağlantısının süresi dolmuş. Lütfen yeni bir talep oluşturun.',
            ])->withInput();
        }

        // Find user and update password
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Bu e-posta adresi ile kayıtlı bir kullanıcı bulunamadı.',
            ])->withInput();
        }

        $user->update([
            'password' => Hash::make($password),
        ]);

        // Delete the token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()->route('login')->with('status', 'Şifreniz başarıyla sıfırlandı. Yeni şifrenizle giriş yapabilirsiniz.');
    }
}


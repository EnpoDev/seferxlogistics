<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;

class ForgotPasswordController extends Controller
{
    /**
     * Show the form to request a password reset link.
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the given email.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Bu e-posta adresi ile kayıtlı bir kullanıcı bulunamadı.',
            ])->withInput();
        }

        // Generate token
        $token = Str::random(64);

        // Delete existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Insert new token
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Send email
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $email]);

        try {
            Mail::send('emails.password-reset', [
                'user' => $user,
                'resetUrl' => $resetUrl,
                'token' => $token,
            ], function ($message) use ($email, $user) {
                $message->to($email, $user->name)
                    ->subject('Şifre Sıfırlama Talebi - SeferX Lojistik');
            });

            return back()->with('status', 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => 'E-posta gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.',
            ])->withInput();
        }
    }
}


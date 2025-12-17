<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // If user has multiple roles, redirect to panel selection
            if ($user->hasMultipleRoles()) {
                return redirect()->route('panel.selection');
            }
            
            // If user has single role, auto-assign and redirect
            $panel = $user->getFirstRole();
            session(['active_panel' => $panel]);
            
            if ($panel === 'bayi') {
                return redirect()->intended(route('bayi.harita'));
            } else {
                return redirect()->intended(route('harita'));
            }
        }

        return back()->withErrors([
            'email' => 'Girdiğiniz bilgiler hatalı.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

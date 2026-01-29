<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * Sadece super_admin rolune sahip kullanicilar erisebilir.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Sadece super_admin rolu erisebilir
        if (!$user->hasRole('super_admin')) {
            abort(403, 'Bu alana erisim yetkiniz bulunmamaktadir.');
        }

        return $next($request);
    }
}

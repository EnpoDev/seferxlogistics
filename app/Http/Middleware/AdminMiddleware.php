<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * super_admin veya admin rolune sahip kullanicilar erisebilir.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // super_admin veya admin rolu erisebilir
        if (!$user->hasRole('super_admin') && !$user->hasRole('admin')) {
            abort(403, 'Bu alana erisim yetkiniz bulunmamaktadir.');
        }

        return $next($request);
    }
}

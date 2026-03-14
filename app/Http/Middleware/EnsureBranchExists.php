<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchExists
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->role === 'bayi' && !$user->getActiveBranchId()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hesabınıza atanmış bir işletme bulunamadı. Lütfen yöneticinizle iletişime geçin.',
                ], 403);
            }

            abort(403, 'Hesabınıza atanmış bir işletme bulunamadı. Lütfen yöneticinizle iletişime geçin.');
        }

        return $next($request);
    }
}

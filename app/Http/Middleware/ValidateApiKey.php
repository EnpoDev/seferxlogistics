<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKeyHeader = $request->header('X-API-Key');

        if (!$apiKeyHeader) {
            return response()->json([
                'success' => false,
                'message' => 'API key gerekli. X-API-Key header\'ı eksik.',
            ], 401);
        }

        $apiKey = ApiKey::findByKey($apiKeyHeader);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz veya süresi dolmuş API key.',
            ], 401);
        }

        if (!$apiKey->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'API key devre dışı veya süresi dolmuş.',
            ], 401);
        }

        // Mark as used
        $apiKey->markAsUsed();

        // Store API key in request for later use
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guvenlik Header'lari Middleware
 *
 * OWASP tavsiyelerine uygun HTTP guvenlik header'lari ekler.
 * XSS, Clickjacking ve diger saldiri vektorlerine karsi koruma saglar.
 *
 * @see https://owasp.org/www-project-secure-headers/
 * @package App\Http\Middleware
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // XSS Korumasi
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Clickjacking Korumasi
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // MIME Type Sniffing Engelleme
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (eski adıyla Feature-Policy)
        $response->headers->set('Permissions-Policy', implode(', ', [
            'accelerometer=()',
            'camera=()',
            'geolocation=(self)',  // Harita icin gerekli
            'gyroscope=()',
            'magnetometer=()',
            'microphone=()',
            'payment=()',
            'usb=()',
        ]));

        // Content Security Policy
        if (app()->environment('production')) {
            $csp = $this->buildContentSecurityPolicy();
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // Strict Transport Security (sadece HTTPS)
        if ($request->secure() || app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Cache Control (hassas sayfalar icin)
        if ($this->isSensitivePage($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }

    /**
     * Content Security Policy olustur
     *
     * @return string
     */
    private function buildContentSecurityPolicy(): string
    {
        $directives = [
            // Varsayilan kaynak
            "default-src 'self'",

            // Script kaynaklari
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.pusher.com https://cdn.jsdelivr.net",

            // Style kaynaklari
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",

            // Font kaynaklari
            "font-src 'self' https://fonts.gstatic.com data:",

            // Resim kaynaklari
            "img-src 'self' data: blob: https://*.tile.openstreetmap.org https://*.basemaps.cartocdn.com",

            // Baglanti kaynaklari (API, WebSocket)
            "connect-src 'self' https://*.pusher.com wss://*.pusher.com https://nominatim.openstreetmap.org",

            // Frame kaynaklari
            "frame-src 'self'",

            // Form action
            "form-action 'self'",

            // Base URI
            "base-uri 'self'",

            // Object kaynaklari (Flash, vb.)
            "object-src 'none'",

            // Frame ancestors (Clickjacking)
            "frame-ancestors 'self'",

            // Upgrade insecure requests
            "upgrade-insecure-requests",
        ];

        return implode('; ', $directives);
    }

    /**
     * Hassas sayfa mi kontrol et
     *
     * @param Request $request
     * @return bool
     */
    private function isSensitivePage(Request $request): bool
    {
        $sensitivePaths = [
            'login',
            'register',
            'password',
            'ayarlar',
            'settings',
            'profile',
            'kurye/giris',
            'api/couriers',
            'api/orders',
        ];

        foreach ($sensitivePaths as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AuditLogService;

/**
 * Webhook Imza Dogrulama Middleware
 *
 * Ucuncu parti platformlardan gelen webhook isteklerinin
 * gercekligini dogrular. Replay attack'lari onler.
 *
 * Desteklenen Platformlar:
 * - Getir
 * - Trendyol
 * - Yemeksepeti
 *
 * @package App\Http\Middleware
 */
class ValidateWebhookSignature
{
    /**
     * Maksimum timestamp farki (saniye)
     * Replay attack'lari onlemek icin
     */
    private const MAX_TIMESTAMP_DIFF = 300; // 5 dakika

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $platform Platform adi
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $platform): Response
    {
        // Webhook secret'ini al
        $secret = $this->getWebhookSecret($platform);

        if (empty($secret)) {
            AuditLogService::logWebhook($platform, 'error', [
                'reason' => 'Webhook secret not configured',
            ]);

            return response()->json([
                'error' => 'Webhook configuration error',
            ], 500);
        }

        // Imza dogrulamasi
        if (!$this->verifySignature($request, $platform, $secret)) {
            AuditLogService::logSuspiciousActivity(
                'Invalid webhook signature',
                [
                    'platform' => $platform,
                    'ip' => $request->ip(),
                    'headers' => $this->getSafeHeaders($request),
                ]
            );

            return response()->json([
                'error' => 'Invalid signature',
            ], 401);
        }

        // Timestamp kontrolu (replay attack onleme)
        if (!$this->verifyTimestamp($request, $platform)) {
            AuditLogService::logSuspiciousActivity(
                'Webhook timestamp out of range (possible replay attack)',
                [
                    'platform' => $platform,
                    'ip' => $request->ip(),
                ]
            );

            return response()->json([
                'error' => 'Request timestamp out of range',
            ], 401);
        }

        // Basarili webhook logu
        AuditLogService::logWebhook($platform, 'validated');

        return $next($request);
    }

    /**
     * Platform icin webhook secret'ini al
     */
    private function getWebhookSecret(string $platform): ?string
    {
        return match (strtolower($platform)) {
            'getir' => config('services.getir.webhook_secret') ?? env('WEBHOOK_SECRET_GETIR'),
            'trendyol' => config('services.trendyol.webhook_secret') ?? env('WEBHOOK_SECRET_TRENDYOL'),
            'yemeksepeti' => config('services.yemeksepeti.webhook_secret') ?? env('WEBHOOK_SECRET_YEMEKSEPETI'),
            default => env('WEBHOOK_SECRET_' . strtoupper($platform)),
        };
    }

    /**
     * Imza dogrula
     */
    private function verifySignature(Request $request, string $platform, string $secret): bool
    {
        $providedSignature = $this->getSignatureFromRequest($request, $platform);

        if (empty($providedSignature)) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = $this->calculateSignature($payload, $secret, $platform);

        // Timing-safe karsilastirma
        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Request'ten imzayi al
     */
    private function getSignatureFromRequest(Request $request, string $platform): ?string
    {
        // Platform bazli header isimleri
        $headerNames = match (strtolower($platform)) {
            'getir' => ['X-Getir-Signature', 'X-Signature'],
            'trendyol' => ['X-Trendyol-Signature', 'X-Webhook-Signature'],
            'yemeksepeti' => ['X-YS-Signature', 'X-Signature'],
            default => ['X-Signature', 'X-Webhook-Signature', 'X-Hub-Signature-256'],
        };

        foreach ($headerNames as $headerName) {
            $signature = $request->header($headerName);
            if ($signature) {
                // "sha256=" prefix'ini temizle
                if (str_starts_with($signature, 'sha256=')) {
                    $signature = substr($signature, 7);
                }
                return $signature;
            }
        }

        return null;
    }

    /**
     * Beklenen imzayi hesapla
     */
    private function calculateSignature(string $payload, string $secret, string $platform): string
    {
        // Platform bazli algoritma
        $algorithm = match (strtolower($platform)) {
            'trendyol' => 'sha512',
            default => 'sha256',
        };

        return hash_hmac($algorithm, $payload, $secret);
    }

    /**
     * Timestamp dogrula
     */
    private function verifyTimestamp(Request $request, string $platform): bool
    {
        $timestamp = $this->getTimestampFromRequest($request, $platform);

        if ($timestamp === null) {
            // Timestamp header'i yoksa, bu kontrolu atla
            // (bazi platformlar timestamp gondermiyor)
            return true;
        }

        $currentTime = time();
        $diff = abs($currentTime - $timestamp);

        return $diff <= self::MAX_TIMESTAMP_DIFF;
    }

    /**
     * Request'ten timestamp'i al
     */
    private function getTimestampFromRequest(Request $request, string $platform): ?int
    {
        $headerNames = ['X-Timestamp', 'X-Webhook-Timestamp', 'X-Request-Timestamp'];

        foreach ($headerNames as $headerName) {
            $timestamp = $request->header($headerName);
            if ($timestamp && is_numeric($timestamp)) {
                return (int) $timestamp;
            }
        }

        // Body icinde timestamp kontrolu
        $body = $request->all();
        if (isset($body['timestamp']) && is_numeric($body['timestamp'])) {
            return (int) $body['timestamp'];
        }

        return null;
    }

    /**
     * Loglama icin guvenli header'lari al
     */
    private function getSafeHeaders(Request $request): array
    {
        $safeHeaders = [];
        $allowedHeaders = [
            'content-type',
            'user-agent',
            'x-forwarded-for',
            'x-real-ip',
        ];

        foreach ($allowedHeaders as $header) {
            $value = $request->header($header);
            if ($value) {
                $safeHeaders[$header] = $value;
            }
        }

        return $safeHeaders;
    }
}

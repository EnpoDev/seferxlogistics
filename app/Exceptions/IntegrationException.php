<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationException extends Exception
{
    protected string $platform;
    protected string $errorCode;
    protected array $context;
    protected ?string $responseBody;

    public function __construct(
        string $message,
        string $platform,
        string $errorCode = 'INTEGRATION_ERROR',
        array $context = [],
        ?string $responseBody = null,
        int $httpCode = 502,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $httpCode, $previous);
        $this->platform = $platform;
        $this->errorCode = $errorCode;
        $this->context = $context;
        $this->responseBody = $responseBody;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function render(Request $request): JsonResponse|null
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $this->errorCode,
                    'message' => $this->getMessage(),
                    'platform' => $this->platform,
                ],
            ], $this->getCode());
        }

        return null;
    }

    public function report(): void
    {
        \Log::error('Integration Exception', [
            'platform' => $this->platform,
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'response_body' => $this->responseBody,
            'trace' => $this->getTraceAsString(),
        ]);
    }

    // Factory methods for common integration errors
    public static function connectionFailed(string $platform, ?string $details = null): self
    {
        return new self(
            $details ?? "{$platform} bağlantısı başarısız",
            $platform,
            'CONNECTION_FAILED',
            [],
            null,
            503
        );
    }

    public static function authenticationFailed(string $platform): self
    {
        return new self(
            "{$platform} kimlik doğrulaması başarısız",
            $platform,
            'AUTH_FAILED',
            [],
            null,
            401
        );
    }

    public static function rateLimited(string $platform, int $retryAfter = 60): self
    {
        return new self(
            "{$platform} istek limiti aşıldı. {$retryAfter} saniye sonra tekrar deneyin.",
            $platform,
            'RATE_LIMITED',
            ['retry_after' => $retryAfter],
            null,
            429
        );
    }

    public static function invalidResponse(string $platform, ?string $responseBody = null): self
    {
        return new self(
            "{$platform} geçersiz yanıt döndü",
            $platform,
            'INVALID_RESPONSE',
            [],
            $responseBody,
            502
        );
    }

    public static function webhookValidationFailed(string $platform): self
    {
        return new self(
            "{$platform} webhook doğrulaması başarısız",
            $platform,
            'WEBHOOK_VALIDATION_FAILED',
            [],
            null,
            401
        );
    }

    public static function orderSyncFailed(string $platform, string $orderId, ?string $reason = null): self
    {
        return new self(
            $reason ?? "{$platform} sipariş senkronizasyonu başarısız",
            $platform,
            'ORDER_SYNC_FAILED',
            ['external_order_id' => $orderId],
            null,
            422
        );
    }

    public static function platformNotConfigured(string $platform): self
    {
        return new self(
            "{$platform} entegrasyonu yapılandırılmamış",
            $platform,
            'NOT_CONFIGURED',
            [],
            null,
            422
        );
    }
}

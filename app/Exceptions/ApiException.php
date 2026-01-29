<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiException extends Exception
{
    protected string $errorCode;
    protected array $errors;
    protected array $meta;

    public function __construct(
        string $message,
        string $errorCode = 'API_ERROR',
        int $httpCode = 400,
        array $errors = [],
        array $meta = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, $httpCode, $previous);
        $this->errorCode = $errorCode;
        $this->errors = $errors;
        $this->meta = $meta;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
            ],
        ];

        if (!empty($this->errors)) {
            $response['error']['details'] = $this->errors;
        }

        if (!empty($this->meta)) {
            $response['meta'] = $this->meta;
        }

        return response()->json($response, $this->getCode());
    }

    public function report(): void
    {
        \Log::warning('API Exception', [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'meta' => $this->meta,
        ]);
    }

    // Factory methods for common API errors
    public static function badRequest(string $message = 'Geçersiz istek'): self
    {
        return new self($message, 'BAD_REQUEST', 400);
    }

    public static function unauthorized(string $message = 'Yetkilendirme gerekli'): self
    {
        return new self($message, 'UNAUTHORIZED', 401);
    }

    public static function forbidden(string $message = 'Erişim reddedildi'): self
    {
        return new self($message, 'FORBIDDEN', 403);
    }

    public static function notFound(string $resource = 'Kaynak'): self
    {
        return new self("{$resource} bulunamadı", 'NOT_FOUND', 404);
    }

    public static function methodNotAllowed(): self
    {
        return new self('HTTP metodu desteklenmiyor', 'METHOD_NOT_ALLOWED', 405);
    }

    public static function conflict(string $message = 'Çakışma hatası'): self
    {
        return new self($message, 'CONFLICT', 409);
    }

    public static function validationFailed(array $errors): self
    {
        return new self(
            'Doğrulama hatası',
            'VALIDATION_FAILED',
            422,
            $errors
        );
    }

    public static function tooManyRequests(int $retryAfter = 60): self
    {
        return new self(
            "Çok fazla istek. {$retryAfter} saniye sonra tekrar deneyin.",
            'TOO_MANY_REQUESTS',
            429,
            [],
            ['retry_after' => $retryAfter]
        );
    }

    public static function serverError(string $message = 'Sunucu hatası'): self
    {
        return new self($message, 'SERVER_ERROR', 500);
    }

    public static function serviceUnavailable(string $service = 'Servis'): self
    {
        return new self("{$service} şu anda kullanılamıyor", 'SERVICE_UNAVAILABLE', 503);
    }
}

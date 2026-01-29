<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessException extends Exception
{
    protected string $errorCode;
    protected array $context;

    public function __construct(
        string $message,
        string $errorCode = 'BUSINESS_ERROR',
        array $context = [],
        int $httpCode = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $httpCode, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function render(Request $request): JsonResponse|null
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $this->errorCode,
                    'message' => $this->getMessage(),
                    'context' => $this->context,
                ],
            ], $this->getCode());
        }

        return null;
    }

    public function report(): void
    {
        \Log::warning('Business Exception', [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }

    // Factory methods for common business errors
    public static function orderNotFound(int $orderId): self
    {
        return new self(
            "Sipariş bulunamadı: #{$orderId}",
            'ORDER_NOT_FOUND',
            ['order_id' => $orderId],
            404
        );
    }

    public static function courierNotAvailable(int $courierId): self
    {
        return new self(
            "Kurye müsait değil",
            'COURIER_NOT_AVAILABLE',
            ['courier_id' => $courierId],
            422
        );
    }

    public static function orderAlreadyAssigned(int $orderId): self
    {
        return new self(
            "Sipariş zaten bir kuryeye atanmış",
            'ORDER_ALREADY_ASSIGNED',
            ['order_id' => $orderId],
            422
        );
    }

    public static function invalidOrderStatus(string $currentStatus, string $requiredStatus): self
    {
        return new self(
            "Geçersiz sipariş durumu. Mevcut: {$currentStatus}, Gerekli: {$requiredStatus}",
            'INVALID_ORDER_STATUS',
            ['current' => $currentStatus, 'required' => $requiredStatus],
            422
        );
    }

    public static function insufficientPermission(string $action): self
    {
        return new self(
            "Bu işlem için yetkiniz yok: {$action}",
            'INSUFFICIENT_PERMISSION',
            ['action' => $action],
            403
        );
    }

    public static function branchNotActive(int $branchId): self
    {
        return new self(
            "Şube aktif değil",
            'BRANCH_NOT_ACTIVE',
            ['branch_id' => $branchId],
            422
        );
    }

    public static function poolNotEnabled(): self
    {
        return new self(
            "Havuz sistemi bu şube için aktif değil",
            'POOL_NOT_ENABLED',
            [],
            422
        );
    }
}

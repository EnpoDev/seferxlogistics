<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DatabaseException extends Exception
{
    protected string $errorCode;
    protected string $operation;
    protected ?string $table;
    protected array $context;

    public function __construct(
        string $message,
        string $operation,
        ?string $table = null,
        string $errorCode = 'DATABASE_ERROR',
        array $context = [],
        int $httpCode = 500,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $httpCode, $previous);
        $this->operation = $operation;
        $this->table = $table;
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function render(Request $request): JsonResponse|null
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            // Production'da detayları gizle
            $message = app()->environment('production')
                ? 'Veritabanı işlemi başarısız'
                : $this->getMessage();

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $this->errorCode,
                    'message' => $message,
                ],
            ], $this->getCode());
        }

        return null;
    }

    public function report(): void
    {
        \Log::error('Database Exception', [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'operation' => $this->operation,
            'table' => $this->table,
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }

    // Factory methods for common database errors
    public static function connectionFailed(?string $details = null): self
    {
        return new self(
            $details ?? 'Veritabanı bağlantısı kurulamadı',
            'connect',
            null,
            'CONNECTION_FAILED',
            [],
            503
        );
    }

    public static function queryFailed(string $operation, ?string $table = null, ?string $details = null): self
    {
        return new self(
            $details ?? 'Veritabanı sorgusu başarısız',
            $operation,
            $table,
            'QUERY_FAILED',
            [],
            500
        );
    }

    public static function transactionFailed(?string $details = null): self
    {
        return new self(
            $details ?? 'Veritabanı işlemi geri alındı',
            'transaction',
            null,
            'TRANSACTION_FAILED',
            [],
            500
        );
    }

    public static function duplicateEntry(string $table, string $field): self
    {
        return new self(
            "Bu {$field} değeri zaten kullanılıyor",
            'insert',
            $table,
            'DUPLICATE_ENTRY',
            ['field' => $field],
            422
        );
    }

    public static function foreignKeyViolation(string $table, string $referencedTable): self
    {
        return new self(
            "Bu kayıt başka kayıtlarla ilişkili olduğu için silinemez",
            'delete',
            $table,
            'FOREIGN_KEY_VIOLATION',
            ['referenced_table' => $referencedTable],
            422
        );
    }

    public static function recordNotFound(string $table, int|string $id): self
    {
        return new self(
            "Kayıt bulunamadı",
            'select',
            $table,
            'RECORD_NOT_FOUND',
            ['id' => $id],
            404
        );
    }

    public static function deadlock(): self
    {
        return new self(
            'Veritabanı kilitlenmesi. Lütfen tekrar deneyin.',
            'transaction',
            null,
            'DEADLOCK',
            [],
            503
        );
    }
}

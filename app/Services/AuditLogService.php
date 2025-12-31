<?php

namespace App\Services;

use App\Helpers\PrivacyHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Yapılandırılmış Güvenlik ve Denetim Log Servisi
 *
 * KVKK Madde 12: Veri güvenliğine ilişkin yükümlülükler
 * - Kişisel verilere yapılan erişimlerin loglanması
 * - Yetkisiz erişim girişimlerinin kaydedilmesi
 *
 * @package App\Services
 * @version 1.0.0
 */
class AuditLogService
{
    /**
     * Log seviyeleri
     */
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_SECURITY = 'security';

    /**
     * Eylem tipleri
     */
    public const ACTION_LOGIN = 'auth.login';
    public const ACTION_LOGOUT = 'auth.logout';
    public const ACTION_LOGIN_FAILED = 'auth.login_failed';
    public const ACTION_PASSWORD_RESET = 'auth.password_reset';

    public const ACTION_DATA_ACCESS = 'data.access';
    public const ACTION_DATA_CREATE = 'data.create';
    public const ACTION_DATA_UPDATE = 'data.update';
    public const ACTION_DATA_DELETE = 'data.delete';
    public const ACTION_DATA_EXPORT = 'data.export';

    public const ACTION_PERMISSION_DENIED = 'security.permission_denied';
    public const ACTION_RATE_LIMITED = 'security.rate_limited';
    public const ACTION_SUSPICIOUS = 'security.suspicious_activity';

    public const ACTION_PAYMENT = 'payment.process';
    public const ACTION_INTEGRATION = 'integration.sync';
    public const ACTION_WEBHOOK = 'integration.webhook';

    /**
     * Yapılandırılmış log kaydı oluştur
     *
     * @param string $action Eylem tipi (ACTION_* sabitleri)
     * @param string $status Durum (success, failed, pending)
     * @param array $context Ek bağlam bilgileri
     * @param string $level Log seviyesi
     * @return void
     */
    public static function log(
        string $action,
        string $status,
        array $context = [],
        string $level = self::LEVEL_INFO
    ): void {
        $logEntry = self::buildLogEntry($action, $status, $context);

        // Hassas verileri temizle
        $sanitizedEntry = PrivacyHelper::sanitizeForLogging($logEntry);

        // Laravel log kanalına yaz
        match ($level) {
            self::LEVEL_CRITICAL => Log::channel('security')->critical(json_encode($sanitizedEntry)),
            self::LEVEL_ERROR => Log::channel('security')->error(json_encode($sanitizedEntry)),
            self::LEVEL_WARNING => Log::channel('security')->warning(json_encode($sanitizedEntry)),
            self::LEVEL_SECURITY => Log::channel('security')->notice(json_encode($sanitizedEntry)),
            default => Log::channel('daily')->info(json_encode($sanitizedEntry)),
        };
    }

    /**
     * Kimlik doğrulama başarılı
     */
    public static function logLoginSuccess(int $userId, string $userType = 'user'): void
    {
        self::log(self::ACTION_LOGIN, 'success', [
            'user_id' => $userId,
            'user_type' => $userType,
        ]);
    }

    /**
     * Kimlik doğrulama başarısız
     */
    public static function logLoginFailed(string $identifier, string $reason = 'invalid_credentials'): void
    {
        self::log(self::ACTION_LOGIN_FAILED, 'failed', [
            'identifier' => PrivacyHelper::maskEmail($identifier) ?: PrivacyHelper::maskPhone($identifier),
            'reason' => $reason,
        ], self::LEVEL_WARNING);
    }

    /**
     * Veri erişimi logla
     */
    public static function logDataAccess(string $model, $recordId, string $accessType = 'read'): void
    {
        self::log(self::ACTION_DATA_ACCESS, 'success', [
            'model' => $model,
            'record_id' => $recordId,
            'access_type' => $accessType,
        ]);
    }

    /**
     * Veri değişikliği logla
     */
    public static function logDataChange(
        string $action,
        string $model,
        $recordId,
        array $oldData = [],
        array $newData = []
    ): void {
        self::log($action, 'success', [
            'model' => $model,
            'record_id' => $recordId,
            'changes' => [
                'old' => PrivacyHelper::sanitizeForLogging($oldData),
                'new' => PrivacyHelper::sanitizeForLogging($newData),
            ],
        ]);
    }

    /**
     * Yetki reddedildi
     */
    public static function logPermissionDenied(string $resource, string $action): void
    {
        self::log(self::ACTION_PERMISSION_DENIED, 'denied', [
            'resource' => $resource,
            'attempted_action' => $action,
        ], self::LEVEL_SECURITY);
    }

    /**
     * Rate limit aşıldı
     */
    public static function logRateLimited(string $endpoint): void
    {
        self::log(self::ACTION_RATE_LIMITED, 'blocked', [
            'endpoint' => $endpoint,
        ], self::LEVEL_WARNING);
    }

    /**
     * Şüpheli aktivite
     */
    public static function logSuspiciousActivity(string $description, array $details = []): void
    {
        self::log(self::ACTION_SUSPICIOUS, 'detected', [
            'description' => $description,
            'details' => $details,
        ], self::LEVEL_CRITICAL);
    }

    /**
     * Ödeme işlemi logla
     */
    public static function logPayment(string $transactionId, string $status, float $amount): void
    {
        self::log(self::ACTION_PAYMENT, $status, [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);
    }

    /**
     * Entegrasyon işlemi logla
     */
    public static function logIntegration(string $platform, string $action, string $status, array $context = []): void
    {
        self::log(self::ACTION_INTEGRATION, $status, array_merge([
            'platform' => $platform,
            'integration_action' => $action,
        ], PrivacyHelper::sanitizeForLogging($context)));
    }

    /**
     * Webhook logla
     */
    public static function logWebhook(string $platform, string $status, array $payload = []): void
    {
        self::log(self::ACTION_WEBHOOK, $status, [
            'platform' => $platform,
            'payload_size' => strlen(json_encode($payload)),
            'payload_hash' => hash('sha256', json_encode($payload)),
        ]);
    }

    /**
     * Log girişi oluştur
     */
    private static function buildLogEntry(string $action, string $status, array $context): array
    {
        $user = Auth::user();
        $request = Request::instance();

        return [
            'timestamp' => now()->toIso8601String(),
            'level' => 'INFO',
            'action' => $action,
            'status' => $status,
            'actor' => [
                'id' => $user?->id,
                'type' => self::getActorType(),
                'ip' => PrivacyHelper::maskIpAddress($request->ip()),
                'user_agent' => substr($request->userAgent() ?? '', 0, 100),
            ],
            'request' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'request_id' => $request->header('X-Request-ID', uniqid('req_')),
            ],
            'context' => $context,
            'environment' => app()->environment(),
        ];
    }

    /**
     * Aktör tipini belirle
     */
    private static function getActorType(): string
    {
        $user = Auth::user();

        if (!$user) {
            return 'anonymous';
        }

        if (Auth::guard('courier')->check()) {
            return 'courier';
        }

        $roles = $user->roles ?? [];

        if (in_array('admin', $roles)) {
            return 'admin';
        }
        if (in_array('bayi', $roles)) {
            return 'bayi';
        }
        if (in_array('isletme', $roles)) {
            return 'isletme';
        }

        return 'user';
    }
}

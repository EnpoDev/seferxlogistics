<?php

namespace App\Services;

use App\Models\User;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * KVKK/GDPR Uyumluluk Servisi
 *
 * Kisisel Verilerin Korunmasi Kanunu (KVKK) ve
 * General Data Protection Regulation (GDPR) gereksinimlerini karsilar.
 *
 * Desteklenen Haklar:
 * - Madde 11/b: Kisisel veri islenmisse bilgi talep etme
 * - Madde 11/c: Kisisel verilerin aktarilmasini isteme (Data Portability)
 * - Madde 11/e: Kisisel verilerin silinmesini isteme (Right to be Forgotten)
 * - Madde 11/g: Islenen verilerin duzeltilmesini isteme
 *
 * @package App\Services
 * @version 1.0.0
 */
class KvkkComplianceService
{
    /**
     * Veri saklama suresi (gun)
     */
    private int $retentionDays;

    public function __construct()
    {
        $this->retentionDays = (int) config('app.data_retention_days', 730);
    }

    // =========================================================================
    // VERI EXPORT (Data Portability - KVKK Madde 11/c, GDPR Article 20)
    // =========================================================================

    /**
     * Kullanici verilerini JSON formatinda export et
     *
     * @param User $user
     * @return array Export sonucu
     */
    public function exportUserData(User $user): array
    {
        $exportData = [
            'export_date' => now()->toIso8601String(),
            'data_controller' => 'SeferX Lojistik',
            'data_subject' => [
                'id' => $user->id,
                'type' => 'user',
            ],
            'personal_data' => $this->collectUserPersonalData($user),
            'processing_activities' => $this->collectProcessingActivities($user),
            'consent_records' => $this->collectConsentRecords($user),
        ];

        // JSON dosyasi olustur
        $filename = "kvkk_export_user_{$user->id}_" . now()->format('Ymd_His') . '.json';
        $path = "exports/{$filename}";

        Storage::put($path, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Export logla
        AuditLogService::log(
            AuditLogService::ACTION_DATA_EXPORT,
            'success',
            ['user_id' => $user->id, 'filename' => $filename]
        );

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'download_url' => Storage::temporaryUrl($path, now()->addHours(24)),
            'expires_at' => now()->addHours(24)->toIso8601String(),
        ];
    }

    /**
     * Kurye verilerini export et
     *
     * @param Courier $courier
     * @return array
     */
    public function exportCourierData(Courier $courier): array
    {
        $exportData = [
            'export_date' => now()->toIso8601String(),
            'data_controller' => 'SeferX Lojistik',
            'data_subject' => [
                'id' => $courier->id,
                'type' => 'courier',
            ],
            'personal_data' => [
                'identity' => [
                    'name' => $courier->name,
                    'email' => $courier->email,
                    'phone' => $courier->phone,
                    'tc_no' => $courier->tc_no ? '***MASKED***' : null,
                    'birth_date' => $courier->birth_date,
                    'gender' => $courier->gender,
                ],
                'contact' => [
                    'address' => $courier->address,
                    'emergency_contact' => $courier->emergency_contact,
                    'emergency_phone' => $courier->emergency_phone,
                ],
                'financial' => [
                    'iban' => $courier->iban ? '***MASKED***' : null,
                    'tax_number' => $courier->tax_number ? '***MASKED***' : null,
                ],
                'employment' => [
                    'working_type' => $courier->working_type,
                    'status' => $courier->status,
                    'hired_at' => $courier->created_at,
                ],
                'vehicle' => [
                    'type' => $courier->vehicle_type,
                    'plate' => $courier->vehicle_plate,
                    'license_expiry' => $courier->license_expiry_date,
                ],
            ],
            'delivery_history' => $this->collectCourierDeliveryHistory($courier),
            'earnings_summary' => $this->collectCourierEarnings($courier),
            'location_history_note' => 'Konum verileri gizlilik nedeniyle dahil edilmemistir.',
        ];

        $filename = "kvkk_export_courier_{$courier->id}_" . now()->format('Ymd_His') . '.json';
        $path = "exports/{$filename}";

        Storage::put($path, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        AuditLogService::log(
            AuditLogService::ACTION_DATA_EXPORT,
            'success',
            ['courier_id' => $courier->id, 'filename' => $filename]
        );

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
        ];
    }

    // =========================================================================
    // VERI SILME (Right to be Forgotten - KVKK Madde 11/e, GDPR Article 17)
    // =========================================================================

    /**
     * Kullanici verilerini anonimize et (silme yerine)
     *
     * Not: Yasal saklama yukumlulukleri nedeniyle bazi veriler
     * tamamen silinmek yerine anonimize edilir.
     *
     * @param User $user
     * @param string $reason Silme nedeni
     * @return array
     */
    public function anonymizeUser(User $user, string $reason = 'user_request'): array
    {
        DB::beginTransaction();

        try {
            $originalId = $user->id;
            $anonymizedAt = now();

            // 1. Kullanici bilgilerini anonimize et
            $user->update([
                'name' => 'Anonim Kullanici #' . $user->id,
                'email' => "deleted_{$user->id}@anonymized.local",
                'password' => Hash::make(str()->random(64)),
                'remember_token' => null,
            ]);

            // 2. Iliskili siparislerdeki kisisel verileri anonimize et
            Order::where('user_id', $user->id)->update([
                'customer_name' => 'Anonim Musteri',
                'customer_phone' => '0000000000',
                'customer_address' => 'Adres silindi',
                'customer_notes' => null,
            ]);

            // 3. Silme kaydini olustur
            DB::table('data_deletion_logs')->insert([
                'deletable_type' => User::class,
                'deletable_id' => $originalId,
                'reason' => $reason,
                'anonymized_at' => $anonymizedAt,
                'requested_by' => auth()->id(),
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            AuditLogService::log(
                AuditLogService::ACTION_DATA_DELETE,
                'success',
                [
                    'user_id' => $originalId,
                    'reason' => $reason,
                    'type' => 'anonymization',
                ]
            );

            return [
                'success' => true,
                'message' => 'Kullanici verileri basariyla anonimize edildi.',
                'anonymized_at' => $anonymizedAt->toIso8601String(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            AuditLogService::log(
                AuditLogService::ACTION_DATA_DELETE,
                'failed',
                ['user_id' => $user->id, 'error' => $e->getMessage()],
                AuditLogService::LEVEL_ERROR
            );

            return [
                'success' => false,
                'message' => 'Anonimizasyon sirasinda hata olustu.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Kurye verilerini anonimize et
     *
     * @param Courier $courier
     * @param string $reason
     * @return array
     */
    public function anonymizeCourier(Courier $courier, string $reason = 'user_request'): array
    {
        DB::beginTransaction();

        try {
            $originalId = $courier->id;

            // Kurye bilgilerini anonimize et
            $courier->update([
                'name' => 'Eski Kurye #' . $courier->id,
                'email' => "deleted_courier_{$courier->id}@anonymized.local",
                'phone' => '0000000000',
                'tc_no' => null,
                'address' => null,
                'iban' => null,
                'tax_number' => null,
                'emergency_contact' => null,
                'emergency_phone' => null,
                'password' => null,
                'photo_path' => null,
                'status' => 'deleted',
            ]);

            // Silme kaydini olustur
            DB::table('data_deletion_logs')->insert([
                'deletable_type' => Courier::class,
                'deletable_id' => $originalId,
                'reason' => $reason,
                'anonymized_at' => now(),
                'requested_by' => auth()->id(),
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            AuditLogService::log(
                AuditLogService::ACTION_DATA_DELETE,
                'success',
                ['courier_id' => $originalId, 'reason' => $reason]
            );

            return [
                'success' => true,
                'message' => 'Kurye verileri basariyla anonimize edildi.',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // VERI SAKLAMA POLITIKASI (Data Retention)
    // =========================================================================

    /**
     * Suresi dolmus verileri temizle
     *
     * Bu metod gunluk cron job ile calistirilmalidir.
     *
     * @return array Temizleme raporu
     */
    public function cleanupExpiredData(): array
    {
        $cutoffDate = now()->subDays($this->retentionDays);
        $report = [];

        // 1. Eski export dosyalarini sil
        $expiredExports = Storage::files('exports');
        $deletedExports = 0;
        foreach ($expiredExports as $file) {
            $lastModified = Storage::lastModified($file);
            if (Carbon::createFromTimestamp($lastModified)->lt($cutoffDate)) {
                Storage::delete($file);
                $deletedExports++;
            }
        }
        $report['deleted_exports'] = $deletedExports;

        // 2. Eski session'lari temizle
        $deletedSessions = DB::table('sessions')
            ->where('last_activity', '<', $cutoffDate->timestamp)
            ->delete();
        $report['deleted_sessions'] = $deletedSessions;

        // 3. Eski log kayitlarini arsivle (silmiyoruz, KVKK geregi)
        // Log dosyalari logging.php'de days parametresiyle kontrol ediliyor

        AuditLogService::log(
            'system.cleanup',
            'success',
            $report
        );

        return $report;
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    private function collectUserPersonalData(User $user): array
    {
        return [
            'identity' => [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles,
            ],
            'account' => [
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
        ];
    }

    private function collectProcessingActivities(User $user): array
    {
        return [
            'orders_count' => Order::where('user_id', $user->id)->count(),
            'last_order_date' => Order::where('user_id', $user->id)
                ->latest()
                ->first()
                ?->created_at
                ?->toIso8601String(),
            'login_count_30_days' => DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('last_activity', '>', now()->subDays(30)->timestamp)
                ->count(),
        ];
    }

    private function collectConsentRecords(User $user): array
    {
        if (!DB::getSchemaBuilder()->hasTable('user_consents')) {
            return ['note' => 'Consent tablosu henuz olusturulmamis'];
        }

        return DB::table('user_consents')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn($c) => [
                'type' => $c->consent_type,
                'given_at' => $c->created_at,
                'withdrawn_at' => $c->withdrawn_at,
            ])
            ->toArray();
    }

    private function collectCourierDeliveryHistory(Courier $courier): array
    {
        return [
            'total_deliveries' => Order::where('courier_id', $courier->id)
                ->where('status', 'delivered')
                ->count(),
            'monthly_summary' => Order::where('courier_id', $courier->id)
                ->where('status', 'delivered')
                ->selectRaw('DATE_FORMAT(delivered_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get()
                ->toArray(),
        ];
    }

    private function collectCourierEarnings(Courier $courier): array
    {
        // Ozet bilgi, detayli finansal veri dahil edilmez
        return [
            'note' => 'Detayli finansal veriler guvenlik nedeniyle dahil edilmemistir.',
            'total_orders' => Order::where('courier_id', $courier->id)->count(),
        ];
    }
}

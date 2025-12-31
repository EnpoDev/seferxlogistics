<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kullanici Riza Yonetimi Modeli
 *
 * KVKK Madde 5: Kisisel verilerin islenmesi sartisindan
 * acik riza alinmasini yonetir.
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $courier_id
 * @property string $consent_type
 * @property bool $is_granted
 * @property string|null $consent_text
 * @property string $consent_version
 * @property string|null $ip_address
 * @property \Carbon\Carbon|null $granted_at
 * @property \Carbon\Carbon|null $withdrawn_at
 */
class UserConsent extends Model
{
    protected $fillable = [
        'user_id',
        'courier_id',
        'consent_type',
        'is_granted',
        'consent_text',
        'consent_version',
        'ip_address',
        'user_agent',
        'granted_at',
        'withdrawn_at',
        'withdrawal_reason',
    ];

    protected $casts = [
        'is_granted' => 'boolean',
        'granted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    // Riza Tipleri
    public const TYPE_DATA_PROCESSING = 'data_processing';
    public const TYPE_MARKETING = 'marketing';
    public const TYPE_NEWSLETTER = 'newsletter';
    public const TYPE_LOCATION_TRACKING = 'location_tracking';
    public const TYPE_COOKIES = 'cookies';
    public const TYPE_THIRD_PARTY_SHARING = 'third_party_sharing';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    // Scopes
    public function scopeGranted($query)
    {
        return $query->where('is_granted', true)->whereNull('withdrawn_at');
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForCourier($query, int $courierId)
    {
        return $query->where('courier_id', $courierId);
    }

    // Methods

    /**
     * Rizayi geri cek
     */
    public function withdraw(string $reason = null): bool
    {
        return $this->update([
            'is_granted' => false,
            'withdrawn_at' => now(),
            'withdrawal_reason' => $reason,
        ]);
    }

    /**
     * Riza aktif mi kontrol et
     */
    public function isActive(): bool
    {
        return $this->is_granted && $this->withdrawn_at === null;
    }

    // Static Methods

    /**
     * Kullanici icin riza olustur veya guncelle
     */
    public static function grantForUser(
        int $userId,
        string $consentType,
        string $consentText = null,
        string $version = '1.0'
    ): self {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'consent_type' => $consentType,
            ],
            [
                'is_granted' => true,
                'consent_text' => $consentText,
                'consent_version' => $version,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'granted_at' => now(),
                'withdrawn_at' => null,
            ]
        );
    }

    /**
     * Kurye icin riza olustur veya guncelle
     */
    public static function grantForCourier(
        int $courierId,
        string $consentType,
        string $consentText = null,
        string $version = '1.0'
    ): self {
        return self::updateOrCreate(
            [
                'courier_id' => $courierId,
                'consent_type' => $consentType,
            ],
            [
                'is_granted' => true,
                'consent_text' => $consentText,
                'consent_version' => $version,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'granted_at' => now(),
                'withdrawn_at' => null,
            ]
        );
    }

    /**
     * Kullanicinin belirli bir riza tipi icin izni var mi
     */
    public static function hasConsent(int $userId, string $consentType): bool
    {
        return self::where('user_id', $userId)
            ->where('consent_type', $consentType)
            ->granted()
            ->exists();
    }

    /**
     * Kurye'nin belirli bir riza tipi icin izni var mi
     */
    public static function courierHasConsent(int $courierId, string $consentType): bool
    {
        return self::where('courier_id', $courierId)
            ->where('consent_type', $consentType)
            ->granted()
            ->exists();
    }

    /**
     * Riza tipi aciklamasini getir
     */
    public static function getTypeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_DATA_PROCESSING => 'Kisisel Veri Isleme',
            self::TYPE_MARKETING => 'Pazarlama Iletisimleri',
            self::TYPE_NEWSLETTER => 'E-Bulten',
            self::TYPE_LOCATION_TRACKING => 'Konum Takibi',
            self::TYPE_COOKIES => 'Cerezler',
            self::TYPE_THIRD_PARTY_SHARING => 'Ucuncu Taraf Paylasimi',
            default => $type,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class PaymentCard extends Model
{
    protected $fillable = [
        'user_id',
        'card_holder_name',
        'card_number_last4',
        'card_brand',
        'expiry_month',
        'expiry_year',
        'is_default',
        'token', // Payment gateway token
        'gateway', // iyzico, paytr, stripe
    ];

    protected $casts = [
        'expiry_month' => 'integer',
        'expiry_year' => 'integer',
        'is_default' => 'boolean',
    ];

    protected $hidden = [
        'token',
    ];

    const BRAND_VISA = 'visa';
    const BRAND_MASTERCARD = 'mastercard';
    const BRAND_AMEX = 'amex';
    const BRAND_TROY = 'troy';
    const BRAND_UNKNOWN = 'unknown';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Accessors
    public function getMaskedNumber(): string
    {
        return '**** **** **** ' . $this->card_number_last4;
    }

    public function getExpiryDate(): string
    {
        return sprintf('%02d/%02d', $this->expiry_month, $this->expiry_year % 100);
    }

    public function getBrandIcon(): string
    {
        return match ($this->card_brand) {
            self::BRAND_VISA => '💳',
            self::BRAND_MASTERCARD => '💳',
            self::BRAND_AMEX => '💳',
            self::BRAND_TROY => '💳',
            default => '💳',
        };
    }

    public function getBrandLabel(): string
    {
        return match ($this->card_brand) {
            self::BRAND_VISA => 'Visa',
            self::BRAND_MASTERCARD => 'Mastercard',
            self::BRAND_AMEX => 'American Express',
            self::BRAND_TROY => 'Troy',
            default => 'Kredi Kartı',
        };
    }

    // Methods
    public function isExpired(): bool
    {
        $now = now();
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('m');

        if ($this->expiry_year < $currentYear) {
            return true;
        }

        if ($this->expiry_year === $currentYear && $this->expiry_month < $currentMonth) {
            return true;
        }

        return false;
    }

    public function setAsDefault(): bool
    {
        // Remove default from other cards
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        return $this->update(['is_default' => true]);
    }

    /**
     * Set encrypted token
     */
    public function setTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['token'] = Crypt::encryptString($value);
        } else {
            $this->attributes['token'] = null;
        }
    }

    /**
     * Get decrypted token
     */
    public function getTokenAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Detect card brand from number
     */
    public static function detectBrand(string $cardNumber): string
    {
        $number = preg_replace('/\D/', '', $cardNumber);
        
        if (preg_match('/^4/', $number)) {
            return self::BRAND_VISA;
        }
        
        if (preg_match('/^5[1-5]/', $number) || preg_match('/^2[2-7]/', $number)) {
            return self::BRAND_MASTERCARD;
        }
        
        if (preg_match('/^3[47]/', $number)) {
            return self::BRAND_AMEX;
        }
        
        if (preg_match('/^9792/', $number)) {
            return self::BRAND_TROY;
        }
        
        return self::BRAND_UNKNOWN;
    }
}


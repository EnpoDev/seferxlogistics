<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'language',
        'timezone',
        'currency',
        'auto_accept_orders',
        'sound_notifications',
        'default_order_timeout', // in minutes
        'default_preparation_time', // in minutes
    ];

    protected $casts = [
        'auto_accept_orders' => 'boolean',
        'sound_notifications' => 'boolean',
        'default_order_timeout' => 'integer',
        'default_preparation_time' => 'integer',
    ];

    const LANGUAGE_TR = 'tr';
    const LANGUAGE_EN = 'en';

    const CURRENCY_TRY = 'TRY';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_USD = 'USD';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Methods
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'language' => self::LANGUAGE_TR,
                'timezone' => 'Europe/Istanbul',
                'currency' => self::CURRENCY_TRY,
                'auto_accept_orders' => false,
                'sound_notifications' => true,
                'default_order_timeout' => 30,
                'default_preparation_time' => 20,
            ]
        );
    }

    public function getLanguageLabel(): string
    {
        return match ($this->language) {
            self::LANGUAGE_TR => 'Türkçe',
            self::LANGUAGE_EN => 'English',
            default => $this->language,
        };
    }

    public function getCurrencyLabel(): string
    {
        return match ($this->currency) {
            self::CURRENCY_TRY => 'Türk Lirası (₺)',
            self::CURRENCY_EUR => 'Euro (€)',
            self::CURRENCY_USD => 'Dolar ($)',
            default => $this->currency,
        };
    }

    public function getCurrencySymbol(): string
    {
        return match ($this->currency) {
            self::CURRENCY_TRY => '₺',
            self::CURRENCY_EUR => '€',
            self::CURRENCY_USD => '$',
            default => $this->currency,
        };
    }

    public static function getTimezones(): array
    {
        return [
            'Europe/Istanbul' => 'İstanbul (GMT+3)',
            'Europe/London' => 'Londra (GMT+0)',
            'Europe/Berlin' => 'Berlin (GMT+1)',
            'America/New_York' => 'New York (GMT-5)',
            'Asia/Dubai' => 'Dubai (GMT+4)',
        ];
    }
}


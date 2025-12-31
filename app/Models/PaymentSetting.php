<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSetting extends Model
{
    protected $fillable = [
        'user_id',
        'accept_cash',
        'accept_card',
        'accept_card_on_delivery',
        'accept_online',
        'payment_provider',
        'provider_settings',
        'min_order_amount',
        'max_cash_amount',
    ];

    protected $casts = [
        'accept_cash' => 'boolean',
        'accept_card' => 'boolean',
        'accept_card_on_delivery' => 'boolean',
        'accept_online' => 'boolean',
        'provider_settings' => 'array',
        'min_order_amount' => 'decimal:2',
        'max_cash_amount' => 'decimal:2',
    ];

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
                'accept_cash' => true,
                'accept_card' => true,
                'accept_card_on_delivery' => false,
                'accept_online' => false,
                'payment_provider' => null,
            ]
        );
    }

    public function getProviderLabel(): string
    {
        return match ($this->payment_provider) {
            'iyzico' => 'İyzico',
            'paytr' => 'PayTR',
            'stripe' => 'Stripe',
            default => 'Seçilmedi',
        };
    }

    public function getAcceptedMethods(): array
    {
        $methods = [];
        
        if ($this->accept_cash) {
            $methods[] = 'cash';
        }
        if ($this->accept_card) {
            $methods[] = 'card';
        }
        if ($this->accept_card_on_delivery) {
            $methods[] = 'card_on_delivery';
        }
        if ($this->accept_online) {
            $methods[] = 'online';
        }
        
        return $methods;
    }
}

